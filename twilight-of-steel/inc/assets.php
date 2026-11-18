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
