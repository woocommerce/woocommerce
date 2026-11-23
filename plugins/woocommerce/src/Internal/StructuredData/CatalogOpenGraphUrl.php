<?php
/**
 * Catalog Open Graph URLs.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use Automattic\WooCommerce\Blocks\Utils\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the current catalog URL for Open Graph metadata.
 *
 * @internal
 */
class CatalogOpenGraphUrl {
	/**
	 * Get the current URL without the add-to-cart action or private share key.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public function get_url(): string {
		$url   = Utils::get_current_page_url();
		$parts = explode( '?', $url, 2 );
		if ( ! isset( $parts[1] ) ) {
			return $url;
		}
		$params = array_filter(
			explode( '&', $parts[1] ),
			static function ( $param ) {
				return ! in_array( urldecode( explode( '=', $param, 2 )[0] ), array( 'add-to-cart', 'woo-share' ), true );
			}
		);
		return $parts[0] . ( $params ? '?' . implode( '&', $params ) : '' );
	}
}
