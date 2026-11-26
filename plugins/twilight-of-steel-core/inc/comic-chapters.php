<?php

// Comic chapters: one post per chapter, filed under its volume in
// comic-series.php. The chapter's pages and panels will be blocks in its
// content. Chapters live at /comics/<title>/<volume>/<chapter>/ and are read
// in chapter-number order.

use Carbon_Fields\Container;
use Carbon_Fields\Field;

function twilight_register_comic_chapters(): void
{
    register_post_type('comic_chapter', [
        'labels' => [
            'name' => 'Chapters',
            'singular_name' => 'Chapter',
            'menu_name' => 'Comics',
            'all_items' => 'All chapters',
            'add_new_item' => 'Add chapter',
            'edit_item' => 'Edit chapter',
            'new_item' => 'New chapter',
            'view_item' => 'View chapter',
            'search_items' => 'Search chapters',
            'not_found' => 'No chapters found.',
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-format-gallery',
        'menu_position' => 21,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'rewrite' => ['slug' => 'comics/%comic_series%', 'with_front' => false],
    ]);
}
// Priority 9, ahead of the comic_series taxonomy (10): see comic-series.php.
add_action('init', 'twilight_register_comic_chapters', 9);

// The chapter number sets reading order. The editor no longer shows the
// built-in Order field, so it's a field of its own, copied into menu_order on
// save so archives can sort on a column rather than a meta value.
add_action('carbon_fields_register_fields', function () {
    Container::make('post_meta', __('Chapter', 'twilight-of-steel-core'))
        ->where('post_type', '=', 'comic_chapter')
        ->set_context('side')
        ->add_fields([
            Field::make('text', 'chapter_number', __('Chapter number', 'twilight-of-steel-core'))
                ->set_attribute('type', 'number')
                ->set_help_text(__('Chapters in a volume are read in this order.', 'twilight-of-steel-core')),
        ]);
});

add_action('carbon_fields_post_meta_container_saved', function (int $post_id) {
    if (get_post_type($post_id) !== 'comic_chapter') {
        return;
    }

    global $wpdb;
    $number = (int) carbon_get_post_meta($post_id, 'chapter_number');
    // Not wp_update_post(): that would save the post again, and with it every
    // field container.
    $wpdb->update($wpdb->posts, ['menu_order' => $number], ['ID' => $post_id]);
    clean_post_cache($post_id);
});

// Fill %comic_series% in a chapter's URL with its volume's path, e.g.
// twilight-of-steel/volume-1. A chapter in several terms uses the deepest.
add_filter('post_type_link', function (string $link, WP_Post $post): string {
    if ($post->post_type !== 'comic_chapter' || !str_contains($link, '%comic_series%')) {
        return $link;
    }

    $path = 'unfiled';
    $deepest = -1;
    foreach (get_the_terms($post, 'comic_series') ?: [] as $term) {
        $ancestors = get_ancestors($term->term_id, 'comic_series', 'taxonomy');
        if (count($ancestors) > $deepest) {
            $deepest = count($ancestors);
            $slugs = array_map(
                fn (int $id) => get_term($id, 'comic_series')->slug,
                array_reverse($ancestors)
            );
            $path = implode('/', [...$slugs, $term->slug]);
        }
    }

    return str_replace('%comic_series%', $path, $link);
}, 10, 2);

// Chapters and volumes share the /comics/ prefix, so the chapter rule also
// catches volume URLs: /comics/<title>/<volume>/ reads as title + chapter
// "<volume>". When no chapter has that slug, hand the whole path back to the
// taxonomy. When one does, drop the title/volume from the query, so a chapter
// still loads if it's moved; the redirect below then sends it to its current URL.
add_filter('request', function (array $vars): array {
    if (!isset($vars['comic_chapter'], $vars['comic_series'])) {
        return $vars;
    }

    if (get_page_by_path($vars['comic_chapter'], OBJECT, 'comic_chapter')) {
        unset($vars['comic_series']);
        return $vars;
    }

    $path = $vars['comic_series'];
    $last = $vars['comic_chapter'];
    $vars = array_diff_key($vars, array_flip(['comic_chapter', 'comic_series', 'post_type', 'name', 'page']));

    // /comics/<title>/feed/ is the title's feed, not a chapter called "feed".
    if ($last === 'feed') {
        return [...$vars, 'comic_series' => $path, 'feed' => 'feed'];
    }

    return [...$vars, 'comic_series' => "{$path}/{$last}"];
});

// A chapter reached through an old or wrong title/volume path redirects to
// its URL. WordPress's own canonical redirect doesn't compare pretty paths.
add_action('template_redirect', function () {
    // Leave split chapters' later pages (/2/) and feeds to WordPress.
    if (!is_singular('comic_chapter') || is_preview() || is_feed() || get_query_var('page')) {
        return;
    }

    $canonical = get_permalink();
    $requested = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    if ($canonical && untrailingslashit($requested) !== untrailingslashit((string) wp_parse_url($canonical, PHP_URL_PATH))) {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        wp_safe_redirect($canonical . ($query === '' ? '' : "?{$query}"), 301);
        exit;
    }
});

// A title's or volume's archive lists its chapters in reading order, all on
// one page.
add_action('pre_get_posts', function (WP_Query $query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_tax('comic_series')) {
        return;
    }

    $query->set('orderby', ['menu_order' => 'ASC', 'date' => 'ASC']);
    $query->set('posts_per_page', -1);
});
