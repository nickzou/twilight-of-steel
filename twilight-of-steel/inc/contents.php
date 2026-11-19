<?php

// "Contents" menu for codex entries: a dropdown linking to each h2 in the
// entry. It is a <details> element, so it opens and closes without JavaScript;
// Alpine only closes it after a jump or a tap outside it.

add_filter('render_block_core/post-content', function (string $content): string {
    if (!is_singular('codex')) {
        return $content;
    }

    $headings = [];
    $used_ids = [];
    $html = new WP_HTML_Tag_Processor($content);

    while ($html->next_tag('H2')) {
        $html->set_bookmark('heading');

        // Collect the heading's text, including text inside links or emphasis.
        $text = '';
        while ($html->next_token() && !($html->get_tag() === 'H2' && $html->is_tag_closer())) {
            if ($html->get_token_type() === '#text') {
                $text .= $html->get_modifiable_text();
            }
        }
        $text = trim($text);

        $html->seek('heading');
        if ($text === '') {
            continue;
        }

        // Keep an anchor set in the editor; otherwise make one from the text.
        $id = $html->get_attribute('id');
        if (!is_string($id) || $id === '') {
            $base = sanitize_title($text);
            $id = $base;
            for ($n = 2; isset($used_ids[$id]); $n++) {
                $id = "{$base}-{$n}";
            }
            $html->set_attribute('id', $id);
        }
        $used_ids[$id] = true;
        $headings[] = [$id, $text];
    }
    if ($html->has_bookmark('heading')) {
        $html->release_bookmark('heading');
    }

    // A single section has nowhere to jump to.
    if (count($headings) < 2) {
        return $content;
    }

    wp_enqueue_script('alpinejs');

    $items = '';
    foreach ($headings as [$id, $text]) {
        $items .= sprintf('<li><a href="#%s" @click="$root.open = false">%s</a></li>', esc_attr($id), esc_html($text));
    }

    return sprintf(
        '<details class="codex-contents" x-data @click.outside="$el.open = false"><summary>%s</summary><nav aria-label="%s"><ol>%s</ol></nav></details>',
        esc_html__('Contents', 'twilight-of-steel'),
        esc_attr__('Sections', 'twilight-of-steel'),
        $items
    ) . $html->get_updated_html();
});
