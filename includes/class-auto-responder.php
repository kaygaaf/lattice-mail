<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_Auto_Responder {

    private $table;
    private $table_queue;
    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'lattice_mail_auto_responders';
        $this->table_queue = $wpdb->prefix . 'lattice_mail_responder_queue';
    }

    /**
     * Create a new auto-responder sequence.
     *
     * @param array $data {
     *   @type string  $title          Sequence name/title
     *   @type string  $trigger_type   'welcome' | 'drip'
     *   @type int     $delay_days     Days after trigger to send (0 = immediate for welcome)
     *   @type string  $subject        Email subject
     *   @type string  $content        Email content (HTML)
     *   @type int      $segment_id     (optional) Only for subscribers in this segment
     *   @type string  $status         'active' | 'paused'
     * }
     * @return int|WP_Error
     */
    public function create($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table,
            [
                'title' => sanitize_text_field($data['title']),
                'trigger_type' => sanitize_key($data['trigger_type']),
                'delay_days' => (int) ($data['delay_days'] ?? 0),
                'subject' => sanitize_text_field($data['subject']),
                'content' => wp_kses_post($data['content']),
                'segment_id' => !empty($data['segment_id']) ? (int) $data['segment_id'] : 0,
                'status' => sanitize_key($data['status'] ?? 'active'),
                'created_at' => current_time('mysql'),
            ]
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create auto-responder.', 'lattice-mail'));
        }

        return $wpdb->insert_id;
    }

    public function update($id, $data) {
        global $wpdb;

        $update = [];
        if (isset($data['title']))      $update['title'] = sanitize_text_field($data['title']);
        if (isset($data['subject']))     $update['subject'] = sanitize_text_field($data['subject']);
        if (isset($data['content']))     $update['content'] = wp_kses_post($data['content']);
        if (isset($data['delay_days']))  $update['delay_days'] = (int) $data['delay_days'];
        if (isset($data['segment_id']))  $update['segment_id'] = (int) $data['segment_id'];
        if (isset($data['status']))      $update['status'] = sanitize_key($data['status']);

        $update['updated_at'] = current_time('mysql');

        $wpdb->update($this->table, $update, ['id' => $id]);
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
        if ($status) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE status = %s ORDER BY created_at DESC",
                $status
            ));
        }
        return $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY created_at DESC");
    }

    public function delete($id) {
        global $wpdb;
        $wpdb->delete($this->table, ['id' => $id]);
        $wpdb->delete($this->table_queue, ['responder_id' => $id]);
        return true;
    }

    /**
     * Enqueue a subscriber into all matching active auto-responders.
     * Called when a subscriber is confirmed.
     *
     * @param int $subscriber_id
     */
    public function enqueue_subscriber($subscriber_id) {
        global $wpdb;

        $subscriber = Lattice_Mail_Subscriber::get_instance();
        $sub = $subscriber->get_by_id($subscriber_id);
        if (!$sub || $sub->status !== 'active') {
            return;
        }

        // Get all active auto-responders
        $responders = $this->get_all('active');

        foreach ($responders as $ar) {
            $this->enqueue_single($ar, $subscriber_id);
        }
    }

    /**
     * Enqueue a subscriber for a single auto-responder.
     */
    public function enqueue_single($responder, $subscriber_id) {
        global $wpdb;

        // Skip if segment is set and subscriber not in it
        if (!empty($responder->segment_id)) {
            $segment = Lattice_Mail_Segment::get_instance();
            if (!$segment->subscriber_in_segment($responder->segment_id, $subscriber_id)) {
                return;
            }
        }

        // Skip if already queued for this responder
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_queue} WHERE responder_id = %d AND subscriber_id = %d AND sent_at IS NULL",
            $responder->id,
            $subscriber_id
        ));
        if ($existing > 0) {
            return;
        }

        $scheduled_at = date('Y-m-d H:i:s', strtotime("+{$responder->delay_days} days"));

        $wpdb->insert($this->table_queue, [
            'responder_id' => $responder->id,
            'subscriber_id' => $subscriber_id,
            'scheduled_at' => $scheduled_at,
            'created_at' => current_time('mysql'),
        ]);
    }

    /**
     * Process the queue — send all emails whose scheduled time has passed.
     * Called by WP Cron.
     */
    public function process_queue() {
        global $wpdb;

        $now = current_time('mysql');

        $queue = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, r.subject, r.content, r.segment_id
             FROM {$this->table_queue} q
             JOIN {$this->table} r ON r.id = q.responder_id
             WHERE q.scheduled_at <= %s AND q.sent_at IS NULL AND r.status = 'active'
             ORDER BY q.scheduled_at ASC
             LIMIT 50",
            $now
        ));

        if (empty($queue)) {
            return;
        }

        $smtp = Lattice_Mail_SMTP::get_instance();
        $subscriber = Lattice_Mail_Subscriber::get_instance();

        foreach ($queue as $item) {
            $sub = $subscriber->get_by_id($item->subscriber_id);
            if (!$sub || $sub->status !== 'active') {
                $wpdb->update($this->table_queue, ['sent_at' => current_time('mysql')], ['id' => $item->id]);
                continue;
            }

            $unsub_token = $subscriber->get_unsubscribe_token($sub->id);
            $unsub_url = home_url("?lattice_mail_action=unsubscribe&token={$unsub_token}");

            $email_content = $item->content;
            $email_content .= "\n\n---\n";
            $email_content .= sprintf('<a href="%s">Unsubscribe</a>', $unsub_url);

            $result = $smtp->send($sub->email, $item->subject, $email_content);

            if (!is_wp_error($result)) {
                $wpdb->update($this->table_queue, ['sent_at' => current_time('mysql')], ['id' => $item->id]);
            }
        }
    }

    /**
     * Count queued (pending) emails for a responder.
     */
    public function count_queue($responder_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_queue} WHERE responder_id = %d AND sent_at IS NULL",
            $responder_id
        ));
    }

    /**
     * Count sent emails for a responder.
     */
    public function count_sent($responder_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_queue} WHERE responder_id = %d AND sent_at IS NOT NULL",
            $responder_id
        ));
    }

    /**
     * Pause an auto-responder and cancel all pending queue items.
     */
    public function pause($id) {
        global $wpdb;
        $wpdb->update($this->table, ['status' => 'paused'], ['id' => $id]);
        $wpdb->delete($this->table_queue, ['responder_id' => $id, 'sent_at' => null]);
    }

    /**
     * Activate a paused auto-responder.
     */
    public function activate($id) {
        global $wpdb;
        $wpdb->update($this->table, ['status' => 'active'], ['id' => $id]);
    }
}
