<?php

if (!defined('ABSPATH')) {
    exit;
}

#[\AllowDynamicProperties]
class Lattice_Mail_Subscriber {

    private $table;

    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->table = $GLOBALS['wpdb']->prefix . 'lattice_mail_subscribers';
    }

    public function add($email, $name = '', $source = 'form') {
        global $wpdb;

        if ($this->exists($email)) {
            return new WP_Error('already_exists', __('This email is already subscribed.', 'lattice-mail'));
        }

        $confirm_token = wp_generate_uuid4();

        $result = $wpdb->insert(
            $this->table,
            [
                'email' => $email,
                'name' => $name,
                'source' => $source,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            ]
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to add subscriber.', 'lattice-mail'));
        }

        $subscriber_id = $wpdb->insert_id;

        update_option("lattice_mail_confirm_{$subscriber_id}", $confirm_token, false);

        $this->send_confirmation_email($subscriber_id, $email, $name, $confirm_token);

        return $subscriber_id;
    }

    public function confirm($token) {
        global $wpdb;

        $option = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value = %s",
            $wpdb->esc_like('lattice_mail_confirm_') . '%',
            $token
        ));

        if (!$option) {
            return new WP_Error('invalid_token', __('Invalid confirmation token.', 'lattice-mail'));
        }

        $subscriber_id = str_replace('lattice_mail_confirm_', '', $option->option_name);

        $wpdb->update(
            $this->table,
            ['status' => 'active', 'confirmed_at' => current_time('mysql')],
            ['id' => $subscriber_id]
        );

        delete_option("lattice_mail_confirm_{$subscriber_id}");

        return true;
    }

    public function unsubscribe($token) {
        global $wpdb;

        $option = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value = %s",
            $wpdb->esc_like('lattice_mail_unsub_') . '%',
            $token
        ));

        if (!$option) {
            return new WP_Error('invalid_token', __('Invalid unsubscribe token.', 'lattice-mail'));
        }

        $subscriber_id = str_replace('lattice_mail_unsub_', '', $option->option_name);

        $wpdb->update(
            $this->table,
            ['status' => 'unsubscribed'],
            ['id' => $subscriber_id]
        );

        delete_option("lattice_mail_unsub_{$subscriber_id}");

        return true;
    }

    public function exists($email) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE email = %s",
            $email
        ));
        return $count > 0;
    }

    public function get_all($status = 'active') {
        global $wpdb;
        $sql = "SELECT * FROM {$this->table}";
        if ($status) {
            $sql .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        return $wpdb->get_results($sql);
    }

    public function get_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        ));
    }

    private function send_confirmation_email($subscriber_id, $email, $name, $token) {
        $confirm_url = home_url("?lattice_mail_action=confirm&token={$token}");

        $subject = __('Please confirm your subscription', 'lattice-mail');
        $message = sprintf(
            __("Hi %s,\n\nPlease confirm your subscription by clicking this link:\n\n%s\n\nThank you!", 'lattice-mail'),
            $name ?: __('there', 'lattice-mail'),
            $confirm_url
        );

        $smtp = Lattice_Mail_SMTP::get_instance();
        $smtp->send($email, $subject, $message);
    }

    public function get_unsubscribe_token($subscriber_id) {
        $token = wp_generate_uuid4();
        update_option("lattice_mail_unsub_{$subscriber_id}", $token, false);
        return $token;
    }
}
