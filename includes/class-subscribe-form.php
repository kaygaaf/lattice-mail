<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_Subscribe_Form {

    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_shortcode('lattice_mail_subscribe', [$this, 'shortcode']);
        add_action('widgets_init', [$this, 'register_widget']);
        add_action('wp_head', [$this, 'print_styles']);
    }

    public function shortcode($atts = []) {
        $atts = shortcode_atts([
            'title' => __('Subscribe to our newsletter', 'lattice-mail'),
            'show_name' => 'true',
            'button_text' => __('Subscribe', 'lattice-mail'),
        ], $atts);

        // Check double opt-in setting
        $double_optin = get_option('lattice_mail_woo_double_optin', 'no') === 'yes';

        ob_start();
        ?>
        <div class="lattice-mail-form-wrapper">
            <h3 class="lattice-mail-form-title"><?php echo esc_html($atts['title']); ?></h3>
            <form class="lattice-mail-form" method="post">
                <?php if ($atts['show_name'] === 'true'): ?>
                    <p>
                        <input type="text" name="name" placeholder="<?php esc_attr_e('Your name', 'lattice-mail'); ?>" class="lattice-mail-input">
                    </p>
                <?php endif; ?>
                <p>
                    <input type="email" name="email" required placeholder="<?php esc_attr_e('Your email', 'lattice-mail'); ?>" class="lattice-mail-input">
                </p>
                <p>
                    <?php wp_nonce_field('lattice_mail_subscribe', 'nonce'); ?>
                    <input type="hidden" name="action" value="lattice_mail_subscribe">
                    <button type="submit" class="lattice-mail-button"><?php echo esc_html($atts['button_text']); ?></button>
                </p>
            </form>
            <div class="lattice-mail-message" style="display:none;"></div>
        </div>
        <style>
            .lattice-mail-form-wrapper { max-width: 400px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
            .lattice-mail-form-title { margin: 0 0 15px; text-align: center; color: #333; }
            .lattice-mail-input { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
            .lattice-mail-button { width: 100%; padding: 12px; background: #B88769; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
            .lattice-mail-button:hover { background: #a07656; }
            .lattice-mail-message { margin-top: 15px; padding: 10px; border-radius: 4px; text-align: center; }
            .lattice-mail-message.success { background: #d4edda; color: #155724; }
            .lattice-mail-message.error { background: #f8d7da; color: #721c24; }
        </style>
        <script>
            (function() {
                var form = document.querySelector('.lattice-mail-form');
                if (!form) return;
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(form);
                    var messageDiv = document.querySelector('.lattice-mail-message');
                    messageDiv.style.display = 'none';
                    messageDiv.className = 'lattice-mail-message';
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        messageDiv.style.display = 'block';
                        messageDiv.classList.add(data.success ? 'success' : 'error');
                        messageDiv.textContent = data.data.message || '<?php esc_html_e('An error occurred.', 'lattice-mail'); ?>';
                        if (data.success) form.reset();
                    });
                });
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    public function register_widget() {
        require_once LATTICE_MAIL_PLUGIN_DIR . 'includes/class-subscribe-widget.php';
        register_widget('Lattice_Mail_Subscribe_Widget');
    }

    public function print_styles() {
        ?>
        <style>
            .lattice-mail-widget { padding: 15px; background: #f9f9f9; border-radius: 8px; }
        </style>
        <?php
    }
}

new Lattice_Mail_Subscribe_Form();
