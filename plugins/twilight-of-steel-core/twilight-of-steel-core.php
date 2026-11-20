<?php
/**
 * Plugin Name: Twilight of Steel Core
 * Description: Content model for Twilight of Steel: the Codex and its entry types.
 * Version: 1.0.0
 * Requires at least: 7.1.4
 * Requires PHP: 8.4
 * Author: Nick Zou
 * License: GPL-2.0-or-later
 * Text Domain: twilight-of-steel-core
 */

defined('ABSPATH') || exit;

// Composer libraries (Carbon Fields). The project's composer.json installs them
// here, so the plugin that uses them carries them.
require_once __DIR__ . '/vendor/autoload.php';

// Content lives in this plugin, not the theme, so it survives a theme change.
// Each file in inc/ owns one content type or taxonomy.
foreach (['fields', 'codex', 'codex-types', 'timeline'] as $file) {
    require_once __DIR__ . "/inc/{$file}.php";
}

// New post types and taxonomies need their URLs registered once.
register_activation_hook(__FILE__, function () {
    twilight_of_steel_register_codex_types();
    twilight_of_steel_register_codex();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
