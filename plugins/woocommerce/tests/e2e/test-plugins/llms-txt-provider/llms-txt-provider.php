<?php
/**
 * Plugin Name: E2E llms.txt provider
 * Description: Provides a template-based llms.txt for integration tests.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

if ( did_action( 'template_redirect' ) ) {
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "# Existing llms.txt provider\n";
	return;
}

add_filter(
	'template_include',
	static function ( $template ) {
		global $wp;
		return isset( $wp ) && 'llms.txt' === $wp->request ? __FILE__ : $template;
	}
);
