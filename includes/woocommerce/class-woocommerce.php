<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_WooCommerce {

    public function __construct() {
        add_action('woocommerce_checkout_after_customer_details', [$this, 'add_subscribe_checkbox']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_subscribe_checkbox']);
        add_action('woocommerce_order_status_completed', [$this, 'subscribe_from_order']);

        add_filter('woocommerce_email_subject_customer_note', [$this, 'email_subject'], 10, 3);

        add_shortcode('lattice_mail_woocommerce_subscribe', [$this, 'shortcode']);
    }

    public function add_subscribe_checkbox() {
        $enabled = get_option('lattice_mail_woo_subscribe_enabled', 'yes');
        if ($enabled !== 'yes') {
            return;
        }

        $label = get_option('lattice_mail_woo_subscribe_label', __('Subscribe to our newsletter', 'lattice-mail'));
        $checked = get_option('lattice_mail_woo_subscribe_checked', 'no') === 'yes';
        ?>
        <div class="lattice-mail-woo-subscribe" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="lattice_mail_subscribe" value="1" <?php checked($checked, true); ?>>
                <span><?php echo esc_html($label); ?></span>
            </label>
        </div>
        <?php
    }

    public function save_subscribe_checkbox($order_id) {
        if (isset($_POST['lattice_mail_subscribe']) && $_POST['lattice_mail_subscribe'] === '1') {
            update_post_meta($order_id, '_lattice_mail_subscribed', '1');
        }
    }

    public function subscribe_from_order($order_id) {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $subscribed = get_post_meta($order_id, '_lattice_mail_subscribed', true);
        if ($subscribed !== '1') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $email = $order->get_billing_email();
        $name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());

        $subscriber = Lattice_Mail_Subscriber::get_instance();

        $double_optin = get_option('lattice_mail_woo_double_optin', 'no') === 'yes';
        $status = $double_optin ? 'pending' : 'active';
        $result = $subscriber->add($email, $name, 'woocommerce_checkout', $status);

        if (is_wp_error($result)) {
            $logger = wc_get_logger();
            $logger->error('[Lattice Mail] WooCommerce subscription failed: ' . $result->get_error_message(), ['source' => 'lattice-mail']);
        }
    }

    public function shortcode($atts = []) {
        $atts = shortcode_atts([
            'label' => __('Subscribe to our newsletter and get 10% off your first order!', 'lattice-mail'),
        ], $atts);

        ob_start();
        ?>
        <div class="lattice-mail-woo-subscribe">
            <label>
                <input type="checkbox" name="lattice_mail_subscribe" value="1">
                <span><?php echo esc_html($atts['label']); ?></span>
            </label>
        </div>
        <?php
        return ob_get_clean();
    }

    public function email_subject($subject, $object, $object2) {
        return $subject;
    }
}

new Lattice_Mail_WooCommerce();
