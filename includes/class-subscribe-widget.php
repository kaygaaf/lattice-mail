<?php

if (!defined('ABSPATH')) {
    exit;
}

class Lattice_Mail_Subscribe_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'lattice_mail_subscribe',
            __('Lattice Mail Subscribe', 'lattice-mail'),
            ['description' => __('Subscribe form widget', 'lattice-mail')]
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        echo do_shortcode('[lattice_mail_subscribe title="' . esc_attr($instance['title'] ?? '') . '" show_name="' . ($instance['show_name'] ? 'true' : 'false') . '"]');
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = $instance['title'] ?? '';
        $show_name = $instance['show_name'] ?? true;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'lattice-mail'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label>
                <input type="checkbox" name="<?php echo $this->get_field_name('show_name'); ?>" value="1" <?php checked($show_name, true); ?>>
                <?php _e('Show name field', 'lattice-mail'); ?>
            </label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['show_name'] = !empty($new_instance['show_name']);
        return $instance;
    }
}
