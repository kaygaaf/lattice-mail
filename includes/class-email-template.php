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
