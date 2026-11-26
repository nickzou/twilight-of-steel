<?php

// Comic titles and their volumes, as one hierarchical taxonomy: a title is a
// top-level term and its volumes are its children. Chapters are filed under
// their volume (comic-chapters.php). Its archives are each title's and
// volume's table of contents, at /comics/<title>/ and /comics/<title>/<volume>/.

use Carbon_Fields\Container;
use Carbon_Fields\Field;

function twilight_register_comic_series(): void
{
    register_taxonomy('comic_series', ['comic_chapter'], [
        'labels' => [
            'name' => 'Titles & volumes',
            'singular_name' => 'Title or volume',
            'menu_name' => 'Titles & volumes',
            'all_items' => 'All titles & volumes',
            'edit_item' => 'Edit title or volume',
            'view_item' => 'View title or volume',
            'add_new_item' => 'Add title or volume',
            'parent_item' => 'Title',
            'parent_item_colon' => 'Title:',
            'search_items' => 'Search titles & volumes',
            'not_found' => 'No titles or volumes found.',
            'back_to_items' => '← Back to titles & volumes',
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'rewrite' => ['slug' => 'comics', 'with_front' => false, 'hierarchical' => true],
    ]);
}
// After the chapter post type (9): its /comics/<title>/<volume>/<chapter> rule
// must be tried before this taxonomy's catch-all /comics/<path> rule.
add_action('init', 'twilight_register_comic_series');

add_action('carbon_fields_register_fields', function () {
    Container::make('term_meta', __('Comic details', 'twilight-of-steel-core'))
        ->where('term_taxonomy', '=', 'comic_series')
        ->add_fields([
            Field::make('image', 'comic_cover', __('Cover', 'twilight-of-steel-core')),
            // Terms have no order of their own, and by name "Volume 10" sorts
            // before "Volume 2".
            Field::make('text', 'comic_order', __('Order', 'twilight-of-steel-core'))
                ->set_attribute('type', 'number')
                ->set_help_text(__('Titles and volumes are listed by this number, lowest first.', 'twilight-of-steel-core')),
        ]);
});
