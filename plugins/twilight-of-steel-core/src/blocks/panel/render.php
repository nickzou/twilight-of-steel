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
$aspect = preg_replace('#[^0-9./ ]#', '', $attributes['aspectRatio'] ?? '4/3');

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

// Bubbles are stored in stage units: the stage is 1000 wide (STAGE_WIDTH in
// bubbles.tsx) and as tall as the panel's shape makes it. Turn them into
// percentages of the panel, and the font size into container units (1cqw is
// a hundredth of the panel's width), so they scale with it.
[$ratio_w, $ratio_h] = array_map('floatval', explode('/', $aspect) + [1, 1]);
$stage_height = $ratio_w > 0 && $ratio_h > 0 ? 1000 * $ratio_h / $ratio_w : 1000;
$percent = fn (float $value, float $of) => round($value / $of * 100, 3) . '%';

$bubbles = [];
foreach ($attributes['bubbles'] ?? [] as $bubble) {
    $text = trim(wp_kses($bubble['text'] ?? '', ['strong' => [], 'em' => [], 'b' => [], 'i' => [], 'br' => []]));
    if ($text === '') {
        continue;
    }
    $bubbles[] = [
        'class' => 'twilight-bubble is-' . sanitize_html_class($bubble['spec']['style'] ?? 'speech'),
        'style' => sprintf(
            'left: %s; top: %s; width: %s; font-size: %scqw',
            $percent((float) ($bubble['x'] ?? 0), 1000),
            $percent((float) ($bubble['y'] ?? 0), $stage_height),
            $percent((float) ($bubble['width'] ?? 280), 1000),
            round((float) ($bubble['size'] ?? 28) / 10, 3)
        ),
        'text' => $text,
    ];
}

echo twilight_view('blocks.panel', [
    'wrapper' => get_block_wrapper_attributes(['style' => "aspect-ratio: {$aspect}"]),
    'image' => $image,
    'bubbles' => $bubbles,
    'outlines' => $bubbles ? twilight_sanitize_bubble_outlines($attributes['outlines'] ?? '') : '',
]);
