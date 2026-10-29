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
	 * Get the current URL without the add-to-cart action.
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
				return 'add-to-cart' !== urldecode( explode( '=', $param, 2 )[0] );
			}
		);
		return $parts[0] . ( $params ? '?' . implode( '&', $params ) : '' );
	}
}
