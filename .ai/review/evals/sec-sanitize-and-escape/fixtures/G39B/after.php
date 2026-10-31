<?php
/* Shortcode rendered on a public page. */
add_shortcode( 'wc_entity_notice', static function() { $notice = isset( $_GET['notice'] ) && is_string( $_GET['notice'] ) ? wp_unslash( $_GET['notice'] ) : ''; $notice = wp_kses_post( $notice ); return '<div class="wc-notice">' . html_entity_decode( $notice, ENT_QUOTES, 'UTF-8' ) . '</div>'; } );
