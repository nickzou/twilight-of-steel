<?php

// Lucide icons (assets/icons, copied from lucide-static by scripts/vendor.js),
// printed inline so they take the text colour. Decorative: hidden from screen
// readers, so the text beside an icon has to carry its meaning.
function twilight_icon(string $name): string
{
    static $cache = [];

    if (!isset($cache[$name])) {
        $svg = file_get_contents(get_theme_file_path("assets/icons/{$name}.svg"));
        if ($svg === false) {
            return '';
        }

        // Drop the licence comment; the licence ships in assets/icons/LICENSE.
        $html = new WP_HTML_Tag_Processor(preg_replace('/<!--.*?-->/s', '', $svg));
        $html->next_tag('svg');
        $html->set_attribute('aria-hidden', 'true');
        $html->set_attribute('focusable', 'false');
        $cache[$name] = trim($html->get_updated_html());
    }

    return $cache[$name];
}
