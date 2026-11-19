<?php

// Each file in inc/ owns one feature. Load order matters only for shared helpers.
foreach (['assets', 'icons', 'contents'] as $file) {
    require_once get_theme_file_path("inc/{$file}.php");
}
