<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_Email_Template {

    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_default_template() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #B88769; }
        .header h1 { color: #B88769; margin: 0; font-size: 24px; }
        .content { padding: 30px 0; }
        .content p { margin: 0 0 15px; }
        .footer { text-align: center; padding: 20px 0; border-top: 1px solid #eee; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{site_name}}</h1>
    </div>
    <div class="content">
        {{content}}
    </div>
    <div class="footer">
        <p>{{unsubscribe_link}}</p>
    </div>
</body>
</html>';
    }

    public function get_minimal_template() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
    <style>
        body { font-family: Georgia, serif; line-height: 1.8; color: #222; max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .content { font-size: 16px; }
        .content p { margin: 0 0 16px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 13px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="content">
        {{content}}
    </div>
    <div class="footer">
        <p>{{unsubscribe_link}}</p>
    </div>
</body>
</html>';
    }

    public function get_woocommerce_template() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 0; background: #f5f5f5; }
        .email-wrapper { background: #ffffff; margin: 0 auto; }
        .header { background: #7f54b3; color: #ffffff; padding: 30px 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .header p { margin: 8px 0 0; opacity: 0.85; font-size: 14px; }
        .content { padding: 40px; }
        .content p { margin: 0 0 16px; font-size: 15px; }
        .content h2 { color: #7f54b3; font-size: 20px; margin: 0 0 16px; }
        .content .product-block { background: #f9f9f9; border-left: 4px solid #7f54b3; padding: 15px 20px; margin: 20px 0; }
        .cta-button { display: inline-block; background: #7f54b3; color: #ffffff !important; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: 600; margin: 20px 0; }
        .footer { background: #f0f0f0; padding: 25px 40px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>{{site_name}}</h1>
            <p>{{preview_text}}</p>
        </div>
        <div class="content">
            {{content}}
        </div>
        <div class="footer">
            <p>{{unsubscribe_link}}</p>
        </div>
    </div>
</body>
</html>';
    }

    public function get_templates() {
        return [
            'default' => [
                'name' => __('Default (Branded)', 'lattice-mail'),
                'slug' => 'default',
                'template' => $this->get_default_template(),
            ],
            'minimal' => [
                'name' => __('Minimal (Clean)', 'lattice-mail'),
                'slug' => 'minimal',
                'template' => $this->get_minimal_template(),
            ],
            'woocommerce' => [
                'name' => __('WooCommerce Style', 'lattice-mail'),
                'slug' => 'woocommerce',
                'template' => $this->get_woocommerce_template(),
            ],
        ];
    }

    public function get_template_by_slug($slug) {
        $templates = $this->get_templates();
        return $templates[$slug]['template'] ?? $this->get_default_template();
    }

    public function render($template, $data = []) {
        $defaults = [
            'site_name' => get_bloginfo('name'),
            'subject' => '',
            'content' => '',
            'unsubscribe_link' => '',
        ];
        $data = wp_parse_args($data, $defaults);

        foreach ($data as $key => $value) {
            $template = str_replace("{{{$key}}}", $value, $template);
        }

        return $template;
    }

    public function wrap_content($content, $subject = '') {
        $template = get_option('lattice_mail_email_template', $this->get_default_template());

        $unsubscribe_link = '<a href="{{unsubscribe_url}}">' . __('Unsubscribe', 'lattice-mail') . '</a>';

        return $this->render($template, [
            'subject' => $subject,
            'content' => $content,
            'unsubscribe_link' => $unsubscribe_link,
        ]);
    }
}
