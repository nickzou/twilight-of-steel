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

// A panel's bubble outlines are an SVG drawn in the editor by comical-js and
// saved as an attribute. Keep only drawing elements and attributes, so a
// crafted attribute can't carry script onto the page.
function twilight_sanitize_bubble_outlines(string $svg): string
{
    $attributes = array_fill_keys([
        'id', 'class', 'd', 'points', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'width', 'height', 'transform', 'opacity', 'visibility',
        'fill', 'fill-opacity', 'fill-rule', 'clip-rule', 'clip-path',
        'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap', 'stroke-linejoin',
        'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset',
        'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform',
        'mix-blend-mode', 'style',
    ], true);
    $svg_attributes = $attributes + array_fill_keys([
        'xmlns', 'xmlns:xlink', 'version', 'viewbox', 'preserveaspectratio',
    ], true);

    $allowed = ['svg' => $svg_attributes];
    foreach (['g', 'path', 'defs', 'lineargradient', 'radialgradient', 'stop', 'rect', 'circle',
        'ellipse', 'polygon', 'polyline', 'line', 'clippath'] as $element) {
        $allowed[$element] = $attributes;
    }

    return wp_kses($svg, $allowed);
}

// comical-js draws speech bubbles in the editor (src/blocks/panel/bubbles.tsx).
// It has to run inside the editor's canvas iframe, where the panels are:
// paper.js, which it draws with, only accepts a <canvas> from its own window,
// and otherwise quietly draws on a detached one. Block assets are loaded into
// the iframe, so it goes there; the Panel block looks it up on the iframe's
// window. Readers never get it: the site shows the exported SVG.
add_action('enqueue_block_assets', function () {
    if (!is_admin() || get_current_screen()?->post_type !== 'comic_chapter') {
        return;
    }

    $file = dirname(__DIR__) . '/assets/js/vendor/comical.js';
    wp_enqueue_script('comicaljs', plugins_url('assets/js/vendor/comical.js', __DIR__), [], (string) filemtime($file));
});
