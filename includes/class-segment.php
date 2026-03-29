<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_Segment {

    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'lattice_mail_segments';
        $this->table_subscriber_segments = $wpdb->prefix . 'lattice_mail_subscriber_segments';
    }

    /**
     * Create a new segment.
     * @param string $name  Segment name
     * @param string $slug  URL-safe slug (unique)
     * @param string $description
     * @return int|WP_Error
     */
    public function create($name, $slug, $description = '') {
        global $wpdb;

        $slug = sanitize_title($slug);
        if (empty($slug)) {
            $slug = sanitize_title($name);
        }

        // Ensure unique slug
        $original_slug = $slug;
        $counter = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE slug = %s", $slug))) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        $result = $wpdb->insert(
            $this->table,
            [
                'name' => sanitize_text_field($name),
                'slug' => $slug,
                'description' => sanitize_text_field($description),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create segment.', 'lattice-mail'));
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update a segment.
     */
    public function update($id, $name, $description = '') {
        global $wpdb;

        $wpdb->update(
            $this->table,
            [
                'name' => sanitize_text_field($name),
                'description' => sanitize_text_field($description),
            ],
            ['id' => (int) $id],
            ['%s', '%s'],
            ['%d']
        );

        return true;
    }

    /**
     * Delete a segment (and detach all subscribers).
     */
    public function delete($id) {
        global $wpdb;
        $id = (int) $id;

        $wpdb->delete($this->table_subscriber_segments, ['segment_id' => $id], ['%d']);
        $wpdb->delete($this->table, ['id' => $id], ['%d']);

        return true;
    }

    /**
     * Get segment by ID.
     */
    public function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            (int) $id
        ));
    }

    /**
     * Get segment by slug.
     */
    public function get_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE slug = %s",
            sanitize_title($slug)
        ));
    }

    /**
     * Get all segments.
     */
    public function get_all() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT s.*, COUNT(ss.subscriber_id) as subscriber_count
             FROM {$this->table} s
             LEFT JOIN {$this->table_subscriber_segments} ss ON s.id = ss.segment_id
             LEFT JOIN {$wpdb->prefix}lattice_mail_subscribers sub ON ss.subscriber_id = sub.id AND sub.status = 'active'
             GROUP BY s.id
             ORDER BY s.name ASC"
        );
    }

    /**
     * Add a subscriber to a segment.
     */
    public function add_subscriber($segment_id, $subscriber_id) {
        global $wpdb;
        $segment_id = (int) $segment_id;
        $subscriber_id = (int) $subscriber_id;

        // Check if already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_subscriber_segments} WHERE segment_id = %d AND subscriber_id = %d",
            $segment_id, $subscriber_id
        ));

        if ($exists) {
            return true;
        }

        $wpdb->insert(
            $this->table_subscriber_segments,
            [
                'segment_id' => $segment_id,
                'subscriber_id' => $subscriber_id,
                'added_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );

        return true;
    }

    /**
     * Remove a subscriber from a segment.
     */
    public function remove_subscriber($segment_id, $subscriber_id) {
        global $wpdb;
        $wpdb->delete(
            $this->table_subscriber_segments,
            [
                'segment_id' => (int) $segment_id,
                'subscriber_id' => (int) $subscriber_id,
            ],
            ['%d', '%d']
        );
        return true;
    }

    /**
     * Get all subscriber IDs in a segment.
     */
    public function get_subscriber_ids($segment_id) {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare(
            "SELECT ss.subscriber_id FROM {$this->table_subscriber_segments} ss
             INNER JOIN {$wpdb->prefix}lattice_mail_subscribers sub ON ss.subscriber_id = sub.id
             WHERE ss.segment_id = %d AND sub.status = 'active'",
            (int) $segment_id
        ));
    }

    /**
     * Get subscribers in a segment (with optional pagination).
     */
    public function get_subscribers($segment_id, $status = 'active') {
        global $wpdb;

        $sql = "SELECT sub.* FROM {$this->table_subscriber_segments} ss
                INNER JOIN {$wpdb->prefix}lattice_mail_subscribers sub ON ss.subscriber_id = sub.id
                WHERE ss.segment_id = %d";

        if ($status) {
            $sql .= $wpdb->prepare(" AND sub.status = %s", $status);
        }

        $sql .= " ORDER BY ss.added_at DESC";

        return $wpdb->get_results($wpdb->prepare($sql, (int) $segment_id));
    }

    /**
     * Get segments for a subscriber.
     */
    public function get_for_subscriber($subscriber_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM {$this->table} s
             INNER JOIN {$this->table_subscriber_segments} ss ON s.id = ss.segment_id
             WHERE ss.subscriber_id = %d
             ORDER BY s.name ASC",
            (int) $subscriber_id
        ));
    }

    /**
     * Bulk add subscribers to a segment by email list.
     * @param int   $segment_id
     * @param array $emails
     * @return int Number of subscribers added
     */
    public function bulk_add_by_email($segment_id, $emails) {
        global $wpdb;
        $added = 0;

        foreach ((array) $emails as $email) {
            $email = trim(sanitize_email($email));
            if (!is_email($email)) {
                continue;
            }

            $subscriber = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}lattice_mail_subscribers WHERE email = %s AND status = 'active'",
                $email
            ));

            if ($subscriber) {
                $this->add_subscriber($segment_id, $subscriber->id);
                $added++;
            }
        }

        return $added;
    }

    /**
     * Get subscriber count for a segment.
     */
    public function count_subscribers($segment_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(ss.subscriber_id) FROM {$this->table_subscriber_segments} ss
             INNER JOIN {$wpdb->prefix}lattice_mail_subscribers sub ON ss.subscriber_id = sub.id
             WHERE ss.segment_id = %d AND sub.status = 'active'",
            (int) $segment_id
        ));
    }
}
