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
use GeneroWP\TextReplacements\Plugin;

defined('ABSPATH') or die();

if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

Plugin::getInstance();
