<?php

// Custom blocks built from src/blocks/<name>/ (block.json plus TypeScript/TSX) into
// build/ by @wordpress/scripts: `pnpm build`, or `pnpm start` to rebuild on
// save. build/ isn't committed; the Docker image builds it, and locally it
// needs a build after pulling. Carbon Fields blocks (like the timeline) are
// registered in their own files instead.

add_action('init', function () {
    $manifest = dirname(__DIR__) . '/build/blocks-manifest.php';
    if (!file_exists($manifest)) {
        return;
    }

    // One call registers every block in the manifest wp-scripts writes.
    wp_register_block_types_from_metadata_collection(dirname(__DIR__) . '/build/blocks', $manifest);
});
