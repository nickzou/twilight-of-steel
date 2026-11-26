<?php
/**
 * Panel block, rendered by views/blocks/panel.blade.php.
 *
 * @var array    $attributes
 * @var WP_Block $block
 */

$id = (int) ($attributes['id'] ?? 0);
$focal = $attributes['focalPoint'] ?? ['x' => 0.5, 'y' => 0.5];
$position = sprintf('%s%% %s%%', round($focal['x'] * 100, 2), round($focal['y'] * 100, 2));

// The image's rendered width, for srcset: its share of the page grid on wide
// screens, the full screen on phones (where panels stack).
$columns = max(1, (int) ($block->context['twilight/pageLayout']['columnCount'] ?? 6));
$span = min($columns, max(1, (int) ($attributes['style']['layout']['columnSpan'] ?? $columns)));
$sizes = sprintf('(width <= 480px) 100vw, %dpx', ceil(1200 * $span / $columns));

$image = $id ? wp_get_attachment_image($id, 'full', false, array_filter([
    'alt' => $attributes['alt'] ?: null,
    'sizes' => $sizes,
    'style' => "object-position: {$position}",
    'loading' => 'lazy',
])) : '';

echo twilight_view('blocks.panel', [
    'wrapper' => get_block_wrapper_attributes([
        'style' => 'aspect-ratio: ' . preg_replace('#[^0-9./ ]#', '', $attributes['aspectRatio'] ?? '4/3'),
    ]),
    'image' => $image,
]);
