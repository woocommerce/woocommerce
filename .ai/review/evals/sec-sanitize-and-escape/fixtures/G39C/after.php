<?php
/* Scenario context: The helper is a callback-local closure, unavailable to extensions, and its only use is shown. The public shortcode is the only sink. Request q is untrusted and can contain markup. Refactor moves escaping from helper to final HTML concatenation without further decoding. */
add_shortcode( 'wc_search_label', static function() { $label_text = static function( $value ) { return $value; }; $q = isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? wp_unslash( $_GET['q'] ) : ''; return '<span>' . esc_html( $label_text( $q ) ) . '</span>'; } );
