<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_Campaign {

    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->table = $GLOBALS['wpdb']->prefix . 'lattice_mail_campaigns';
        $this->table_recipients = $GLOBALS['wpdb']->prefix . 'lattice_mail_campaign_recipients';
    }

    public function create($subject, $content, $status = 'draft') {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table,
            [
                'subject' => $subject,
                'content' => $content,
                'status' => $status,
                'created_at' => current_time('mysql'),
            ]
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create campaign.', 'lattice-mail'));
        }

        return $wpdb->insert_id;
    }

    public function update($id, $subject, $content) {
        global $wpdb;

        $wpdb->update(
            $this->table,
            [
                'subject' => $subject,
                'content' => $content,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id]
        );

        return true;
    }

    public function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        ));
    }

    public function get_all($status = null) {
        global $wpdb;
        $sql = "SELECT * FROM {$this->table}";
        if ($status) {
            $sql .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        $sql .= " ORDER BY created_at DESC";
        return $wpdb->get_results($sql);
    }

    public function delete($id) {
        global $wpdb;
        $wpdb->delete($this->table, ['id' => $id]);
        $wpdb->delete($this->table_recipients, ['campaign_id' => $id]);
        return true;
    }

    public function send($id) {
        global $wpdb;

        $campaign = $this->get($id);
        if (!$campaign || $campaign->status === 'sent') {
            return new WP_Error('invalid_campaign', __('Campaign not found or already sent.', 'lattice-mail'));
        }

        $subscriber = Lattice_Mail_Subscriber::get_instance();
        $subscribers = $subscriber->get_all('active');

        if (empty($subscribers)) {
            return new WP_Error('no_recipients', __('No active subscribers.', 'lattice-mail'));
        }

        $smtp = Lattice_Mail_SMTP::get_instance();
        $sent = 0;

        foreach ($subscribers as $sub) {
            $unsub_token = $subscriber->get_unsubscribe_token($sub->id);
            $unsub_url = home_url("?lattice_mail_action=unsubscribe&token={$unsub_token}");

            $email_content = $campaign->content;
            $email_content .= "\n\n---\n";
            $email_content .= sprintf('<a href="%s">Unsubscribe</a>', $unsub_url);

            $result = $smtp->send($sub->email, $campaign->subject, $email_content);

            if (!is_wp_error($result)) {
                $wpdb->insert($this->table_recipients, [
                    'campaign_id' => $id,
                    'subscriber_id' => $sub->id,
                    'sent_at' => current_time('mysql'),
                ]);
                $sent++;
            }
        }

        $wpdb->update(
            $this->table,
            ['status' => 'sent', 'sent_at' => current_time('mysql')],
            ['id' => $id]
        );

        return $sent;
    }

    public function create_from_post($post) {
        $subject = get_the_title($post);
        $content = apply_filters('the_content', $post->post_content);

        $campaign_id = $this->create($subject, $content, 'draft');

        if (!is_wp_error($campaign_id)) {
            $this->send($campaign_id);
        }

        return $campaign_id;
    }
}
