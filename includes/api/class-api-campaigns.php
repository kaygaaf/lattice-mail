<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_API_Campaigns {

    public static function register_routes() {
        register_rest_route('lattice-mail/v1', '/campaigns', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_campaigns'],
            'permission_callback' => [self::class, 'admin_permission'],
        ]);

        register_rest_route('lattice-mail/v1', '/campaigns', [
            'methods' => 'POST',
            'callback' => [self::class, 'create_campaign'],
            'permission_callback' => [self::class, 'admin_permission'],
        ]);

        register_rest_route('lattice-mail/v1', '/campaigns/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_campaign'],
            'permission_callback' => [self::class, 'admin_permission'],
        ]);

        register_rest_route('lattice-mail/v1', '/campaigns/(?P<id>\d+)/send', [
            'methods' => 'POST',
            'callback' => [self::class, 'send_campaign'],
            'permission_callback' => [self::class, 'admin_permission'],
        ]);

        register_rest_route('lattice-mail/v1', '/campaigns/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [self::class, 'delete_campaign'],
            'permission_callback' => [self::class, 'admin_permission'],
        ]);
    }

    public static function admin_permission() {
        return current_user_can('manage_options');
    }

    public static function get_campaigns($request) {
        $campaign = Lattice_Mail_Campaign::get_instance();
        $status = $request->get_param('status');
        $campaigns = $campaign->get_all($status);

        return rest_ensure_response($campaigns);
    }

    public static function create_campaign($request) {
        $campaign = Lattice_Mail_Campaign::get_instance();

        $subject = $request->get_param('subject');
        $content = $request->get_param('content');

        if (empty($subject) || empty($content)) {
            return new WP_Error('missing_fields', __('Subject and content are required.', 'lattice-mail'), ['status' => 400]);
        }

        $id = $campaign->create($subject, $content);

        return rest_ensure_response(['id' => $id, 'message' => __('Campaign created.', 'lattice-mail')], 201);
    }

    public static function get_campaign($request) {
        $campaign = Lattice_Mail_Campaign::get_instance();
        $data = $campaign->get($request['id']);

        if (!$data) {
            return new WP_Error('not_found', __('Campaign not found.', 'lattice-mail'), ['status' => 404]);
        }

        return rest_ensure_response($data);
    }

    public static function send_campaign($request) {
        $campaign = Lattice_Mail_Campaign::get_instance();
        $result = $campaign->send($request['id']);

        if (is_wp_error($result)) {
            return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => 400]);
        }

        return rest_ensure_response(['sent' => $result, 'message' => sprintf(__('%d emails sent.', 'lattice-mail'), $result)]);
    }

    public static function delete_campaign($request) {
        $campaign = Lattice_Mail_Campaign::get_instance();
        $campaign->delete($request['id']);

        return rest_ensure_response(['message' => __('Campaign deleted.', 'lattice-mail')]);
    }
}
