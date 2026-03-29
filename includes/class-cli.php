<?php

if (!defined('ABSPATH')) {
    exit;
}

if (defined('WP_CLI') && WP_CLI) {
    class Lattice_Mail_CLI {

        public function list_subscribers($args, $assoc_args) {
            $subscriber = Lattice_Mail_Subscriber::get_instance();
            $status = $assoc_args['status'] ?? null;
            $subscribers = $subscriber->get_all($status);

            if (empty($subscribers)) {
                WP_CLI::line('No subscribers found.');
                return;
            }

            $rows = [];
            foreach ($subscribers as $s) {
                $rows[] = [$s->id, $s->email, $s->name, $s->status, $s->created_at];
            }

            WP_CLI::table($rows, ['ID', 'Email', 'Name', 'Status', 'Created']);
        }

        public function add_subscriber($args, $assoc_args) {
            $email = $args[0] ?? '';

            if (empty($email) || !is_email($email)) {
                WP_CLI::error('Valid email address required.');
            }

            $name = $assoc_args['name'] ?? '';

            $subscriber = Lattice_Mail_Subscriber::get_instance();
            $result = $subscriber->add($email, $name);

            if (is_wp_error($result)) {
                WP_CLI::error($result->get_error_message());
            }

            WP_CLI::success("Subscriber added: {$email}");
        }

        public function list_campaigns($args, $assoc_args) {
            $campaign = Lattice_Mail_Campaign::get_instance();
            $campaigns = $campaign->get_all();

            if (empty($campaigns)) {
                WP_CLI::line('No campaigns found.');
                return;
            }

            $rows = [];
            foreach ($campaigns as $c) {
                $rows[] = [$c->id, $c->subject, $c->status, $c->created_at, $c->sent_at ?: '—'];
            }

            WP_CLI::table($rows, ['ID', 'Subject', 'Status', 'Created', 'Sent']);
        }

        public function send_campaign($args) {
            $id = (int) $args[0];

            $campaign = Lattice_Mail_Campaign::get_instance();
            $result = $campaign->send($id);

            if (is_wp_error($result)) {
                WP_CLI::error($result->get_error_message());
            }

            WP_CLI::success("Sent {$result} emails.");
        }

        public function create_campaign($args, $assoc_args) {
            $subject = $assoc_args['subject'] ?? '';
            $content = $assoc_args['content'] ?? '';

            if (empty($subject) || empty($content)) {
                WP_CLI::error('Subject and content are required. Use --subject="..." --content="..."');
            }

            $campaign = Lattice_Mail_Campaign::get_instance();
            $id = $campaign->create($subject, $content);

            WP_CLI::success("Campaign created with ID: {$id}");
        }
    }

    WP_CLI::add_command('lattice-mail', 'Lattice_Mail_CLI');
}
