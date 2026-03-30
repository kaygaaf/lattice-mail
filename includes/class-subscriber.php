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

    public function add($email, $name = '', $source = 'form', $status = 'pending') {
        global $wpdb;

        if ($this->exists($email)) {
            // If existing subscriber is unsubscribed, reactivate them (still needs confirm if double opt-in).
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id, status FROM {$this->table} WHERE email = %s",
                $email
            ));
            if ($existing && $existing->status === 'unsubscribed') {
                if ($status === 'active') {
                    $wpdb->update($this->table, ['status' => 'active', 'updated_at' => current_time('mysql')], ['id' => $existing->id]);
                    return $existing->id;
                }
                // For pending re-confirmation, delete old and re-add.
                $wpdb->delete($this->table, ['id' => $existing->id]);
            } else {
                return new WP_Error('already_exists', __('This email is already subscribed.', 'lattice-mail'));
            }
        }

        $confirm_token = wp_generate_uuid4();

        $result = $wpdb->insert(
            $this->table,
            [
                'email' => $email,
                'name' => $name,
                'source' => $source,
                'status' => $status,
                'created_at' => current_time('mysql'),
            ]
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to add subscriber.', 'lattice-mail'));
        }

        $subscriber_id = $wpdb->insert_id;

        if ($status === 'pending') {
            update_option("lattice_mail_confirm_{$subscriber_id}", $confirm_token, false);
            $this->send_confirmation_email($subscriber_id, $email, $name, $confirm_token);
        }

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

        // Trigger auto-responders
        if (class_exists('Lattice_Mail_Auto_Responder')) {
            Lattice_Mail_Auto_Responder::get_instance()->enqueue_subscriber($subscriber_id);
        }

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

    /**
     * Import subscribers from CSV file.
     *
     * @param string $file_path Path to CSV file.
     * @param bool $send_confirmation Send confirmation email (default: false for imported).
     * @param bool $skip_duplicates Skip emails that already exist (default: true).
     *
     * @return array ['added' => int, 'skipped' => int, 'errors' => int, 'details' => []]
     */
    public function import_from_csv($file_path, $send_confirmation = false, $skip_duplicates = true) {
        global $wpdb;

        $result = ['added' => 0, 'skipped' => 0, 'errors' => 0, 'details' => []];
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $result['details'][] = ['status' => 'error', 'message' => __('Cannot open CSV file.', 'lattice-mail')];
            return $result;
        }

        // Skip BOM if present
        $bom = fgets($handle);
        if (substr($bom, 0, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            $result['details'][] = ['status' => 'error', 'message' => __('CSV file is empty or invalid.', 'lattice-mail')];
            return $result;
        }

        // Normalize headers: find email and name columns
        $email_col = null;
        $name_col = null;
        foreach ($headers as $i => $h) {
            $h_lower = strtolower(trim($h));
            if (in_array($h_lower, ['email', 'e-mail', 'mail', 'subscriber', 'subscriber email'])) {
                $email_col = $i;
            }
            if (in_array($h_lower, ['name', 'voornaam', 'full name', 'fullname', 'first name', 'firstname'])) {
                $name_col = $i;
            }
        }

        if ($email_col === null) {
            fclose($handle);
            $result['details'][] = ['status' => 'error', 'message' => __('Could not find email column in CSV. Expected headers: email, name.', 'lattice-mail')];
            return $result;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $email = isset($row[$email_col]) ? sanitize_email(trim($row[$email_col])) : '';
            $name = ($name_col !== null && isset($row[$name_col])) ? sanitize_text_field(trim($row[$name_col])) : '';

            if (empty($email) || !is_email($email)) {
                $result['errors']++;
                $result['details'][] = ['status' => 'error', 'email' => $email, 'message' => __('Invalid email address.', 'lattice-mail')];
                continue;
            }

            $exists = $this->exists($email);
            if ($exists) {
                if ($skip_duplicates) {
                    $result['skipped']++;
                    $result['details'][] = ['status' => 'skipped', 'email' => $email, 'message' => __('Already subscribed.', 'lattice-mail')];
                    continue;
                } else {
                    // Update existing subscriber status to active if unsubscribed
                    $existing = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, status FROM {$this->table} WHERE email = %s",
                        $email
                    ));
                    if ($existing && $existing->status === 'unsubscribed') {
                        $wpdb->update(
                            $this->table,
                            ['status' => 'active', 'updated_at' => current_time('mysql')],
                            ['id' => $existing->id]
                        );
                        $result['added']++;
                        $result['details'][] = ['status' => 'reactivated', 'email' => $email, 'message' => __('Reactivated.', 'lattice-mail')];
                    } else {
                        $result['skipped']++;
                        $result['details'][] = ['status' => 'skipped', 'email' => $email, 'message' => __('Already subscribed.', 'lattice-mail')];
                    }
                    continue;
                }
            }

            $confirm_token = wp_generate_uuid4();

            $insert_result = $wpdb->insert(
                $this->table,
                [
                    'email' => $email,
                    'name' => $name,
                    'source' => 'import',
                    'status' => $send_confirmation ? 'pending' : 'active',
                    'confirmed_at' => $send_confirmation ? null : current_time('mysql'),
                    'created_at' => current_time('mysql'),
                ]
            );

            if ($insert_result === false) {
                $result['errors']++;
                $result['details'][] = ['status' => 'error', 'email' => $email, 'message' => __('Database error.', 'lattice-mail')];
                continue;
            }

            $subscriber_id = $wpdb->insert_id;

            if ($send_confirmation) {
                update_option("lattice_mail_confirm_{$subscriber_id}", $confirm_token, false);
                $this->send_confirmation_email($subscriber_id, $email, $name, $confirm_token);
            }

            $result['added']++;
            $result['details'][] = ['status' => 'added', 'email' => $email, 'name' => $name, 'message' => $send_confirmation ? __('Added, confirmation sent.', 'lattice-mail') : __('Added.', 'lattice-mail')];
        }

        fclose($handle);
        return $result;
    }

    /**
     * Export subscribers to CSV and optionally download.
     *
     * @param string|null $status Filter by status (active, pending, unsubscribed). Null = all.
     * @param bool $download If true, sends file download. If false, returns file path.
     *
     * @return string|WP_Error File path or WP_Error on failure.
     */
    public function export_to_csv($status = null, $download = true) {
        global $wpdb;

        $subscribers = $this->get_all($status);

        $upload_dir = wp_upload_dir();
        $file_name = 'lattice-mail-subscribers-' . date('Y-m-d-His') . '.csv';
        $file_path = $upload_dir['path'] . '/' . $file_name;

        $handle = fopen($file_path, 'w');
        if (!$handle) {
            return new WP_Error('file_error', __('Cannot create export file.', 'lattice-mail'));
        }

        // UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // Header row
        fputcsv($handle, ['email', 'name', 'status', 'source', 'created_at', 'confirmed_at']);

        foreach ($subscribers as $s) {
            fputcsv($handle, [
                $s->email,
                $s->name,
                $s->status,
                $s->source,
                $s->created_at,
                $s->confirmed_at ?: '',
            ]);
        }

        fclose($handle);

        if ($download) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: no-store, no-cache');

            readfile($file_path);
            unlink($file_path); // Clean up after download
            exit;
        }

        return $file_path;
    }
}
