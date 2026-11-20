<?php

// Cache-busting version for a theme file: its modification time.
function twilight_of_steel_asset_version(string $file): int
{
    return filemtime(get_theme_file_path($file));
}

add_action('wp_enqueue_scripts', function () {
    foreach (['assets/css/main.css'] as $file) {
        wp_enqueue_style(
            'twilight-of-steel-' . basename($file, '.css'),
            get_theme_file_uri($file),
            [],
            twilight_of_steel_asset_version($file)
        );
    }
});

// Alpine.js, for declarative interactive pieces. Registered for any feature to
// enqueue; it loads only on pages that use it. Registered on init because
// block templates render, and enqueue, before wp_enqueue_scripts runs.
add_action('init', function () {
    wp_register_script(
        'alpinejs',
        get_theme_file_uri('assets/js/vendor/alpine.min.js'),
        [],
        twilight_of_steel_asset_version('assets/js/vendor/alpine.min.js'),
        ['strategy' => 'defer']
    );
});

// Alpine starts as soon as it runs, so the components a page registers with
// Alpine.data have to load before it. Move it to the end of the queue, after
// every feature has enqueued its component.
add_action('wp_enqueue_scripts', function () {
    if (wp_script_is('alpinejs')) {
        wp_dequeue_script('alpinejs');
        wp_enqueue_script('alpinejs');
    }
}, 100);
