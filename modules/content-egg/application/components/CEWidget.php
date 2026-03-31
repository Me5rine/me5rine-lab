<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

/**
 * ProductSearchWidget class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
abstract class CEWidget extends \WP_Widget
{
    public $name;
    protected $slug;
    protected $description;
    protected $classname;
    protected $settings;

    public function __construct()
    {
        \add_action('widgets_init', function ()
        {
            \register_widget(get_called_class());
        });

        $this->slug = $this->slug();
        $this->name = $this->name();
        $this->description = $this->description();
        $this->classname = $this->classname();
        $this->settings = $this->settings();

        parent::__construct(
            $this->slug,
            \esc_html($this->name),
            array(
                'description' => \esc_html($this->description),
                'classname' => $this->classname
            )
        );

        \add_action('save_post', array($this, 'flushCache'));
        \add_action('deleted_post', array($this, 'flushCache'));
        \add_action('switch_theme', array($this, 'flushCache'));
        \add_action('content_egg_price_history_save', array($this, 'flushCache'));
    }

    abstract public function slug();

    abstract public function description();

    abstract protected function name();

    abstract public function classname();

    public function settings()
    {
        return array();
    }

    public function setCache($data, $key = 0, $expire = 0)
    {
        if (!$key)
        {
            $key = 0;
        }
        $cache = \wp_cache_get($this->slug, 'widget');
        if (!$cache || !is_array($cache))
        {
            $cache = array();
        }
        $cache[$key] = $data;
        \wp_cache_set($this->slug, $cache, 'widget', $expire);
    }

    public function getCache($key = 0)
    {
        $cache = \wp_cache_get($this->slug, 'widget');
        if (!$key)
        {
            $key = 0;
        }
        $cache = \wp_cache_get($this->slug, 'widget');

        if (!$cache || !is_array($cache))
        {
            $cache = array();
        }

        if (isset($cache[$key]))
        {
            return $cache[$key];
        }
        else
        {
            return null;
        }
    }

    public function flushCache()
    {
        \wp_cache_delete($this->slug, 'widget');
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();

        if (!$this->settings || !is_array($this->settings))
        {
            return array();
        }

        foreach ($this->settings as $key => $setting)
        {
            switch ($setting['type'])
            {
                case 'number':
                    $instance[$key] = absint($new_instance[$key]);
                    if (isset($setting['min']) && $instance[$key] < $setting['min'])
                    {
                        $instance[$key] = $setting['min'];
                    }
                    if (isset($setting['max']) && $instance[$key] > $setting['max'])
                    {
                        $instance[$key] = $setting['max'];
                    }
                    break;
                case 'textarea':
                    $instance[$key] = \wp_kses(trim(\wp_unslash($new_instance[$key])), \wp_kses_allowed_html('post'));
                    break;
                case 'checkbox':
                    $instance[$key] = empty($new_instance[$key]) ? 0 : 1;
                    break;
                default:
                    $instance[$key] = (!empty($new_instance[$key])) ? \sanitize_text_field($new_instance[$key]) : '';
                    break;
            }
        }

        $this->flushCache();

        return $instance;
    }

    public function form($instance)
    {
        if (!$this->settings || !is_array($this->settings))
        {
            return array();
        }

        foreach ($this->settings as $key => $setting)
        {
            $value = isset($instance[$key]) ? $instance[$key] : $setting['default'];
            switch ($setting['type'])
            {
                case 'number':
?>
                    <p>
                        <label for="<?php echo \esc_attr($this->get_field_id($key)); ?>"><?php echo \esc_attr($setting['title']); ?>
                            :</label>
                        <input class="widefat" id="<?php echo \esc_attr($this->get_field_id($key)); ?>" name="<?php echo \esc_attr($this->get_field_name($key)); ?>" type="number" min="<?php echo \esc_attr($setting['min']); ?>" max="<?php echo \esc_attr($setting['max']); ?>" value="<?php echo \esc_attr($value); ?>" />
                    </p>
                <?php
                    break;

                case 'select':
                ?>
                    <p>
                        <label for="<?php echo \esc_attr($this->get_field_id($key)); ?>"><?php echo \esc_attr($setting['title']); ?>
                            :</label>
                        <select class="widefat" id="<?php echo \esc_attr($this->get_field_id($key)); ?>" name="<?php echo \esc_attr($this->get_field_name($key)); ?>">
                            <?php foreach ($setting['options'] as $option_key => $option_value) : ?>
                                <option value="<?php echo \esc_attr($option_key); ?>" <?php \selected($option_key, $value); ?>><?php echo \esc_html($option_value); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                <?php
                    break;

                case 'textarea':
                ?>
                    <p>
                        <label for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo esc_html($setting['title']); ?>
                            :</label>
                        <textarea class="widefat <?php echo esc_attr($class); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo esc_attr($this->get_field_name($key)); ?>" cols="20" rows="3"><?php echo esc_textarea($value); ?></textarea>
                        <?php if (isset($setting['desc'])) : ?>
                            <small><?php echo esc_html($setting['desc']); ?></small>
                        <?php endif; ?>
                    </p>
                <?php
                    break;

                case 'checkbox':
                ?>
                    <p>
                        <input class="checkbox" id="<?php echo \esc_attr($this->get_field_id($key)); ?>" name="<?php echo \esc_attr($this->get_field_name($key)); ?>" type="checkbox" value="1" <?php checked($value, 1); ?> />
                        <label for="<?php echo \esc_attr($this->get_field_id($key)); ?>"><?php echo \esc_attr($setting['title']); ?></label>
                    </p>
                <?php
                    break;
                default:
                ?>
                    <p>
                        <label for="<?php echo \esc_attr($this->get_field_id($key)); ?>"><?php echo \esc_attr($setting['title']); ?>
                            :</label>
                        <input class="widefat" id="<?php echo \esc_attr($this->get_field_id($key)); ?>" name="<?php echo \esc_attr($this->get_field_name($key)); ?>" type="text" value="<?php echo \esc_attr($value); ?>">
                    </p>
<?php
                    break;
            }
        }
    }

    protected function beforeWidget($args, $instance)
    {
        // 1) Sanitize instance title (user input)
        $raw_instance_title   = isset($instance['title']) ? $instance['title'] : '';
        $clean_instance_title = wp_kses_post($raw_instance_title);

        // 2) Allow devs to inject HTML via the standard filter
        $filtered_title = apply_filters('widget_title', $clean_instance_title, $instance, $this->id_base);

        // 3) Build allowlists
        $allowed_wrappers = $this->allowed_wrapper_tags();
        $allowed_title    = $this->allowed_title_tags();

        // 4) Output (sanitize on echo)
        $before_widget = isset($args['before_widget']) ? $args['before_widget'] : '';
        echo wp_kses($before_widget, $allowed_wrappers);

        if ('' !== trim($filtered_title))
        {
            $before_title = isset($args['before_title']) ? $args['before_title'] : '';
            $after_title  = isset($args['after_title'])  ? $args['after_title']  : '';

            echo wp_kses($before_title, $allowed_wrappers);
            echo wp_kses($filtered_title, $allowed_title);
            echo wp_kses($after_title,  $allowed_wrappers);
        }
    }

    protected function afterWidget($args)
    {
        $after = isset($args['after_widget']) ? $args['after_widget'] : '';
        echo wp_kses($after, $this->allowed_wrapper_tags());
    }

    protected function allowed_wrapper_tags()
    {
        $allowed = wp_kses_allowed_html('post');

        foreach (array('div', 'span', 'section', 'aside', 'header', 'footer', 'nav', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $tag)
        {
            $allowed[$tag] = array_merge(
                isset($allowed[$tag]) ? $allowed[$tag] : array(),
                array(
                    'class' => true,
                    'id'    => true,
                    'title' => true,
                    'role'  => true,
                )
            );
        }

        return $allowed;
    }

    protected function allowed_title_tags()
    {
        $allowed = wp_kses_allowed_html('post');

        $allowed['svg'] = array(
            'class'        => true,
            'role'         => true,
            'aria-hidden'  => true,
            'focusable'    => true,
            'width'        => true,
            'height'       => true,
            'viewbox'      => true,
            'xmlns'        => true,
            'fill'         => true,
            'stroke'       => true,
            'stroke-width' => true,
        );
        $allowed['g'] = array(
            'fill'         => true,
            'stroke'       => true,
            'stroke-width' => true,
        );
        $allowed['path'] = array(
            'd'               => true,
            'fill'            => true,
            'fill-rule'       => true,
            'clip-rule'       => true,
            'stroke'          => true,
            'stroke-width'    => true,
            'stroke-linecap'  => true,
            'stroke-linejoin' => true,
        );
        $allowed['title'] = array();

        return $allowed;
    }

    protected function allowed_form_tags()
    {
        $allowed = $this->allowed_wrapper_tags();

        $allowed['form'] = array(
            'action'     => true,
            'method'     => true,
            'class'      => true,
            'id'         => true,
            'role'       => true,
            'novalidate' => true,
            'enctype'    => true,
        );
        $allowed['label'] = array('for' => true, 'class' => true);
        $allowed['input'] = array(
            'type'        => true,
            'name'        => true,
            'value'       => true,
            'id'          => true,
            'class'       => true,
            'placeholder' => true,
            'checked'     => true,
            'readonly'    => true,
            'required'    => true,
            'min'         => true,
            'max'         => true,
            'step'        => true,
            'size'        => true,
            'maxlength'   => true,
        );
        $allowed['button'] = array('type' => true, 'name' => true, 'value' => true, 'class' => true, 'id' => true);
        $allowed['select'] = array('name' => true, 'id' => true, 'class' => true, 'multiple' => true, 'size' => true);
        $allowed['option'] = array('value' => true, 'selected' => true);

        return $allowed;
    }
}
