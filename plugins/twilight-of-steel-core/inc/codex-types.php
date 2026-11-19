<?php

// Entry types for the Codex: Character, Place, Faction and so on. An entry can
// have more than one, and types can nest.

function twilight_of_steel_register_codex_types(): void
{
    register_taxonomy('codex_type', ['codex'], [
        'labels' => [
            'name' => 'Entry types',
            'singular_name' => 'Entry type',
            'menu_name' => 'Entry types',
            'all_items' => 'All entry types',
            'edit_item' => 'Edit entry type',
            'add_new_item' => 'Add entry type',
            'search_items' => 'Search entry types',
            'not_found' => 'No entry types found.',
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'rewrite' => ['slug' => 'codex/type', 'with_front' => false],
    ]);
}
// Priority 9, ahead of the Codex post type (10): rewrite rules match in
// registration order, and /codex/type/<slug> must be tried before the
// hierarchical /codex/<entry> rule catches it.
add_action('init', 'twilight_of_steel_register_codex_types', 9);
