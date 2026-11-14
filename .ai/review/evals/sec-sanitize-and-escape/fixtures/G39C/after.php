<?php
/* Shortcode rendered on a public page; closure is local to this callback. */
add_shortcode( 'wc_search_label', static function() { $label_text = static function( $value ) { return $value; }; $q = isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? wp_unslash( $_GET['q'] ) : ''; return '<span>' . esc_html( $label_text( $q ) ) . '</span>'; } );
