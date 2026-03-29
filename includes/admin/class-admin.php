<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu_pages() {
        add_menu_page(
            __('Lattice Mail', 'lattice-mail'),
            __('Lattice Mail', 'lattice-mail'),
            'manage_options',
            'lattice-mail',
            [$this, 'render_dashboard'],
            'dashicons-email-alt',
            30
        );

        add_submenu_page(
            'lattice-mail',
            __('Dashboard', 'lattice-mail'),
            __('Dashboard', 'lattice-mail'),
            'manage_options',
            'lattice-mail',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'lattice-mail',
            __('Subscribers', 'lattice-mail'),
            __('Subscribers', 'lattice-mail'),
            'manage_options',
            'lattice-mail-subscribers',
            [$this, 'render_subscribers']
        );

        add_submenu_page(
            'lattice-mail',
            __('Segments', 'lattice-mail'),
            __('Segments', 'lattice-mail'),
            'manage_options',
            'lattice-mail-segments',
            [$this, 'render_segments']
        );

        add_submenu_page(
            'lattice-mail',
            __('Campaigns', 'lattice-mail'),
            __('Campaigns', 'lattice-mail'),
            'manage_options',
            'lattice-mail-campaigns',
            [$this, 'render_campaigns']
        );

        add_submenu_page(
            'lattice-mail',
            __('Settings', 'lattice-mail'),
            __('Settings', 'lattice-mail'),
            'manage_options',
            'lattice-mail-settings',
            [$this, 'render_settings']
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'lattice-mail') === false) {
            return;
        }

        wp_enqueue_style('lattice-mail-admin', LATTICE_MAIL_PLUGIN_URL . 'assets/admin.css', [], LATTICE_MAIL_VERSION);
        wp_enqueue_script('lattice-mail-admin', LATTICE_MAIL_PLUGIN_URL . 'assets/admin.js', ['jquery'], LATTICE_MAIL_VERSION, true);
    }

    public function render_dashboard() {
        $subscriber = Lattice_Mail_Subscriber::get_instance();
        $campaign = Lattice_Mail_Campaign::get_instance();

        $total_subscribers = count($subscriber->get_all());
        $active_subscribers = count($subscriber->get_all('active'));
        $total_campaigns = count($campaign->get_all());
        $sent_campaigns = count($campaign->get_all('sent'));

        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Lattice Mail Dashboard', 'lattice-mail'); ?></h1>

            <div class="lattice-mail-stats">
                <div class="stat-box">
                    <h3><?php echo esc_html($total_subscribers); ?></h3>
                    <p><?php _e('Total Subscribers', 'lattice-mail'); ?></p>
                </div>
                <div class="stat-box">
                    <h3><?php echo esc_html($active_subscribers); ?></h3>
                    <p><?php _e('Active Subscribers', 'lattice-mail'); ?></p>
                </div>
                <div class="stat-box">
                    <h3><?php echo esc_html($total_campaigns); ?></h3>
                    <p><?php _e('Total Campaigns', 'lattice-mail'); ?></p>
                </div>
                <div class="stat-box">
                    <h3><?php echo esc_html($sent_campaigns); ?></h3>
                    <p><?php _e('Sent Campaigns', 'lattice-mail'); ?></p>
                </div>
            </div>

            <h2><?php _e('Quick Actions', 'lattice-mail'); ?></h2>
            <p>
                <a href="<?php echo admin_url('admin.php?page=lattice-mail-campaigns'); ?>" class="button button-primary">
                    <?php _e('Create Campaign', 'lattice-mail'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=lattice-mail-settings'); ?>" class="button">
                    <?php _e('SMTP Settings', 'lattice-mail'); ?>
                </a>
            </p>

            <h2><?php _e('Recent Campaigns', 'lattice-mail'); ?></h2>
            <?php
            $campaigns = $campaign->get_all();
            if (empty($campaigns)): ?>
                <p><?php _e('No campaigns yet.', 'lattice-mail'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Subject', 'lattice-mail'); ?></th>
                            <th><?php _e('Status', 'lattice-mail'); ?></th>
                            <th><?php _e('Sent', 'lattice-mail'); ?></th>
                            <th><?php _e('Opens', 'lattice-mail'); ?></th>
                            <th><?php _e('Clicks', 'lattice-mail'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $wpdb;
                        $recip_table = $wpdb->prefix . 'lattice_mail_campaign_recipients';
                        foreach (array_slice($campaigns, 0, 5) as $c):
                            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$recip_table} WHERE campaign_id = %d", $c->id));
                            $opened = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$recip_table} WHERE campaign_id = %d AND opened_at IS NOT NULL", $c->id));
                            $clicked = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$recip_table} WHERE campaign_id = %d AND clicked_at IS NOT NULL", $c->id));
                        ?>
                            <tr>
                                <td><?php echo esc_html($c->subject); ?></td>
                                <td><span class="status-<?php echo esc_attr($c->status); ?>"><?php echo esc_html($c->status); ?></span></td>
                                <td><?php echo $c->sent_at ? esc_html($c->sent_at) : '—'; ?></td>
                                <td><?php echo $total > 0 ? esc_html("{$opened} / {$total}") . ' (' . round($opened / $total * 100) . '%)' : '—'; ?></td>
                                <td><?php echo $total > 0 ? esc_html("{$clicked} / {$total}") . ' (' . round($clicked / $total * 100) . '%)' : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_subscribers() {
        $subscriber = Lattice_Mail_Subscriber::get_instance();
        $segment = Lattice_Mail_Segment::get_instance();
        $subscribers = $subscriber->get_all();
        $segments = $segment->get_all();

        // Handle tag actions
        if (isset($_POST['lattice_mail_add_segment']) && wp_verify_nonce($_POST['lattice_mail_segment_nonce'], 'lattice_mail_add_segment')) {
            $subscriber_id = (int) $_POST['subscriber_id'];
            $segment_id = (int) $_POST['segment_id'];
            $segment->add_subscriber($segment_id, $subscriber_id);
            wp_redirect(add_query_arg('updated', '1', wp_get_referer()));
            exit;
        }

        if (isset($_POST['lattice_mail_remove_segment']) && wp_verify_nonce($_POST['lattice_mail_segment_nonce'], 'lattice_mail_remove_segment')) {
            $subscriber_id = (int) $_POST['subscriber_id'];
            $segment_id = (int) $_POST['segment_id'];
            $segment->remove_subscriber($segment_id, $subscriber_id);
            wp_redirect(add_query_arg('updated', '1', wp_get_referer()));
            exit;
        }

        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Subscribers', 'lattice-mail'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Email', 'lattice-mail'); ?></th>
                        <th><?php _e('Name', 'lattice-mail'); ?></th>
                        <th><?php _e('Segments', 'lattice-mail'); ?></th>
                        <th><?php _e('Status', 'lattice-mail'); ?></th>
                        <th><?php _e('Source', 'lattice-mail'); ?></th>
                        <th><?php _e('Joined', 'lattice-mail'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                        <tr><td colspan="6"><?php _e('No subscribers yet.', 'lattice-mail'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $s):
                            $sub_segments = $segment->get_for_subscriber($s->id);
                        ?>
                            <tr>
                                <td><?php echo esc_html($s->email); ?></td>
                                <td><?php echo esc_html($s->name); ?></td>
                                <td>
                                    <?php foreach ($sub_segments as $seg): ?>
                                        <span class="lattice-tag" style="background:#e8f0fe;color:#1a73e8;padding:2px 8px;border-radius:12px;margin-right:4px;display:inline-block;font-size:12px;">
                                            <?php echo esc_html($seg->name); ?>
                                            <form method="post" style="display:inline;margin-left:4px;">
                                                <input type="hidden" name="subscriber_id" value="<?php echo esc_attr($s->id); ?>">
                                                <input type="hidden" name="segment_id" value="<?php echo esc_attr($seg->id); ?>">
                                                <?php wp_nonce_field('lattice_mail_remove_segment', 'lattice_mail_segment_nonce'); ?>
                                                <button type="submit" name="lattice_mail_remove_segment" style="background:none;border:none;color:#d32f2f;cursor:pointer;font-size:12px;padding:0;line-height:1;">×</button>
                                            </form>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (!empty($segments)): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="subscriber_id" value="<?php echo esc_attr($s->id); ?>">
                                            <select name="segment_id" style="font-size:12px;padding:2px;">
                                                <option value=""><?php _e('Add tag...', 'lattice-mail'); ?></option>
                                                <?php foreach ($segments as $seg): ?>
                                                    <option value="<?php echo esc_attr($seg->id); ?>"><?php echo esc_html($seg->name); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php wp_nonce_field('lattice_mail_add_segment', 'lattice_mail_segment_nonce'); ?>
                                            <button type="submit" name="lattice_mail_add_segment" style="font-size:12px;cursor:pointer;">+</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-<?php echo esc_attr($s->status); ?>"><?php echo esc_html($s->status); ?></span></td>
                                <td><?php echo esc_html($s->source); ?></td>
                                <td><?php echo esc_html($s->created_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_segments() {
        $segment = Lattice_Mail_Segment::get_instance();
        $segments = $segment->get_all();

        // Handle create
        if (isset($_POST['lattice_mail_create_segment']) && wp_verify_nonce($_POST['lattice_mail_segment_nonce'], 'lattice_mail_create_segment')) {
            $name = sanitize_text_field($_POST['name'] ?? '');
            $slug = sanitize_title($_POST['slug'] ?? '');
            $description = sanitize_text_field($_POST['description'] ?? '');
            if (!empty($name)) {
                $segment->create($name, $slug, $description);
                wp_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=lattice-mail-segments')));
                exit;
            }
        }

        // Handle delete
        if (isset($_POST['lattice_mail_delete_segment']) && wp_verify_nonce($_POST['lattice_mail_segment_nonce'], 'lattice_mail_delete_segment')) {
            $id = (int) $_POST['segment_id'];
            $segment->delete($id);
            wp_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=lattice-mail-segments')));
            exit;
        }

        // Handle bulk add by email
        if (isset($_POST['lattice_mail_bulk_add']) && wp_verify_nonce($_POST['lattice_mail_segment_nonce'], 'lattice_mail_bulk_add')) {
            $segment_id = (int) $_POST['segment_id'];
            $emails_raw = sanitize_textarea_field($_POST['emails'] ?? '');
            $emails = array_filter(array_map('trim', explode("\n", $emails_raw)));
            $added = $segment->bulk_add_by_email($segment_id, $emails);
            echo '<div class="notice notice-success"><p>' . sprintf(esc_html(__('%d subscribers added to segment.', 'lattice-mail')), $added) . '</p></div>';
        }

        $subscribers_count = function($seg_id) use ($segment) {
            return $segment->count_subscribers($seg_id);
        };

        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Segments', 'lattice-mail'); ?></h1>

            <h2><?php _e('Create Segment', 'lattice-mail'); ?></h2>
            <form method="post" style="max-width: 600px; margin-bottom: 30px;">
                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php _e('Name', 'lattice-mail'); ?> *</label></th>
                        <td><input type="text" name="name" id="name" class="widefat" required placeholder="<?php _e('e.g. Kinderverhalen', 'lattice-mail'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="slug"><?php _e('Slug', 'lattice-mail'); ?></label></th>
                        <td><input type="text" name="slug" id="slug" class="widefat" placeholder="<?php _e('auto-generated if empty', 'lattice-mail'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php _e('Description', 'lattice-mail'); ?></label></th>
                        <td><input type="text" name="description" id="description" class="widefat"></td>
                    </tr>
                </table>
                <?php wp_nonce_field('lattice_mail_create_segment', 'lattice_mail_segment_nonce'); ?>
                <button type="submit" name="lattice_mail_create_segment" class="button button-primary"><?php _e('Create Segment', 'lattice-mail'); ?></button>
            </form>

            <h2><?php _e('All Segments', 'lattice-mail'); ?></h2>
            <?php if (empty($segments)): ?>
                <p><?php _e('No segments yet. Create one above.', 'lattice-mail'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'lattice-mail'); ?></th>
                            <th><?php _e('Slug', 'lattice-mail'); ?></th>
                            <th><?php _e('Subscribers', 'lattice-mail'); ?></th>
                            <th><?php _e('Actions', 'lattice-mail'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($segments as $seg): ?>
                            <tr>
                                <td><?php echo esc_html($seg->name); ?></td>
                                <td><code><?php echo esc_html($seg->slug); ?></code></td>
                                <td><?php echo (int) $seg->subscriber_count; ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="segment_id" value="<?php echo esc_attr($seg->id); ?>">
                                        <?php wp_nonce_field('lattice_mail_delete_segment', 'lattice_mail_segment_nonce'); ?>
                                        <button type="submit" name="lattice_mail_delete_segment" class="button button-secondary" onclick="return confirm('Delete this segment?');"><?php _e('Delete', 'lattice-mail'); ?></button>
                                    </form>
                                    <button type="button" class="button" onclick="jQuery('#bulk-add-<?php echo esc_attr($seg->id); ?>').toggle()"><?php _e('Bulk add emails', 'lattice-mail'); ?></button>
                                    <div id="bulk-add-<?php echo esc_attr($seg->id); ?>" style="display:none; margin-top:10px; padding:10px; background:#f0f0f0; border:1px solid #ccc;">
                                        <form method="post">
                                            <input type="hidden" name="segment_id" value="<?php echo esc_attr($seg->id); ?>">
                                            <textarea name="emails" rows="5" class="widefat" placeholder="<?php _e('One email per line', 'lattice-mail'); ?>"></textarea>
                                            <?php wp_nonce_field('lattice_mail_bulk_add', 'lattice_mail_segment_nonce'); ?>
                                            <button type="submit" name="lattice_mail_bulk_add" class="button button-primary" style="margin-top:5px;"><?php _e('Add to segment', 'lattice-mail'); ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_campaigns() {
        $campaign = Lattice_Mail_Campaign::get_instance();
        $campaigns = $campaign->get_all();

        $segment = Lattice_Mail_Segment::get_instance();
        $segments = $segment->get_all();

        if (isset($_POST['lattice_mail_campaign_submit']) && wp_verify_nonce($_POST['lattice_mail_campaign_nonce'], 'lattice_mail_campaign')) {
            $subject = sanitize_text_field($_POST['subject'] ?? '');
            $content = wp_kses_post($_POST['content'] ?? '');
            $segment_id = (int) ($_POST['segment_id'] ?? 0);
            $campaign->create($subject, $content, 'draft', $segment_id);
            wp_redirect(admin_url('admin.php?page=lattice-mail-campaigns'));
            exit;
        }

        if (isset($_POST['lattice_mail_send_submit']) && wp_verify_nonce($_POST['lattice_mail_send_nonce'], 'lattice_mail_send')) {
            $id = (int) $_POST['campaign_id'];
            $result = $campaign->send($id);
            if (!is_wp_error($result)) {
                echo '<div class="notice notice-success"><p>' . sprintf(esc_html(__('%d emails sent.', 'lattice-mail')), $result) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            }
        }

        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Campaigns', 'lattice-mail'); ?></h1>

            <h2><?php _e('Create Campaign', 'lattice-mail'); ?></h2>
            <form method="post" style="max-width: 600px;">
                <table class="form-table">
                    <tr>
                        <th><label for="subject"><?php _e('Subject', 'lattice-mail'); ?></label></th>
                        <td><input type="text" name="subject" id="subject" class="widefat" required></td>
                    </tr>
                    <tr>
                        <th><label for="content"><?php _e('Content', 'lattice-mail'); ?></label></th>
                        <td><textarea name="content" id="content" rows="10" class="widefat"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="segment_id"><?php _e('Segment', 'lattice-mail'); ?></label></th>
                        <td>
                            <select name="segment_id" id="segment_id">
                                <option value="0"><?php _e('All subscribers', 'lattice-mail'); ?></option>
                                <?php foreach ($segments as $seg): ?>
                                    <option value="<?php echo esc_attr($seg->id); ?>">
                                        <?php echo esc_html($seg->name); ?> (<?php echo (int) $seg->subscriber_count; ?> <?php _e('subscribers', 'lattice-mail'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Leave at "All subscribers" to send to everyone.', 'lattice-mail'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php wp_nonce_field('lattice_mail_campaign', 'lattice_mail_campaign_nonce'); ?>
                <button type="submit" name="lattice_mail_campaign_submit" class="button button-primary"><?php _e('Create Draft', 'lattice-mail'); ?></button>
            </form>

            <h2><?php _e('All Campaigns', 'lattice-mail'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Subject', 'lattice-mail'); ?></th>
                        <th><?php _e('Status', 'lattice-mail'); ?></th>
                        <th><?php _e('Opens', 'lattice-mail'); ?></th>
                        <th><?php _e('Clicks', 'lattice-mail'); ?></th>
                        <th><?php _e('Actions', 'lattice-mail'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campaigns)): ?>
                        <tr><td colspan="5"><?php _e('No campaigns yet.', 'lattice-mail'); ?></td></tr>
                    <?php else: ?>
                        <?php
                        global $wpdb;
                        $recip_table = $wpdb->prefix . 'lattice_mail_campaign_recipients';
                        foreach ($campaigns as $c):
                            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$recip_table} WHERE campaign_id = %d", $c->id));
                            $opened = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$recip_table} WHERE campaign_id = %d AND opened_at IS NOT NULL", $c->id));
                            $clicked = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$recip_table} WHERE campaign_id = %d AND clicked_at IS NOT NULL", $c->id));
                        ?>
                            <tr>
                                <td><?php echo esc_html($c->subject); ?></td>
                                <td><span class="status-<?php echo esc_attr($c->status); ?>"><?php echo esc_html($c->status); ?></span></td>
                                <td><?php echo $total > 0 ? esc_html("{$opened} ({$total})") : '—'; ?></td>
                                <td><?php echo $total > 0 ? esc_html("{$clicked} ({$total})") : '—'; ?></td>
                                <td>
                                    <?php if ($c->status === 'draft'): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="campaign_id" value="<?php echo esc_attr($c->id); ?>">
                                            <?php wp_nonce_field('lattice_mail_send', 'lattice_mail_send_nonce'); ?>
                                            <button type="submit" name="lattice_mail_send_submit" class="button button-primary"><?php _e('Send', 'lattice-mail'); ?></button>
                                        </form>
                                    <?php else: ?>
                                        <?php echo esc_html($c->sent_at); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_settings() {
        $smtp = Lattice_Mail_SMTP::get_instance();
        $settings = $smtp->get_settings();

        if (isset($_POST['lattice_mail_settings_submit']) && wp_verify_nonce($_POST['lattice_mail_settings_nonce'], 'lattice_mail_settings')) {
            $new_settings = [
                'from_email' => sanitize_email($_POST['from_email'] ?? ''),
                'from_name' => sanitize_text_field($_POST['from_name'] ?? ''),
                'mailer' => sanitize_text_field($_POST['mailer'] ?? 'wp_mail'),
                'smtp_host' => sanitize_text_field($_POST['smtp_host'] ?? ''),
                'smtp_port' => (int) ($_POST['smtp_port'] ?? 587),
                'smtp_user' => sanitize_text_field($_POST['smtp_user'] ?? ''),
                'smtp_pass' => sanitize_text_field($_POST['smtp_pass'] ?? ''),
                'smtp_secure' => sanitize_text_field($_POST['smtp_secure'] ?? 'tls'),
            ];
            $smtp->save_settings($new_settings);
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'lattice-mail') . '</p></div>';
            $settings = $smtp->get_settings();
        }

        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Lattice Mail Settings', 'lattice-mail'); ?></h1>

            <form method="post" style="max-width: 600px;">
                <h2><?php _e('General', 'lattice-mail'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="from_email"><?php _e('From Email', 'lattice-mail'); ?></label></th>
                        <td><input type="email" name="from_email" id="from_email" value="<?php echo esc_attr($settings['from_email']); ?>" class="widefat"></td>
                    </tr>
                    <tr>
                        <th><label for="from_name"><?php _e('From Name', 'lattice-mail'); ?></label></th>
                        <td><input type="text" name="from_name" id="from_name" value="<?php echo esc_attr($settings['from_name']); ?>" class="widefat"></td>
                    </tr>
                </table>

                <h2><?php _e('Mailer', 'lattice-mail'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Mailer', 'lattice-mail'); ?></th>
                        <td>
                            <label><input type="radio" name="mailer" value="wp_mail" <?php checked($settings['mailer'], 'wp_mail'); ?>> WordPress default (wp_mail)</label><br>
                            <label><input type="radio" name="mailer" value="smtp" <?php checked($settings['mailer'], 'smtp'); ?>> SMTP</label>
                        </td>
                    </tr>
                </table>

                <div id="smtp-settings" style="<?php echo $settings['mailer'] === 'smtp' ? '' : 'display:none;'; ?>">
                    <h2><?php _e('SMTP Settings', 'lattice-mail'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="smtp_host"><?php _e('SMTP Host', 'lattice-mail'); ?></label></th>
                            <td><input type="text" name="smtp_host" id="smtp_host" value="<?php echo esc_attr($settings['smtp_host']); ?>" class="widefat"></td>
                        </tr>
                        <tr>
                            <th><label for="smtp_port"><?php _e('SMTP Port', 'lattice-mail'); ?></label></th>
                            <td><input type="number" name="smtp_port" id="smtp_port" value="<?php echo esc_attr($settings['smtp_port']); ?>" class="widefat"></td>
                        </tr>
                        <tr>
                            <th><label for="smtp_user"><?php _e('Username', 'lattice-mail'); ?></label></th>
                            <td><input type="text" name="smtp_user" id="smtp_user" value="<?php echo esc_attr($settings['smtp_user']); ?>" class="widefat"></td>
                        </tr>
                        <tr>
                            <th><label for="smtp_pass"><?php _e('Password', 'lattice-mail'); ?></label></th>
                            <td><input type="password" name="smtp_pass" id="smtp_pass" value="<?php echo esc_attr($settings['smtp_pass']); ?>" class="widefat"></td>
                        </tr>
                        <tr>
                            <th><?php _e('Encryption', 'lattice-mail'); ?></th>
                            <td>
                                <label><input type="radio" name="smtp_secure" value="tls" <?php checked($settings['smtp_secure'], 'tls'); ?>> TLS</label><br>
                                <label><input type="radio" name="smtp_secure" value="ssl" <?php checked($settings['smtp_secure'], 'ssl'); ?>> SSL</label>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php wp_nonce_field('lattice_mail_settings', 'lattice_mail_settings_nonce'); ?>
                <button type="submit" name="lattice_mail_settings_submit" class="button button-primary"><?php _e('Save Settings', 'lattice-mail'); ?></button>
            </form>

            <script>
                document.querySelectorAll('input[name="mailer"]').forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        document.getElementById('smtp-settings').style.display = this.value === 'smtp' ? '' : 'none';
                    });
                });
            </script>
        </div>
        <?php
    }
}

new Lattice_Mail_Admin();
