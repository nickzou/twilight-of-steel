<?php

// Server side of the comic blocks (src/blocks/comic-page, src/blocks/panel).

// The blocks' default style holds their phone layout: pages one column wide,
// panels one column each; the page's default layout is its desktop grid. The
// editor applies attribute defaults, but a block saved with its defaults
// doesn't store them, and WordPress builds layout CSS from the stored
// attributes alone. Fill the defaults in before it does.
add_filter('render_block_data', function (array $block): array {
    if (!in_array($block['blockName'] ?? '', ['twilight/comic-page', 'twilight/panel'], true)) {
        return $block;
    }

    $type = WP_Block_Type_Registry::get_instance()->get_registered($block['blockName']);
    foreach (['style', 'layout'] as $name) {
        if (!isset($block['attrs'][$name]) && isset($type?->attributes[$name]['default'])) {
            $block['attrs'][$name] = $type->attributes[$name]['default'];
        }
    }

    return $block;
});

// Comic pages belong in chapters only.
add_filter('allowed_block_types_all', function (array|bool $allowed, WP_Block_Editor_Context $context): array|bool {
    if ($context->post?->post_type === 'comic_chapter') {
        return $allowed;
    }

    if ($allowed === true) {
        $allowed = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return is_array($allowed)
        ? array_values(array_diff($allowed, ['twilight/comic-page', 'twilight/panel']))
        : $allowed;
}, 10, 2);
