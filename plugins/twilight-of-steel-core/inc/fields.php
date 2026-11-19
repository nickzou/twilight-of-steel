<?php

// Carbon Fields: custom fields defined in code. Field containers go in the
// file of the content type they belong to, registered on the
// carbon_fields_register_fields action.
add_action('after_setup_theme', function () {
    \Carbon_Fields\Carbon_Fields::boot();
});
