<?php
/* Scenario context: Published public page contains this shortcode. Any visitor controls q in the page URL. Shortcode HTML is printed verbatim by the normal content pipeline; no later escaping or CSP blocks script execution. No prior sanitizer modifies q. */
add_shortcode( 'wc_search_label', static function() {
    $query = isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? wp_unslash( $_GET['q'] ) : '';
    return '<span class="wc-search-label">' . $query . '</span>';
} );
