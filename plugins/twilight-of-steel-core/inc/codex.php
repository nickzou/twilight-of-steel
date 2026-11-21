<?php

// The Codex: the world-building reference. Entries are hierarchical, so a place
// can sit under its region. Entry types live in codex-types.php.

function twilight_register_codex(): void
{
    register_post_type('codex', [
        'labels' => [
            'name' => 'Codex',
            'singular_name' => 'Entry',
            'menu_name' => 'Codex',
            'all_items' => 'All entries',
            'add_new_item' => 'Add entry',
            'edit_item' => 'Edit entry',
            'new_item' => 'New entry',
            'view_item' => 'View entry',
            'search_items' => 'Search the Codex',
            'not_found' => 'No entries found.',
            'parent_item_colon' => 'Parent entry:',
            'archives' => 'Codex',
        ],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-book-alt',
        'menu_position' => 20,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions'],
        'has_archive' => 'codex',
        'rewrite' => ['slug' => 'codex', 'with_front' => false],
    ]);
}
add_action('init', 'twilight_register_codex');

// The Codex reads like a reference: alphabetical, and long enough per page to browse.
add_action('pre_get_posts', function (WP_Query $query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->is_post_type_archive('codex') || $query->is_tax('codex_type')) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
        $query->set('posts_per_page', 50);
    }
});
