<?php

if (!defined('ABSPATH')) {
    exit;
}

#[\AllowDynamicProperties]
class Lattice_Mail_Campaign {

    private $table;
    private $table_recipients;

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

    public function create($subject, $content, $status = 'draft', $segment_id = 0) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table,
            [
                'subject' => $subject,
                'content' => $content,
                'status' => $status,
                'segment_id' => (int) $segment_id,
                'created_at' => current_time('mysql'),
            ]
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create campaign.', 'lattice-mail'));
        }

        return $wpdb->insert_id;
    }

    public function update($id, $subject, $content, $segment_id = 0) {
        global $wpdb;

        $wpdb->update(
            $this->table,
            [
                'subject' => $subject,
                'content' => $content,
                'segment_id' => (int) $segment_id,
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

        // If campaign has a segment, only get subscribers in that segment
        $segment_id = !empty($campaign->segment_id) ? (int) $campaign->segment_id : 0;
        if ($segment_id > 0) {
            $segment = Lattice_Mail_Segment::get_instance();
            $subscriber_ids = $segment->get_subscriber_ids($segment_id);
            if (empty($subscriber_ids)) {
                return new WP_Error('no_recipients', __('No subscribers in this segment.', 'lattice-mail'));
            }
            // Get full subscriber objects
            $placeholders = implode(',', array_fill(0, count($subscriber_ids), '%d'));
            $subscribers = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}lattice_mail_subscribers WHERE id IN ($placeholders) AND status = 'active'",
                    ...$subscriber_ids
                )
            );
        } else {
            $subscribers = $subscriber->get_all('active');
        }

        if (empty($subscribers)) {
            return new WP_Error('no_recipients', __('No active subscribers.', 'lattice-mail'));
        }

        $smtp = Lattice_Mail_SMTP::get_instance();
        $sent = 0;

        foreach ($subscribers as $sub) {
            $unsub_token = $subscriber->get_unsubscribe_token($sub->id);
            $unsub_url = home_url("?lattice_mail_action=unsubscribe&token={$unsub_token}");

            $email_content = $campaign->content;

            // Insert into DB first to get the recipient ID
            $recip_id = $wpdb->insert($this->table_recipients, [
                'campaign_id' => $id,
                'subscriber_id' => $sub->id,
                'sent_at' => current_time('mysql'),
            ]);
            $recipient_record_id = $wpdb->insert_id;

            // Build open tracking pixel
            $open_url = home_url("?lattice_mail_action=open&r={$recipient_record_id}&c={$id}");

            // Rewrite all links for click tracking (except mailto: and tel:)
            $email_content = preg_replace_callback(
                '/(<a\s+[^>]*href=["\'])([^"\']+)(["\'][^>]*>)/i',
                function($matches) use ($recipient_record_id, $id) {
                    $original_url = $matches[2];
                    // Skip javascript:, mailto:, tel:, and anchor links
                    if (preg_match('/^(javascript:|mailto:|tel:|#)/i', $original_url)) {
                        return $matches[0];
                    }
                    $tracked_url = home_url("?lattice_mail_action=click&r={$recipient_record_id}&c={$id}&url=" . urlencode($original_url));
                    return $matches[1] . $tracked_url . $matches[3];
                },
                $email_content
            );

            // Unsubscribe link also gets tracked
            $email_content .= "\n\n---\n";
            $email_content .= sprintf('<a href="%s">Unsubscribe</a>', $unsub_url);

            // Add open tracking pixel (1x1 transparent image, hidden)
            $email_content .= '<img src="' . esc_url($open_url) . '" width="1" height="1" alt="" style="display:none;" />';

            $result = $smtp->send($sub->email, $campaign->subject, $email_content);

            if (!is_wp_error($result)) {
                $sent++;
            } else {
                // Remove the recipient record if send failed
                $wpdb->delete($this->table_recipients, ['id' => $recipient_record_id]);
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
