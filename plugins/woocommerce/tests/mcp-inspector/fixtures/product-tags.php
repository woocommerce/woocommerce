<?php
/**
 * Product tags whose names equal their slugs.
 *
 * @package WooCommerce\Tests\McpInspector
 */

declare( strict_types = 1 );

/**
 * @return list<string> The tag slugs.
 */
return static function (): array {
	$slugs = array( 'mcp-alpha', 'mcp-beta', 'mcp-gamma' );

	foreach ( $slugs as $slug ) {
		if ( ! term_exists( $slug, 'product_tag' ) ) {
			wp_insert_term( $slug, 'product_tag', array( 'slug' => $slug ) );
		}
	}

	return $slugs;
};
