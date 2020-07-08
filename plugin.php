<?php
/*
Plugin Name:        Text Replacements
Plugin URI:         http://genero.fi
Description:        Replace text
Version:            0.1.0
Author:             Genero
Author URI:         http://genero.fi/
License:            MIT License
License URI:        http://opensource.org/licenses/MIT
*/
namespace GeneroWP\TextReplacements;

use Puc_v4_Factory;
use GeneroWP\Common\Singleton;
use GeneroWP\Common\Assets;

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

class Plugin
{
    use Singleton;
    use Assets;

    public $version = '0.1.0';
    public $plugin_name = 'text-replacements';
    public $plugin_path;
    public $plugin_url;
    public $github_url = 'https://github.com/generoi/text-replacements';

    protected $strings;

    public function __construct()
    {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);

        Puc_v4_Factory::buildUpdateChecker($this->github_url, __FILE__, $this->plugin_name);

        add_action('plugins_loaded', [$this, 'init'], 0);
    }

    public function init()
    {
        $this->loadStrings();

        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'add_options_page']);
        add_filter('gettext', [$this, 'gettext'], 10, 3);
    }

    public function loadStrings()
    {
        $this->strings = get_option('text_replacements', []);
    }

    public function gettext($translation, $text, $domain)
    {
        if (isset($this->strings["$domain:$text"]['replace'])) {
            return $this->strings["$domain:$text"]['replace'];
        }

        return $translation;
    }

    public function add_options_page()
    {
        add_submenu_page(
            'tools.php',
            'Text replacements',
            'Text replacements',
            'manage_options',
            'text-replacements',
            [$this, 'admin_page']
        );
    }

    public function admin_page()
    {
        $finder = new StringFinder();
        $finder->setFileExtensions(apply_filters('text-replacements/scan/extensions', $finder->getFileExtensions()));
        $finder->setDomains(apply_filters('text-replacements/scan/domains', []));
        $strings = $finder->scan(apply_filters('text-replacements/scan/dirs', [
            get_template_directory(),
        ]));

        if (isset($_POST['strings'])) {
            check_admin_referer('text-replacements-nonce');

            $message = 'Changes saved.';
            $values = $this->sanitize_values($_POST['strings']);
            update_option('text_replacements', $values, true);
        } else {
            $values = get_option('text_replacements', []);
        }

        foreach ($values as $id => $value) {
            $id = esc_attr($id);
            if (!isset($strings[$id])) {
                continue;
            }
            $strings[$id]['replace'] = $value['replace'];
        }

        ksort($strings);

        include __DIR__ . '/views/admin.php';
    }

    public function sanitize_values($strings)
    {
        $settings = [];
        if (is_array($strings)) {
            foreach ($strings as $id => $data) {
                if ($data['replace']) {
                    $settings[$id]['replace'] = wp_kses_post(stripslashes($data['replace']));
                }
            }
        }
        return $settings;
    }

    public function load_textdomain()
    {
        load_plugin_textdomain($this->plugin_name, false, $this->plugin_path . '/languages');
    }

    public static function activate()
    {
        foreach ([
            // 'advanced-custom-fields-pro/acf.php' => 'Advanced Custom Fields PRO',
            // 'timber-library/timber.php' => 'Timber Library',
            // 'wp-timber-extended/wp-timber-extended.php' => 'WP Timber Extended',
        ] as $plugin => $name) {
            if (!is_plugin_active($plugin) && current_user_can('activate_plugins')) {
                wp_die(sprintf(
                    __('Sorry, but this plugin requires the %s plugin to be installed and active. <br><a href="%s">&laquo; Return to Plugins</a>', 'wp-hero'),
                    $name,
                    admin_url('plugins.php')
                ));
            }
        }
    }

    public static function deactivate()
    {
    }
}

Plugin::getInstance();
