<?php

namespace GeneroWP\TextReplacements;

class Plugin
{


    /** @var array<string,array{search:string,replace:?string,domain:string}> $strings */
    protected array $strings;

    protected static Plugin $instance;

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'init'], 0);
    }

    public function init(): void
    {
        $this->loadStrings();

        add_action('admin_menu', [$this, 'add_options_page']);
        add_filter('gettext', [$this, 'gettext'], 10, 3);
        add_filter('ngettext', [$this, 'ngettext'], 10, 5);
    }

    public function loadStrings(): void
    {
        $this->strings = get_option('text_replacements', []);
    }

    public function gettext(string $translation, string $text, string $domain): string
    {
        if (isset($this->strings["$domain:$text"]['replace'])) {
            return $this->strings["$domain:$text"]['replace'];
        }

        return $translation;
    }

    public function ngettext(string $translation, string $single, string $plural, int $number, string $domain): string
    {
        $text = $number === 1 ? $single : $plural;
        if (isset($this->strings["$domain:$text"]['replace'])) {
            return $this->strings["$domain:$text"]['replace'];
        }

        return $translation;
    }

    public function add_options_page(): void
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

    public function admin_page(): void
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

        include dirname(__DIR__) . '/views/admin.php';
    }

    /**
     * @param array<string,array{search:string,replace:?string,domain:string}> $strings
     * @return array<string,array{replace:string}>
     */
    public function sanitize_values(array $strings): array
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
}
