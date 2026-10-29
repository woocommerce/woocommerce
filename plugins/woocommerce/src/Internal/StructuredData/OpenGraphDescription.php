<?php
/**
 * Open Graph description formatting.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

defined( 'ABSPATH' ) || exit;

/**
 * Cleans text for an Open Graph description.
 *
 * @internal
 */
class OpenGraphDescription {
	/**
	 * Remove markup and normalize whitespace.
	 *
	 * @param string $description Source text.
	 * @return string
	 */
	public function clean( string $description ): string {
		$description = wp_strip_all_tags( strip_shortcodes( $description ) );
		return trim( (string) preg_replace( '/\s+/u', ' ', $description ) );
	}
}
