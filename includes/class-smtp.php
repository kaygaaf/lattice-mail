<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_SMTP {

    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_settings() {
        return [
            'from_email' => get_option('lattice_mail_from_email', get_option('admin_email')),
            'from_name' => get_option('lattice_mail_from_name', get_bloginfo('name')),
            'mailer' => get_option('lattice_mail_mailer', 'wp_mail'),
            'smtp_host' => get_option('lattice_mail_smtp_host', ''),
            'smtp_port' => get_option('lattice_mail_smtp_port', 587),
            'smtp_user' => get_option('lattice_mail_smtp_user', ''),
            'smtp_pass' => get_option('lattice_mail_smtp_pass', ''),
            'smtp_secure' => get_option('lattice_mail_smtp_secure', 'tls'),
            'smtp_provider' => get_option('lattice_mail_smtp_provider', 'other'),
        ];
    }

    public function get_provider_presets() {
        return [
            'sendgrid' => [
                'name' => 'SendGrid',
                'host' => 'smtp.sendgrid.net',
                'port' => 587,
                'secure' => 'tls',
                'fields' => ['user' => 'API Key', 'pass_label' => 'API Key'],
                'docs_url' => 'https://docs.sendgrid.com/ui/account-and-settings/api-keys',
            ],
            'ses' => [
                'name' => 'Amazon SES',
                'host' => 'email-smtp.eu-west-1.amazonaws.com',
                'port' => 587,
                'secure' => 'tls',
                'fields' => ['user' => 'Access Key ID', 'pass_label' => 'Secret Access Key'],
                'docs_url' => 'https://docs.aws.amazon.com/ses/latest/dg/smtp-credentials.html',
            ],
            'mailcow' => [
                'name' => 'MailCow',
                'host' => '',
                'port' => 587,
                'secure' => 'tls',
                'fields' => ['user' => 'Username', 'pass_label' => 'Password'],
                'docs_url' => 'https://docs.mailcow.email/postfix/smtp_guide/',
            ],
            'mailgun' => [
                'name' => 'Mailgun',
                'host' => 'smtp.mailgun.org',
                'port' => 587,
                'secure' => 'tls',
                'fields' => ['user' => 'Default SMTP login', 'pass_label' => 'Password'],
                'docs_url' => 'https://help.mailgun.com/hc/en-us/articles/360012874452-What-are-the-credentials-for-SMTP-',
            ],
            'postmark' => [
                'name' => 'Postmark',
                'host' => 'smtp.postmarkapp.com',
                'port' => 587,
                'secure' => 'tls',
                'fields' => ['user' => 'Server Token', 'pass_label' => 'Server Token'],
                'docs_url' => 'https://postmarkapp.com/support/article/847-connecting-to-smtp',
            ],
            'other' => [
                'name' => 'Other / Manual',
                'host' => '',
                'port' => 587,
                'secure' => 'tls',
                'fields' => ['user' => 'Username', 'pass_label' => 'Password'],
                'docs_url' => '',
            ],
        ];
    }

    public function save_settings($settings) {
        foreach ($settings as $key => $value) {
            update_option("lattice_mail_{$key}", $value);
        }
        return true;
    }

    public function test_smtp_connection($test_email = null) {
        $settings = $this->get_settings();

        if (empty($settings['smtp_host'])) {
            return new WP_Error('smtp_missing', __('SMTP host is not configured.', 'lattice-mail'));
        }

        if (empty($test_email)) {
            $test_email = $settings['from_email'];
        }

        return $this->send_via_smtp(
            $test_email,
            __('Lattice Mail SMTP Test', 'lattice-mail'),
            '<p>' . __('This is a test email from Lattice Mail plugin.', 'lattice-mail') . '</p>' .
            '<p>' . sprintf(__('Sent at: %s', 'lattice-mail'), current_time('Y-m-d H:i:s')) . '</p>',
            $settings,
            ['Content-Type: text/html; charset=UTF-8']
        );
    }

    /**
     * Human-readable PHPMailer error messages
     */
    public static function human_readable_smtp_error($exception) {
        $msg = $exception->getMessage();

        if (strpos($msg, 'SMTP Error: Could not authenticate') !== false || strpos($msg, 'SMTP connect() failed') !== false) {
            if (strpos($msg, 'authentication') !== false || strpos($msg, 'auth') !== false) {
                return __('Authentication failed. Please check your username/API key and password/secret.', 'lattice-mail');
            }
            return __('Could not connect to the SMTP server. Please check the host, port, and encryption settings.', 'lattice-mail');
        }

        if (strpos($msg, 'connection timed out') !== false || strpos($msg, 'Connection refused') !== false) {
            return __('Connection timed out or was refused. Check the SMTP host and port.', 'lattice-mail');
        }

        if (strpos($msg, 'TLS') !== false || strpos($msg, 'SSL') !== false) {
            return __('TLS/SSL error. Make sure encryption is set correctly (TLS on port 587, SSL on port 465).', 'lattice-mail');
        }

        if (strpos($msg, 'SMTP Error: Could not connect to host') !== false) {
            return __('Could not reach the SMTP host. Double-check the hostname.', 'lattice-mail');
        }

        return $msg;
    }

    public function send($to, $subject, $message) {
        $settings = $this->get_settings();

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$settings['from_name']} <{$settings['from_email']}>",
        ];

        if ($settings['mailer'] === 'smtp' && !empty($settings['smtp_host'])) {
            return $this->send_via_smtp($to, $subject, $message, $settings, $headers);
        }

        return wp_mail($to, $subject, $message, $headers);
    }

    private function send_via_smtp($to, $subject, $message, $settings, $headers) {
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_user'];
            $mail->Password = $settings['smtp_pass'];
            $mail->SMTPSecure = $settings['smtp_secure'] === 'ssl' ? 'ssl' : 'tls';
            $mail->Port = (int) $settings['smtp_port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($settings['from_email'], $settings['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $message;

            foreach ($headers as $header) {
                $mail->addCustomHeader($header);
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            return new WP_Error('mail_failed', $mail->ErrorInfo);
        }
    }

    public function test_connection($settings, $test_email = null) {
        if (empty($test_email)) {
            $test_email = $settings['from_email'];
        }
        $result = $this->send_via_smtp(
            $test_email,
            __('Lattice Mail SMTP Test', 'lattice-mail'),
            '<p>' . __('This is a test email from Lattice Mail plugin.', 'lattice-mail') . '</p>' .
            '<p>' . sprintf(__('Sent at: %s', 'lattice-mail'), current_time('Y-m-d H:i:s')) . '</p>',
            $settings,
            ['Content-Type: text/html; charset=UTF-8']
        );
        return $result;
    }
}
