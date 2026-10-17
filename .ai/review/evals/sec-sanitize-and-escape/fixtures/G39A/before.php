<?php
/* Shortcode rendered on a public page. */
add_shortcode( 'wc_search_label', static function() {
    $query = isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? wp_unslash( $_GET['q'] ) : '';
    return '<span class="wc-search-label">' . esc_html( $query ) . '</span>';
} );
