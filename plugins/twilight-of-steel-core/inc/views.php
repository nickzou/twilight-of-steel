<?php

// Blade templates (BladeOne) for markup rendered from PHP. Templates live in
// views/ as name.blade.php. Compiled copies are cached in wp-content/cache/blade:
// the plugin folder isn't writable in local dev, and deploys start the cache
// fresh. MODE_AUTO recompiles a template whenever it changes.

use eftec\bladeone\BladeOne;

function twilight_view(string $view, array $data = []): string
{
    static $blade = null;

    if ($blade === null) {
        // BladeOne only creates its folders from its CLI.
        $cache = WP_CONTENT_DIR . '/cache/blade';
        wp_mkdir_p($cache);

        $blade = new BladeOne(dirname(__DIR__) . '/views', $cache, BladeOne::MODE_AUTO);
        // Fail like any PHP error, rather than printing the error into the page.
        $blade->throwOnError = true;
    }

    return $blade->run($view, $data);
}
