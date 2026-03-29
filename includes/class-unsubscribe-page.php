<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_Unsubscribe_Page {

    public function __construct() {
        add_action('template_redirect', [$this, 'handle_unsubscribe_page']);
        add_filter('the_content', [$this, 'add_unsubscribe_content']);
    }

    public function handle_unsubscribe_page() {
        if (!isset($_GET['lattice_mail_confirmed'])) {
            return;
        }

        add_filter('the_content', [$this, 'show_confirmed_message']);
    }

    public function show_confirmed_message($content) {
        if (!isset($_GET['lattice_mail_confirmed'])) {
            return $content;
        }

        return '<div class="lattice-mail-message success">' .
            '<h2>' . __('Subscription Confirmed!', 'lattice-mail') . '</h2>' .
            '<p>' . __('Thank you for confirming your subscription.', 'lattice-mail') . '</p>' .
            '</div>';
    }

    public function add_unsubscribe_content($content) {
        global $post;

        if (!is_page('unsubscribe')) {
            return $content;
        }

        if (isset($_GET['lattice_mail_unsubscribed'])) {
            return '<div class="lattice-mail-message success">' .
                '<h2>' . __('Unsubscribed', 'lattice-mail') . '</h2>' .
                '<p>' . __('You have been successfully unsubscribed.', 'lattice-mail') . '</p>' .
                '</div>';
        }

        if (isset($_GET['lattice_mail_error'])) {
            return '<div class="lattice-mail-message error">' .
                '<h2>' . __('Error', 'lattice-mail') . '</h2>' .
                '<p>' . __('An error occurred. Please try again.', 'lattice-mail') . '</p>' .
                '</div>';
        }

        if (isset($_GET['token']) && isset($_GET['lattice_mail_action']) && $_GET['lattice_mail_action'] === 'unsubscribe') {
            return '<p>' . __('Processing your unsubscribe request...', 'lattice-mail') . '</p>';
        }

        return $content;
    }

    public static function create_unsubscribe_page() {
        $page = get_page_by_path('unsubscribe');

        if ($page) {
            return $page->ID;
        }

        $id = wp_insert_post([
            'post_type' => 'page',
            'post_title' => __('Unsubscribe', 'lattice-mail'),
            'post_name' => 'unsubscribe',
            'post_status' => 'publish',
            'post_content' => '',
        ]);

        return $id;
    }
}

new Lattice_Mail_Unsubscribe_Page();
