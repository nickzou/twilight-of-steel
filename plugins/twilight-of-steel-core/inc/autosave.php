<?php

// Editor fix: edits made after an autosave weren't flagged as unsaved, which
// hit Carbon Fields blocks in particular. See assets/js/autosave-edits.js.

add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'twilight-autosave-edits',
        plugins_url('assets/js/autosave-edits.js', __DIR__),
        ['wp-data', 'wp-editor', 'wp-block-editor'],
        filemtime(dirname(__DIR__) . '/assets/js/autosave-edits.js'),
        true
    );
});
