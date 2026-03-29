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
        ];
    }

    public function save_settings($settings) {
        foreach ($settings as $key => $value) {
            update_option("lattice_mail_{$key}", $value);
        }
        return true;
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

    public function test_connection($settings) {
        $test_email = $settings['from_email'];
        $result = $this->send_via_smtp(
            $test_email,
            __('Lattice Mail SMTP Test', 'lattice-mail'),
            __('This is a test email from Lattice Mail plugin.', 'lattice-mail'),
            $settings,
            ['Content-Type: text/html; charset=UTF-8']
        );
        return $result;
    }
}
