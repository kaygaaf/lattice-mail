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
            __('Auto-Responders', 'lattice-mail'),
            __('Auto-Responders', 'lattice-mail'),
            'manage_options',
            'lattice-mail-auto-responders',
            [$this, 'render_auto_responders']
        );

        add_submenu_page(
            'lattice-mail',
            __('Settings', 'lattice-mail'),
            __('Settings', 'lattice-mail'),
            'manage_options',
            'lattice-mail-settings',
            [$this, 'render_settings']
        );

        add_submenu_page(
            'lattice-mail',
            __('Import / Export', 'lattice-mail'),
            __('Import / Export', 'lattice-mail'),
            'manage_options',
            'lattice-mail-import-export',
            [$this, 'render_import_export']
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'lattice-mail') === false) {
            return;
        }

        wp_enqueue_style('lattice-mail-admin', LATTICE_MAIL_PLUGIN_URL . 'assets/admin.css', [], LATTICE_MAIL_VERSION);
        wp_enqueue_script('lattice-mail-admin', LATTICE_MAIL_PLUGIN_URL . 'assets/admin.js', ['jquery'], LATTICE_MAIL_VERSION, true);

        // WordPress built-in editors (TinyMCE + Quicktags)
        wp_enqueue_editor();
        wp_enqueue_media();
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
        // Handle email preview render (inside iframe) — supports both GET and POST
        if (isset($_GET['lattice_mail_preview']) || isset($_POST['lattice_mail_preview'])) {
            $subject = sanitize_text_field($_POST['subject'] ?? $_GET['subject'] ?? 'Preview Subject');
            $preview_text = sanitize_text_field($_POST['preview_text'] ?? $_GET['preview_text'] ?? '');
            $content = wp_kses_post($_POST['content'] ?? $_GET['content'] ?? '');
            $template_slug = sanitize_key($_POST['template'] ?? $_GET['template'] ?? 'default');

            $email_template = Lattice_Mail_Email_Template::get_instance();
            $template_html = $email_template->get_template_by_slug($template_slug);

            $unsubscribe_url = home_url("?lattice_mail_action=unsubscribe&token=PREVIEW_TOKEN");

            $html = $email_template->render($template_html, [
                'site_name' => get_bloginfo('name'),
                'subject' => $subject,
                'preview_text' => $preview_text,
                'content' => wp_kses_post($content),
                'unsubscribe_link' => '<a href="' . esc_url($unsubscribe_url) . '">' . __('Unsubscribe', 'lattice-mail') . '</a>',
                'unsubscribe_url' => $unsubscribe_url,
            ]);

            echo $html;
            exit;
        }

        $campaign = Lattice_Mail_Campaign::get_instance();
        $campaigns = $campaign->get_all();

        $segment = Lattice_Mail_Segment::get_instance();
        $segments = $segment->get_all();

        if (isset($_POST['lattice_mail_campaign_submit']) && wp_verify_nonce($_POST['lattice_mail_campaign_nonce'], 'lattice_mail_campaign')) {
            $subject = sanitize_text_field($_POST['subject'] ?? '');
            $preview_text = sanitize_text_field($_POST['preview_text'] ?? '');
            $content = wp_kses_post($_POST['content'] ?? '');
            $segment_id = (int) ($_POST['segment_id'] ?? 0);
            $template_slug = sanitize_key($_POST['template_slug'] ?? 'default');
            $campaign->create($subject, $content, 'draft', $segment_id, $preview_text, $template_slug);
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

        $email_template = Lattice_Mail_Email_Template::get_instance();
        $templates = $email_template->get_templates();
        $default_content = '<p>' . __('Write your email content here...', 'lattice-mail') . '</p>';
        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Campaigns', 'lattice-mail'); ?></h1>

            <h2><?php _e('Create Campaign', 'lattice-mail'); ?></h2>
            <form method="post" id="lattice-mail-campaign-form">
                <div class="lattice-campaign-editor">
                    <div class="lattice-editor-main">
                        <table class="form-table">
                            <tr>
                                <th><label for="subject"><?php _e('Subject Line', 'lattice-mail'); ?> *</label></th>
                                <td>
                                    <input type="text" name="subject" id="subject" class="widefat" required placeholder="<?php _e('e.g. Nieuwsbrief Maart 2026', 'lattice-mail'); ?>">
                                    <p class="description"><?php _e('The email subject line that recipients will see.', 'lattice-mail'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="preview_text"><?php _e('Preview Text', 'lattice-mail'); ?></label></th>
                                <td>
                                    <input type="text" name="preview_text" id="preview_text" class="widefat" maxlength="500" placeholder="<?php _e('Short preview text shown in inbox (recommended: 40-90 chars)', 'lattice-mail'); ?>">
                                    <p class="description"><?php _e('Shown after the subject in most email clients. Keep it under 90 characters.', 'lattice-mail'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="lattice-mail-editor"><?php _e('Email Content', 'lattice-mail'); ?></label></th>
                                <td>
                                    <?php
                                    wp_editor(
                                        $default_content,
                                        'lattice-mail-editor',
                                        [
                                            'textarea_name' => 'content',
                                            'media_buttons' => true,
                                            'textarea_rows' => 15,
                                            'teeny' => false,
                                            'quicktags' => true,
                                        ]
                                    );
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="lattice-editor-sidebar">
                        <div class="lattice-editor-sidebar-box">
                            <h3><?php _e('Template', 'lattice-mail'); ?></h3>
                            <p class="description" style="margin-bottom:10px;"><?php _e('Choose an email template style. Your content will be wrapped in the selected template.', 'lattice-mail'); ?></p>
                            <select name="template_slug" id="template_slug" style="width:100%;">
                                <?php foreach ($templates as $t): ?>
                                    <option value="<?php echo esc_attr($t['slug']); ?>"><?php echo esc_html($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lattice-editor-sidebar-box">
                            <h3><?php _e('Segment', 'lattice-mail'); ?></h3>
                            <select name="segment_id" id="segment_id" style="width:100%;">
                                <option value="0"><?php _e('All subscribers', 'lattice-mail'); ?></option>
                                <?php foreach ($segments as $seg): ?>
                                    <option value="<?php echo esc_attr($seg->id); ?>">
                                        <?php echo esc_html($seg->name); ?> (<?php echo (int) $seg->subscriber_count; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lattice-editor-sidebar-box">
                            <h3><?php _e('Preview', 'lattice-mail'); ?></h3>
                            <button type="button" id="lattice-mail-preview-btn" class="button button-secondary" style="width:100%;">
                                <?php _e('Open Email Preview', 'lattice-mail'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <?php wp_nonce_field('lattice_mail_campaign', 'lattice_mail_campaign_nonce'); ?>
                <p style="margin-top:20px;">
                    <button type="submit" name="lattice_mail_campaign_submit" class="button button-primary button-hero">
                        <?php _e('Create Campaign Draft', 'lattice-mail'); ?>
                    </button>
                </p>
            </form>
        </div>

        <!-- Preview Modal -->
        <div id="lattice-mail-preview-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:100000; background:rgba(0,0,0,0.7);">
            <div style="background:#fff; max-width:700px; max-height:90vh; margin:40px auto; overflow:auto; border-radius:8px; position:relative;">
                <div style="padding:20px; background:#f0f0f0; border-bottom:1px solid #ddd; display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="margin:0; font-size:18px;"><?php _e('Email Preview', 'lattice-mail'); ?></h2>
                    <button type="button" id="lattice-mail-preview-close" style="background:none; border:none; font-size:24px; cursor:pointer; line-height:1;">&times;</button>
                </div>
                <div id="lattice-mail-preview-body" style="padding:0;">
                    <iframe id="lattice-mail-preview-frame" name="lattice-preview-frame" style="width:100%; height:600px; border:none; display:block;"></iframe>
                </div>
            </div>
        </div>

        <!-- Hidden form for POST-based preview -->
        <form id="lattice-mail-preview-form" method="post" action="<?php echo admin_url('admin.php?page=lattice-mail-campaigns'); ?>" target="lattice-preview-frame" style="display:none;">
            <input type="hidden" name="lattice_mail_preview" value="1">
            <input type="hidden" name="subject" id="preview_subject" value="">
            <input type="hidden" name="preview_text" id="preview_preview_text" value="">
            <input type="hidden" name="content" id="preview_content" value="">
            <input type="hidden" name="template" id="preview_template" value="">
        </form>

        <script>
        (function($) {
            'use strict';

            var LatticeMailPreview = {
                modal: null,
                frame: null,
                init: function() {
                    this.modal = $('#lattice-mail-preview-modal');
                    this.frame = $('#lattice-mail-preview-frame');
                    this.form = $('#lattice-mail-preview-form');

                    $('#lattice-mail-preview-btn').on('click', $.proxy(this.openPreview, this));
                    $('#lattice-mail-preview-close').on('click', $.proxy(this.closePreview, this));

                    this.modal.on('click', function(e) {
                        if (e.target === this) {
                            LatticeMailPreview.closePreview();
                        }
                    });

                    $(document).on('keyup', function(e) {
                        if (e.key === 'Escape') {
                            LatticeMailPreview.closePreview();
                        }
                    });
                },
                openPreview: function() {
                    var subject = $('#subject').val() || '<?php esc_attr_e('No Subject', 'lattice-mail'); ?>';
                    var preview_text = $('#preview_text').val() || '';
                    var content = '';
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                        content = tinyMCE.activeEditor.getContent();
                    } else {
                        content = $('#lattice-mail-editor').val();
                    }
                    var template_slug = $('#template_slug').val() || 'default';

                    $('#preview_subject').val(subject);
                    $('#preview_preview_text').val(preview_text);
                    $('#preview_content').val(content);
                    $('#preview_template').val(template_slug);

                    this.form.submit();
                    this.modal.show();
                },
                closePreview: function() {
                    this.modal.hide();
                }
            };

            $(document).ready(function() {
                LatticeMailPreview.init();
            });

        })(jQuery);
        </script>

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

    public function render_auto_responders() {
        $ar = Lattice_Mail_Auto_Responder::get_instance();
        $segment = Lattice_Mail_Segment::get_instance();
        $segments = $segment->get_all();

        // Handle create
        if (isset($_POST['lattice_mail_ar_create']) && wp_verify_nonce($_POST['lattice_mail_ar_nonce'], 'lattice_mail_ar_create')) {
            $title = sanitize_text_field($_POST['title'] ?? '');
            $trigger_type = sanitize_key($_POST['trigger_type'] ?? 'welcome');
            $delay_days = (int) ($_POST['delay_days'] ?? 0);
            $subject = sanitize_text_field($_POST['subject'] ?? '');
            $content = wp_kses_post($_POST['content'] ?? '');
            $segment_id = (int) ($_POST['segment_id'] ?? 0);
            $status = sanitize_key($_POST['status'] ?? 'active');

            if (!empty($title) && !empty($subject) && !empty($content)) {
                $ar->create([
                    'title' => $title,
                    'trigger_type' => $trigger_type,
                    'delay_days' => $delay_days,
                    'subject' => $subject,
                    'content' => $content,
                    'segment_id' => $segment_id,
                    'status' => $status,
                ]);
                wp_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=lattice-mail-auto-responders')));
                exit;
            }
        }

        // Handle delete
        if (isset($_POST['lattice_mail_ar_delete']) && wp_verify_nonce($_POST['lattice_mail_ar_nonce'], 'lattice_mail_ar_delete')) {
            $id = (int) $_POST['ar_id'];
            $ar->delete($id);
            wp_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=lattice-mail-auto-responders')));
            exit;
        }

        // Handle pause/activate
        if (isset($_POST['lattice_mail_ar_toggle']) && wp_verify_nonce($_POST['lattice_mail_ar_nonce'], 'lattice_mail_ar_toggle')) {
            $id = (int) $_POST['ar_id'];
            $current_status = sanitize_text_field($_POST['current_status']);
            if ($current_status === 'active') {
                $ar->pause($id);
            } else {
                $ar->activate($id);
            }
            wp_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=lattice-mail-auto-responders')));
            exit;
        }

        $responders = $ar->get_all();

        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Auto-Responders', 'lattice-mail'); ?></h1>
            <p><?php _e('Auto-responders send emails automatically when a subscriber joins or after a set number of days.', 'lattice-mail'); ?></p>

            <h2><?php _e('Create Auto-Responder', 'lattice-mail'); ?></h2>
            <form method="post" style="max-width: 700px; margin-bottom: 40px; background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">
                <table class="form-table">
                    <tr>
                        <th><label for="ar_title"><?php _e('Name', 'lattice-mail'); ?> *</label></th>
                        <td><input type="text" name="title" id="ar_title" class="widefat" required placeholder="<?php _e('e.g. Welcome Serie Deel 1', 'lattice-mail'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="ar_trigger"><?php _e('Trigger', 'lattice-mail'); ?></label></th>
                        <td>
                            <select name="trigger_type" id="ar_trigger">
                                <option value="welcome"><?php _e('On subscription (welcome)', 'lattice-mail'); ?></option>
                                <option value="drip"><?php _e('Drip (X days after subscribe)', 'lattice-mail'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ar_delay"><?php _e('Days Delay', 'lattice-mail'); ?></label></th>
                        <td>
                            <input type="number" name="delay_days" id="ar_delay" value="0" min="0" style="width: 80px;">
                            <p class="description"><?php _e('0 = send immediately on subscription. For drip, set number of days after subscription.', 'lattice-mail'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ar_segment"><?php _e('Segment', 'lattice-mail'); ?></label></th>
                        <td>
                            <select name="segment_id" id="ar_segment">
                                <option value="0"><?php _e('All subscribers', 'lattice-mail'); ?></option>
                                <?php foreach ($segments as $seg): ?>
                                    <option value="<?php echo esc_attr($seg->id); ?>"><?php echo esc_html($seg->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Leave at "All subscribers" to apply to everyone.', 'lattice-mail'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ar_subject"><?php _e('Subject', 'lattice-mail'); ?> *</label></th>
                        <td><input type="text" name="subject" id="ar_subject" class="widefat" required placeholder="<?php _e('e.g. Welkom bij onze nieuwsbrief!', 'lattice-mail'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="ar_content"><?php _e('Content (HTML)', 'lattice-mail'); ?> *</label></th>
                        <td><textarea name="content" id="ar_content" rows="8" class="widefat" required placeholder="<p>Hoi!</p><p>Welkom bij...</p>"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="ar_status"><?php _e('Status', 'lattice-mail'); ?></label></th>
                        <td>
                            <select name="status" id="ar_status">
                                <option value="active"><?php _e('Active', 'lattice-mail'); ?></option>
                                <option value="paused"><?php _e('Paused', 'lattice-mail'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php wp_nonce_field('lattice_mail_ar_create', 'lattice_mail_ar_nonce'); ?>
                <button type="submit" name="lattice_mail_ar_create" class="button button-primary"><?php _e('Create Auto-Responder', 'lattice-mail'); ?></button>
            </form>

            <h2><?php _e('All Auto-Responders', 'lattice-mail'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'lattice-mail'); ?></th>
                        <th><?php _e('Trigger', 'lattice-mail'); ?></th>
                        <th><?php _e('Delay', 'lattice-mail'); ?></th>
                        <th><?php _e('Status', 'lattice-mail'); ?></th>
                        <th><?php _e('Sent', 'lattice-mail'); ?></th>
                        <th><?php _e('Queued', 'lattice-mail'); ?></th>
                        <th><?php _e('Actions', 'lattice-mail'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($responders)): ?>
                        <tr><td colspan="7"><?php _e('No auto-responders yet.', 'lattice-mail'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($responders as $r): ?>
                            <tr>
                                <td><?php echo esc_html($r->title); ?></td>
                                <td><?php echo $r->trigger_type === 'welcome' ? __('Welcome email', 'lattice-mail') : __('Drip email', 'lattice-mail'); ?></td>
                                <td><?php echo $r->delay_days; ?> <?php _e('days', 'lattice-mail'); ?></td>
                                <td><span class="status-<?php echo esc_attr($r->status); ?>"><?php echo esc_html($r->status); ?></span></td>
                                <td><?php echo (int) $ar->count_sent($r->id); ?></td>
                                <td><?php echo (int) $ar->count_queue($r->id); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="ar_id" value="<?php echo esc_attr($r->id); ?>">
                                        <input type="hidden" name="current_status" value="<?php echo esc_attr($r->status); ?>">
                                        <?php wp_nonce_field('lattice_mail_ar_toggle', 'lattice_mail_ar_nonce'); ?>
                                        <button type="submit" name="lattice_mail_ar_toggle" class="button button-secondary" style="font-size:12px;">
                                            <?php echo $r->status === 'active' ? __('Pause', 'lattice-mail') : __('Activate', 'lattice-mail'); ?>
                                        </button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e('Are you sure?', 'lattice-mail'); ?>');">
                                        <input type="hidden" name="ar_id" value="<?php echo esc_attr($r->id); ?>">
                                        <?php wp_nonce_field('lattice_mail_ar_delete', 'lattice_mail_ar_nonce'); ?>
                                        <button type="submit" name="lattice_mail_ar_delete" class="button button-link" style="color:#b32d2e;font-size:12px;"><?php _e('Delete', 'lattice-mail'); ?></button>
                                    </form>
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

        $active_tab = sanitize_key($_GET['tab'] ?? 'general');

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

        if (isset($_POST['lattice_mail_woo_submit']) && wp_verify_nonce($_POST['lattice_mail_woo_nonce'], 'lattice_mail_woo_settings')) {
            update_option('lattice_mail_woo_subscribe_enabled', sanitize_text_field($_POST['lattice_mail_woo_enabled'] ?? 'no'));
            update_option('lattice_mail_woo_subscribe_label', sanitize_text_field($_POST['lattice_mail_woo_label'] ?? __('Subscribe to our newsletter', 'lattice-mail')));
            update_option('lattice_mail_woo_subscribe_checked', sanitize_text_field($_POST['lattice_mail_woo_checked'] ?? 'no'));
            update_option('lattice_mail_woo_double_optin', sanitize_text_field($_POST['lattice_mail_woo_double_optin'] ?? 'no'));
            echo '<div class="notice notice-success"><p>' . esc_html__('WooCommerce settings saved.', 'lattice-mail') . '</p></div>';
        }

        $woo_enabled = get_option('lattice_mail_woo_subscribe_enabled', 'yes');
        $woo_label = get_option('lattice_mail_woo_subscribe_label', __('Subscribe to our newsletter', 'lattice-mail'));
        $woo_checked = get_option('lattice_mail_woo_subscribe_checked', 'no');
        $woo_double_optin = get_option('lattice_mail_woo_double_optin', 'no');

        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Lattice Mail Settings', 'lattice-mail'); ?></h1>

            <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="<?php echo admin_url('admin.php?page=lattice-mail-settings&tab=general'); ?>" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'lattice-mail'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=lattice-mail-settings&tab=woo'); ?>" class="nav-tab <?php echo $active_tab === 'woo' ? 'nav-tab-active' : ''; ?>"><?php _e('WooCommerce', 'lattice-mail'); ?></a>
            </h2>

            <?php if ($active_tab === 'general'): ?>
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
            <?php elseif ($active_tab === 'woo'): ?>
            <form method="post" style="max-width: 600px;">
                <h2><?php _e('WooCommerce Checkout Subscription', 'lattice-mail'); ?></h2>
                <p><?php _e('Configure the newsletter subscription checkbox shown at WooCommerce checkout.', 'lattice-mail'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="lattice_mail_woo_enabled"><?php _e('Enable Subscription', 'lattice-mail'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lattice_mail_woo_enabled" id="lattice_mail_woo_enabled" value="yes" <?php checked($woo_enabled, 'yes'); ?>>
                                <?php _e('Show subscription checkbox at WooCommerce checkout', 'lattice-mail'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lattice_mail_woo_label"><?php _e('Checkbox Label', 'lattice-mail'); ?></label></th>
                        <td>
                            <input type="text" name="lattice_mail_woo_label" id="lattice_mail_woo_label" value="<?php echo esc_attr($woo_label); ?>" class="widefat">
                            <p class="description"><?php _e('The text shown next to the subscription checkbox.', 'lattice-mail'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lattice_mail_woo_checked"><?php _e('Default State', 'lattice-mail'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lattice_mail_woo_checked" id="lattice_mail_woo_checked" value="yes" <?php checked($woo_checked, 'yes'); ?>>
                                <?php _e('Pre-check the subscription checkbox by default', 'lattice-mail'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lattice_mail_woo_double_optin"><?php _e('Double Opt-In', 'lattice-mail'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="lattice_mail_woo_double_optin" id="lattice_mail_woo_double_optin" value="yes" <?php checked($woo_double_optin, 'yes'); ?>>
                                <?php _e('Require email confirmation before subscribing (double opt-in)', 'lattice-mail'); ?>
                            </label>
                            <p class="description"><?php _e('When enabled, subscribers will receive a confirmation email and will only be added after they click the confirmation link.', 'lattice-mail'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php wp_nonce_field('lattice_mail_woo_settings', 'lattice_mail_woo_nonce'); ?>
                <button type="submit" name="lattice_mail_woo_submit" class="button button-primary"><?php _e('Save WooCommerce Settings', 'lattice-mail'); ?></button>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_import_export() {
        $subscriber = Lattice_Mail_Subscriber::get_instance();
        $message = '';
        $error = '';

        // Handle CSV export
        if (isset($_POST['lattice_mail_export']) && wp_verify_nonce($_POST['lattice_mail_export_nonce'], 'lattice_mail_export')) {
            $status = sanitize_key($_POST['export_status'] ?? '');
            $subscriber->export_to_csv($status ?: null);
            // export_to_csv exits, so nothing below runs
        }

        // Handle template download
        if (isset($_POST['lattice_mail_template']) && wp_verify_nonce($_POST['lattice_mail_template_nonce'], 'lattice_mail_template')) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="lattice-mail-subscribers-template.csv"');
            header('Cache-Control: no-store, no-cache');
            echo "\xEF\xBB\xBF"; // BOM
            echo "email,name\n";
            echo "jan@example.com,Jan Jansen\n";
            echo "piet@example.com,Piet Smit\n";
            exit;
        }

        // Handle CSV import
        if (isset($_POST['lattice_mail_import']) && wp_verify_nonce($_POST['lattice_mail_import_nonce'], 'lattice_mail_import')) {
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                $error = __('No file uploaded or upload error.', 'lattice-mail');
            } else {
                $file = $_FILES['import_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($ext !== 'csv') {
                    $error = __('Only CSV files are supported.', 'lattice-mail');
                } else {
                    $send_confirmation = !empty($_POST['send_confirmation']);
                    $skip_duplicates = !empty($_POST['skip_duplicates']);
                    $result = $subscriber->import_from_csv($file['tmp_name'], $send_confirmation, $skip_duplicates);
                    $message = sprintf(
                        __('Import complete: %d added, %d skipped, %d errors.', 'lattice-mail'),
                        $result['added'],
                        $result['skipped'],
                        $result['errors']
                    );
                }
            }
        }

        // Count subscribers by status
        $all = $subscriber->get_all();
        $active = $subscriber->get_all('active');
        $pending = $subscriber->get_all('pending');
        $unsubscribed = $subscriber->get_all('unsubscribed');

        ?>
        <div class="wrap lattice-mail-admin">
            <h1><?php _e('Import / Export Subscribers', 'lattice-mail'); ?></h1>

            <?php if ($message): ?>
                <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">

                <!-- EXPORT -->
                <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">
                    <h2><?php _e('Export Subscribers', 'lattice-mail'); ?></h2>
                    <p><?php _e('Download your subscribers as a CSV file. You can filter by status.', 'lattice-mail'); ?></p>

                    <form method="post">
                        <table class="form-table">
                            <tr>
                                <th><label for="export_status"><?php _e('Status Filter', 'lattice-mail'); ?></label></th>
                                <td>
                                    <select name="export_status" id="export_status">
                                        <option value=""><?php _e('All subscribers', 'lattice-mail'); ?></option>
                                        <option value="active"><?php _e('Active', 'lattice-mail'); ?></option>
                                        <option value="pending"><?php _e('Pending', 'lattice-mail'); ?></option>
                                        <option value="unsubscribed"><?php _e('Unsubscribed', 'lattice-mail'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p style="margin-top:10px;">
                            <strong><?php _e('Current counts:', 'lattice-mail'); ?></strong><br>
                            <?php echo count($all); ?> <?php _e('total', 'lattice-mail'); ?> |
                            <?php echo count($active); ?> <?php _e('active', 'lattice-mail'); ?> |
                            <?php echo count($pending); ?> <?php _e('pending', 'lattice-mail'); ?> |
                            <?php echo count($unsubscribed); ?> <?php _e('unsubscribed', 'lattice-mail'); ?>
                        </p>
                        <?php wp_nonce_field('lattice_mail_export', 'lattice_mail_export_nonce'); ?>
                        <button type="submit" name="lattice_mail_export" class="button button-primary">
                            <?php _e('Download CSV', 'lattice-mail'); ?>
                        </button>
                    </form>
                </div>

                <!-- IMPORT -->
                <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">
                    <h2><?php _e('Import Subscribers', 'lattice-mail'); ?></h2>
                    <p><?php _e('Upload a CSV file with email addresses. First row must be headers: email, name (optional).', 'lattice-mail'); ?></p>
                    <p><?php _e('Duplicate detection: already subscribed emails are skipped by default.', 'lattice-mail'); ?></p>

                    <form method="post" enctype="multipart/form-data">
                        <table class="form-table">
                            <tr>
                                <th><label for="import_file"><?php _e('CSV File', 'lattice-mail'); ?></label></th>
                                <td>
                                    <input type="file" name="import_file" id="import_file" accept=".csv" required>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Options', 'lattice-mail'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="send_confirmation" value="1">
                                        <?php _e('Send confirmation email (subscribers will be pending until confirmed)', 'lattice-mail'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="skip_duplicates" value="1" checked>
                                        <?php _e('Skip already subscribed emails', 'lattice-mail'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        <p class="description" style="margin-bottom:10px;">
                            <?php _e('CSV format example:', 'lattice-mail'); ?><br>
                            <code>email,name</code><br>
                            <code>jan@example.com,Jan Jansen</code><br>
                            <code>piet@example.com,Piet Smit</code>
                        </p>
                        <?php wp_nonce_field('lattice_mail_import', 'lattice_mail_import_nonce'); ?>
                        <button type="submit" name="lattice_mail_import" class="button button-primary">
                            <?php _e('Import CSV', 'lattice-mail'); ?>
                        </button>
                    </form>
                </div>

            </div>

            <hr style="margin: 40px 0;">

            <h2><?php _e('CSV Template', 'lattice-mail'); ?></h2>
            <p><?php _e('Download a blank CSV template to use for import:', 'lattice-mail'); ?></p>
            <form method="post" style="display:inline;">
                <?php wp_nonce_field('lattice_mail_template', 'lattice_mail_template_nonce'); ?>
                <button type="submit" name="lattice_mail_template" class="button"><?php _e('Download Template', 'lattice-mail'); ?></button>
            </form>
        </div>
        <?php
    }
}

new Lattice_Mail_Admin();
