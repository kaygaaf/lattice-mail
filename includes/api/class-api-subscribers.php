<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_API_Subscribers {

    public static function register_routes() {
        register_rest_route('lattice-mail/v1', '/subscribers', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_subscribers'],
            'permission_callback' => [self::class, 'admin_permission'],
        ]);

        register_rest_route('lattice-mail/v1', '/subscribers', [
            'methods' => 'POST',
            'callback' => [self::class, 'add_subscriber'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('lattice-mail/v1', '/subscribers/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_subscriber'],
            'permission_callback' => [self::class, 'admin_permission'],
        ]);

        register_rest_route('lattice-mail/v1', '/subscribers/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [self::class, 'delete_subscriber'],
            'permission_callback' => [self::class, 'admin_permission'],
        ]);
    }

    public static function admin_permission() {
        return current_user_can('manage_options');
    }

    public static function get_subscribers($request) {
        $subscriber = Lattice_Mail_Subscriber::get_instance();
        $status = $request->get_param('status');
        $subscribers = $subscriber->get_all($status);

        return rest_ensure_response($subscribers);
    }

    public static function add_subscriber($request) {
        $subscriber = Lattice_Mail_Subscriber::get_instance();

        $email = $request->get_param('email');
        $name = $request->get_param('name');

        if (empty($email) || !is_email($email)) {
            return new WP_Error('invalid_email', __('Invalid email address.', 'lattice-mail'), ['status' => 400]);
        }

        $result = $subscriber->add($email, $name);

        if (is_wp_error($result)) {
            return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => 400]);
        }

        return rest_ensure_response(['id' => $result, 'message' => __('Subscriber added.', 'lattice-mail')], 201);
    }

    public static function get_subscriber($request) {
        $subscriber = Lattice_Mail_Subscriber::get_instance();
        $data = $subscriber->get_by_id($request['id']);

        if (!$data) {
            return new WP_Error('not_found', __('Subscriber not found.', 'lattice-mail'), ['status' => 404]);
        }

        return rest_ensure_response($data);
    }

    public static function delete_subscriber($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'lattice_mail_subscribers';

        $wpdb->delete($table, ['id' => $request['id']]);

        return rest_ensure_response(['message' => __('Subscriber deleted.', 'lattice-mail')]);
    }
}
