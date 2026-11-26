<?php
/**
 * Plugin Name: Twilight of Steel Core
 * Description: Content model for Twilight of Steel: the Codex and the comics.
 * Version: 1.0.0
 * Requires at least: 7.1.4
 * Requires PHP: 8.4
 * Author: Nick Zou
 * License: GPL-2.0-or-later
 * Text Domain: twilight-of-steel-core
 */

defined('ABSPATH') || exit;

// Composer libraries (Carbon Fields, BladeOne). The project's composer.json
// installs them here, so the plugin that uses them carries them.
require_once __DIR__ . '/vendor/autoload.php';

// Content lives in this plugin, not the theme, so it survives a theme change.
// Each file in inc/ owns one content type or taxonomy.
foreach (['fields', 'views', 'codex', 'codex-types', 'comic-chapters', 'comic-series', 'timeline', 'autosave', 'blocks'] as $file) {
    require_once __DIR__ . "/inc/{$file}.php";
}

// Rebuild the URL rules whenever the post types or taxonomies change them, so
// a deploy that adds or moves one works without a manual flush. The rules
// are fingerprinted once everything has registered on init.
add_action('init', function () {
    global $wp_rewrite;

    $fingerprint = md5(serialize([$wp_rewrite->extra_permastructs, $wp_rewrite->rewritecode]));
    if (get_option('twilight_rewrite_fingerprint') !== $fingerprint) {
        flush_rewrite_rules(false);
        update_option('twilight_rewrite_fingerprint', $fingerprint);
    }
}, PHP_INT_MAX);

// Activation runs after init, so rebuild the rules on the next request.
register_activation_hook(__FILE__, function () {
    delete_option('twilight_rewrite_fingerprint');
});

register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
