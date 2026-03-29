<?php
/**
 * Plugin Name: Lattice Mail
 * Plugin URI: https://latticeplugins.com/mail
 * Description: Email marketing and subscriber management for WordPress & WooCommerce
 * Version: 0.2.0
 * Author: Lattice Plugins
 * Author URI: https://latticeplugins.com
 * Text Domain: lattice-mail
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('LATTICE_MAIL_VERSION', '0.2.0');
define('LATTICE_MAIL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LATTICE_MAIL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
final class Lattice_Mail {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-subscriber.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-segment.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-campaign.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-email-template.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-smtp.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/api/class-api-subscribers.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/api/class-api-campaigns.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-subscribe-form.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/admin/class-admin.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-cli.php';
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-unsubscribe-page.php';

        if (defined('WC_VERSION')) {
            require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/woocommerce/class-woocommerce.php';
        }
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('rest_api_init', [$this, 'register_api_routes']);
        add_action('transition_post_status', [$this, 'notify_subscribers_on_publish'], 10, 3);

        add_action('wp_ajax_lattice_mail_subscribe', [$this, 'ajax_subscribe']);
        add_action('wp_ajax_nopriv_lattice_mail_subscribe', [$this, 'ajax_subscribe']);
        add_action('template_redirect', [$this, 'handle_url_actions']);
    }

    public function init() {
        load_plugin_textdomain('lattice-mail', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_subscribers = $wpdb->prefix . 'lattice_mail_subscribers';
        $table_campaigns = $wpdb->prefix . 'lattice_mail_campaigns';
        $table_campaign_recipients = $wpdb->prefix . 'lattice_mail_campaign_recipients';

        $sql_subscribers = "CREATE TABLE $table_subscribers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'active',
            source varchar(50) DEFAULT 'form',
            confirmed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            KEY status (status)
        ) $charset_collate;";

        $sql_campaigns = "CREATE TABLE $table_campaigns (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subject varchar(500) NOT NULL,
            content text NOT NULL,
            status varchar(20) DEFAULT 'draft',
            segment_id bigint(20) DEFAULT 0,
            sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY segment_id (segment_id)
        ) $charset_collate;";

        $sql_recipients = "CREATE TABLE $table_campaign_recipients (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) NOT NULL,
            subscriber_id bigint(20) NOT NULL,
            sent_at datetime DEFAULT NULL,
            opened_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY subscriber_id (subscriber_id)
        ) $charset_collate;";

        $table_segments = $wpdb->prefix . 'lattice_mail_segments';
        $table_subscriber_segments = $wpdb->prefix . 'lattice_mail_subscriber_segments';

        $sql_segments = "CREATE TABLE $table_segments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description varchar(500) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        $sql_subscriber_segments = "CREATE TABLE $table_subscriber_segments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            segment_id bigint(20) NOT NULL,
            subscriber_id bigint(20) NOT NULL,
            added_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY segment_subscriber (segment_id, subscriber_id),
            KEY segment_id (segment_id),
            KEY subscriber_id (subscriber_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_subscribers);
        dbDelta($sql_campaigns);
        dbDelta($sql_recipients);
        dbDelta($sql_segments);
        dbDelta($sql_subscriber_segments);

        // Migrate: add segment_id to campaigns if missing
        $existing_cols = $wpdb->get_col("DESCRIBE $table_campaigns", 0);
        if (!in_array('segment_id', $existing_cols)) {
            $wpdb->query("ALTER TABLE $table_campaigns ADD COLUMN segment_id bigint(20) DEFAULT 0 AFTER status");
        }

        update_option('lattice_mail_version', LATTICE_MAIL_VERSION);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function register_api_routes() {
        Lattice_Mail_API_Subscribers::register_routes();
        Lattice_Mail_API_Campaigns::register_routes();
    }

    public function ajax_subscribe() {
        check_ajax_referer('lattice_mail_subscribe', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => __('Invalid email address.', 'lattice-mail')]);
        }

        $subscriber = Lattice_Mail_Subscriber::get_instance();
        $result = $subscriber->add($email, $name);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('Successfully subscribed!', 'lattice-mail')]);
    }

    public function handle_url_actions() {
        if (!isset($_GET['lattice_mail_action'])) {
            return;
        }

        $action = sanitize_key($_GET['lattice_mail_action']);
        $token = sanitize_text_field($_GET['token'] ?? '');

        if ($action === 'confirm' && !empty($token)) {
            $subscriber = Lattice_Mail_Subscriber::get_instance();
            $result = $subscriber->confirm($token);

            if (!is_wp_error($result)) {
                wp_redirect(home_url('?lattice_mail_confirmed=1'));
            } else {
                wp_redirect(home_url('?lattice_mail_error=1'));
            }
            exit;
        }

        if ($action === 'unsubscribe' && !empty($token)) {
            $subscriber = Lattice_Mail_Subscriber::get_instance();
            $result = $subscriber->unsubscribe($token);

            if (!is_wp_error($result)) {
                wp_redirect(home_url('?lattice_mail_unsubscribed=1'));
            } else {
                wp_redirect(home_url('?lattice_mail_error=1'));
            }
            exit;
        }
    }

    public function notify_subscribers_on_publish($new_status, $old_status, $post) {
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }

        if (!in_array($post->post_type, ['post', 'page'])) {
            return;
        }

        $campaign = Lattice_Mail_Campaign::get_instance();
        $campaign->create_from_post($post);
    }
}

function Lattice_Mail() {
    return Lattice_Mail::instance();
}

Lattice_Mail();
