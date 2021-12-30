<?php
namespace Offset;

use Cocur\Slugify\Slugify;

/**
 * Creating and configuring a Gutenberg block
 */
class Block
{
    protected $hook_name = '';
    protected $pack_name = '';
    protected $dir = '';
    protected $url = '';
    protected $editor_styles = array();
    protected $editor_scripts = array();
    protected $styles = array();
    protected $scripts = array();
    protected $render = '';
    protected $wordpress_load_type = '';
    protected $block_config = null;

    /**
     * Init a Gutenberg block
     *
     * @return Block
     */
    public function init()
    {
        add_action('init', array($this, 'hookInit'), 50, 0);

        if (!empty($this->editor_styles) || !empty($this->editor_scripts)) {
            add_action('admin_enqueue_scripts', array($this, 'hookActionAdminEnqueueScripts'), 10, 0);
        }

        if (!empty($this->style) || !empty($this->scripts)) {
            add_action('wp_enqueue_scripts', array($this, 'hookActionWPEnqueueScripts'), 10, 0);
        }

        return $this;
    }

    /**
     * Enter the path of the folder containing the block.json
     *
     * @param string $json_path The path to the block.json file
     * @return boolean
     */
    public function setSettingsFromJSONPath(string $json_path = '')
    {
        if (empty($json_path) || !file_exists($json_path)) {
            return false;
        }

        $this->dir = dirname($json_path);

        $theme_path = get_stylesheet_directory();
        $theme_template = wp_get_theme()->get('Template') ?? '';

        if (is_int(strpos($this->dir, WP_PLUGIN_DIR))) {
            $this->wordpress_load_type = 'plugin';
        } else if (is_int(strpos($this->dir, WPMU_PLUGIN_DIR))) {
            $this->wordpress_load_type = 'mu_plugin';
        } else if (is_int(strpos($this->dir, $theme_path)) && empty($theme_template)) {
            $this->wordpress_load_type = 'theme';
        } else if (is_int(strpos($this->dir, $theme_path)) && !empty($theme_template)) {
            $this->wordpress_load_type = 'child_theme';
        }

        // Set block settings
        try {
            $block_config = file_get_contents($this->dir . DIRECTORY_SEPARATOR . 'block.json');
            $block_config = json_decode($block_config, true);

            if (empty($block_config) || empty($block_config['name'])) {
                return false;
            }

            $this->block_config = (object) $block_config;

            if (!empty($this->block_config)) {
                $this->block_config->editorStyle = $this->block_config->editorStyle ?? '';
                $this->block_config->editorScript = $this->block_config->editorScript ?? '';
                $this->block_config->style = $this->block_config->style ?? '';
                $this->block_config->script = $this->block_config->script ?? '';
            }
        } catch (\Throwable $th) {
            return false;
        }

        // Set hook_name
        $slugify = new Slugify();
        $this->hook_name = $slugify->slugify($this->block_config->name, '_');

        // Set assets
        if (!empty($this->block_config->offset) && !empty($this->block_config->offset['blockUrl'])) {
            if (!empty($this->block_config->editorStyle)) {
                $style_params = $this->block_config->offset['editorStyle'] ?? array();

                $deps = array(
                    'wp-edit-blocks',
                );

                $this->editor_styles = array(
                    array(
                        'key' => $this->hook_name . '_editor_style',
                        'src' => $this->block_config->offset['blockUrl'] . '/' . self::cleanBlockJSONAssetUrl($this->block_config->editorStyle),
                        'deps' => array_merge($deps, $style_params['deps'] ?? array()),
                        'ver' => $style_params['ver'] ?? false,
                        'media' => $style_params['media'] ?? 'all',
                    ),
                );
            }

            if (!empty($this->block_config->editorScript)) {
                $script_params = $this->block_config->offset['editorScript'] ?? array();

                $deps = array(
                    'wp-block-editor',
                    'wp-blocks',
                    'wp-components',
                    'wp-element',
                    'wp-i18n',
                    'wp-editor',
                    'wp-polyfill',
                );

                $this->editor_scripts = array(
                    array(
                        'key' => $this->hook_name . '_editor_script',
                        'src' => $this->block_config->offset['blockUrl'] . '/' . self::cleanBlockJSONAssetUrl($this->block_config->editorScript),
                        'deps' => array_merge($deps, $script_params['deps'] ?? array()),
                        'ver' => $script_params['ver'] ?? false,
                        'in_footer' => $script_params['in_footer'] ?? false,
                    ),
                );
            }

            if (!empty($this->block_config->style)) {
                $style_params = $this->block_config->offset['style'] ?? array();

                $this->styles = array(
                    array(
                        'key' => $this->hook_name . '_style',
                        'src' => $this->block_config->offset['blockUrl'] . '/' . self::cleanBlockJSONAssetUrl($this->block_config->style),
                        'deps' => $style_params['deps'] ?? array(),
                        'ver' => $style_params['ver'] ?? false,
                        'media' => $style_params['media'] ?? 'all',
                    ),
                );
            }

            if (!empty($this->block_config->script)) {
                $script_params = $this->block_config->offset['script'] ?? array();

                $this->scripts = array(
                    array(
                        'key' => $this->hook_name . '_script',
                        'src' => $this->block_config->offset['blockUrl'] . '/' . self::cleanBlockJSONAssetUrl($this->block_config->script),
                        'deps' => $script_params['deps'] ?? array(),
                        'ver' => $script_params['ver'] ?? false,
                        'in_footer' => $script_params['in_footer'] ?? 'all',
                    ),
                );
            }
        }

        // Set render
        $this->block_config->render_callback = array($this, 'renderCallback');

        return true;
    }

    /**
     * Adds the path to the file or function that will be used to display the content of the block
     *
     * @param string|function $render The path to the template file or the function returning the content of the block
     * @return boolean
     */
    public function setRender($render = null)
    {
        if (empty($render)) {
            return false;
        }

        // If no filter render HTML is register, and render settings is file path, get file block render
        if (!empty($render) && is_string($render) && file_exists($render)) {
            $this->render = $render;
        }

        // If no filter render HTML is register, and render settings is function, get function block render
        if (!empty($render) && is_callable($render)) {
            $this->render = $render;
        }

        return true;
    }

    /**
     * Register the Gutenberg block in WordPress
     *
     * @return void
     */
    public function hookInit()
    {
        if (empty($this->block_config)) {
            return false;
        }

        register_block_type($this->block_config->name, $this->block_config);
    }

    /**
     * Adds the style and script of the Gutenberg block to the admin
     *
     * @return void
     */
    public function hookActionAdminEnqueueScripts()
    {
        global $current_screen;

        if (empty($current_screen) || !method_exists($current_screen, 'is_block_editor') || !$current_screen->is_block_editor()) {
            return false;
        }

        foreach ($this->editor_styles as $editor_style) {
            wp_enqueue_style($editor_style['key'], $editor_style['src'], $editor_style['deps'], $editor_style['ver'], $editor_style['media']);
        }

        foreach ($this->editor_scripts as $editor_script) {
            wp_enqueue_script($editor_script['key'], $editor_script['src'], $editor_script['deps'], $editor_script['ver'], $editor_script['in_footer']);
        }
    }

    /**
     * Adds the style and script of the Gutenberg block
     *
     * @return void
     */
    public function hookActionWPEnqueueScripts()
    {
        foreach ($this->styles as $style) {
            wp_enqueue_style($style['key'], $style['src'], $style['deps'], $style['ver'], $style['media']);
        }

        foreach ($this->scripts as $script) {
            wp_enqueue_script($script['key'], $script['src'], $script['deps'], $script['ver'], $script['in_footer']);
        }
    }

    /**
     * Clean the url of the block.json generated by "create-block
     *
     * @param string $block_json_url The url generated by "create-block" in the file block.json
     * @return string
     */
    public static function cleanBlockJSONAssetUrl(string $block_json_url = '')
    {
        $block_json_url = trim($block_json_url, 'file');
        $block_json_url = trim($block_json_url, ':');
        $block_json_url = trim($block_json_url, '.');
        $block_json_url = trim($block_json_url, '/');
        return $block_json_url;
    }

    /**
     * Manages the dynamic rendering of the Gutenberg block
     *
     * @param array $attributes The values saved in the Gutenberg block
     * @param string $content Content saved in the Gutenberg block
     * @return void
     */
    public function renderCallback(array $attributes = array(), string $content = '')
    {
        $html = '';
        $is_style_enqueue = true;
        $is_script_enqueue = true;

        // Filter attributes
        $attributes = apply_filters('offset_block_attributes', $attributes);
        $attributes = apply_filters('offset_block_attributes_' . $this->hook_name, $attributes);

        // Filter content
        $content = apply_filters('offset_block_content', $content);
        $content = apply_filters('offset_block_content_' . $this->hook_name, $content);

        // Filter render HTML by block name
        $filters_render_html_block_name = apply_filters('offset_block_render_' . $this->hook_name, $html, $attributes, $content);

        if (empty($html) && is_string($filters_render_html_block_name) && !empty($filters_render_html_block_name)) {
            $html = $filters_render_html_block_name;
        }

        // If no filter render HTML is register, and render settings is file path, get file block render
        if (empty($html) && !empty($this->render) && is_string($this->render) && file_exists($this->render)) {
            $html = $this->renderFromFile($attributes, $content);
        }

        // If no filter render HTML is register, and render settings is function, get function block render
        if (empty($html) && !empty($this->render) && is_callable($this->render)) {
            $html = $this->render($attributes, $content);
        }

        // Enqueue or not the style with filters function
        $is_style_enqueue_all_blocks = apply_filters('offset_block_is_style_enqueue', true);
        $is_style_enqueue_block_name = apply_filters('offset_block_is_style_enqueue_' . $this->hook_name, true);

        if (is_bool($is_style_enqueue_all_blocks) && !$is_style_enqueue_all_blocks) {
            $is_style_enqueue = false;
        }

        if (is_bool($is_style_enqueue_block_name) && !$is_style_enqueue_block_name) {
            $is_style_enqueue = false;
        }

        if ($is_style_enqueue) {
            wp_enqueue_style($this->hook_name . '_style');
        }

        // Enqueue or not the script with filters function
        $is_script_enqueue_all_blocks = apply_filters('offset_block_is_script_enqueue', true);
        $is_script_enqueue_block_name = apply_filters('offset_block_is_script_enqueue_' . $this->hook_name, true);

        if (is_bool($is_script_enqueue_all_blocks) && !$is_script_enqueue_all_blocks) {
            $is_script_enqueue = false;
        }

        if (is_bool($is_script_enqueue_block_name) && !$is_script_enqueue_block_name) {
            $is_script_enqueue = false;
        }
        if ($is_script_enqueue) {
            wp_enqueue_script($this->hook_name . '_script');
        }

        // If no HTML content is stock, get error render
        if (!is_string($html)) {
            $html = '<div>no render for: ' . $this->name . '</div>';
        }

        return $html;
    }

    /**
     * Manages the dynamic rendering of the Gutenberg block in an external file
     *
     * @param array $attributes The values saved in the Gutenberg block
     * @param string $content Content saved in the Gutenberg block
     * @return string
     */
    private function renderFromFile(array $attributes = array(), string $content = '')
    {
        extract(array($attributes, $content));

        ob_start();

        require $this->render;

        $html = ob_get_contents();
        ob_end_clean();

        return !empty($html) ? $html : '';
    }
}
