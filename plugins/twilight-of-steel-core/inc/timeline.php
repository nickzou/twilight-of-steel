<?php

// Timeline block: a vertical list of events, in the order they're entered.
// Dates are free text in the world's own calendar ("Year 312, Third Age"),
// so nothing is parsed or sorted. Built with Carbon Fields: the editor shows
// the fields as a form, and this file's render callback draws the block.

use Carbon_Fields\Block;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {
    // Registered here, not on init: Carbon Fields registers fields early in
    // init, and set_style() needs the handle to exist already.
    wp_register_style(
        'twilight-of-steel-timeline',
        plugins_url('assets/css/timeline.css', __DIR__),
        [],
        filemtime(dirname(__DIR__) . '/assets/css/timeline.css')
    );

    Block::make('timeline', __('Timeline', 'twilight-of-steel-core'))
        ->set_description(__('Events in order, each with a date in the world’s own calendar.', 'twilight-of-steel-core'))
        ->set_category('widgets')
        ->set_icon('backup')
        ->set_keywords([__('history', 'twilight-of-steel-core'), __('events', 'twilight-of-steel-core')])
        ->set_style('twilight-of-steel-timeline')
        ->add_fields([
            Field::make('complex', 'events', __('Events', 'twilight-of-steel-core'))
                ->set_layout('tabbed-vertical')
                ->setup_labels([
                    'plural_name' => __('Events', 'twilight-of-steel-core'),
                    'singular_name' => __('Event', 'twilight-of-steel-core'),
                ])
                ->set_header_template('<%- date || "New event" %>')
                ->add_fields([
                    Field::make('text', 'date', __('Date', 'twilight-of-steel-core'))
                        ->set_help_text(__('As readers see it, e.g. “Year 312, Third Age”.', 'twilight-of-steel-core')),
                    Field::make('text', 'title', __('Title', 'twilight-of-steel-core'))
                        ->set_required(true),
                    Field::make('text', 'description', __('Description', 'twilight-of-steel-core')),
                    Field::make('association', 'entry', __('Codex entry', 'twilight-of-steel-core'))
                        ->set_help_text(__('Optional: the title links to this entry.', 'twilight-of-steel-core'))
                        ->set_types([['type' => 'post', 'post_type' => 'codex']])
                        ->set_max(1),
                ]),
        ])
        ->set_render_callback('twilight_of_steel_render_timeline');
});

function twilight_of_steel_render_timeline(array $fields, array $attributes): void
{
    $events = array_filter($fields['events'] ?? [], fn($event) => ($event['title'] ?? '') !== '');
    if (!$events) {
        return;
    }

    $class = trim('timeline ' . ($attributes['className'] ?? ''));
?>
    <ol class="<?= esc_attr($class) ?>">
        <?php foreach ($events as $event) :
            $entry_id = (int) ($event['entry'][0]['id'] ?? 0);
            $url = $entry_id ? get_permalink($entry_id) : '';
        ?>
            <li class="timeline-event">
                <?php if (($event['date'] ?? '') !== '') : ?>
                    <p class="timeline-date"><?= esc_html($event['date']) ?></p>
                <?php endif; ?>
                <h3 class="timeline-title">
                    <?php if ($url) : ?>
                        <a href="<?= esc_url($url) ?>"><?= esc_html($event['title']) ?></a>
                    <?php else : ?>
                        <?= esc_html($event['title']) ?>
                    <?php endif; ?>
                </h3>
                <?php if (($event['description'] ?? '') !== '') : ?>
                    <div class="timeline-description"><?= wp_kses_post(wpautop($event['description'])) ?></div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
<?php
}
