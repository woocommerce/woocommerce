<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * Shared helpers for the UCP (Universal Commerce Protocol) routes.
 */
class UcpUtils {
	/**
	 * UCP specification version these routes implement.
	 */
	const VERSION = '2026-04-08';

	/**
	 * Whether the UCP routes are registered.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		/**
		 * Filters whether the UCP (Universal Commerce Protocol) routes are registered.
		 *
		 * @since 11.3.0
		 *
		 * @param bool $enabled Whether the routes are registered. Default false.
		 */
		return (bool) apply_filters( 'woocommerce_ucp_enabled', false );
	}

	/**
	 * Build the `ucp` envelope every UCP response carries.
	 *
	 * @param string|null $status Response status, omitted from the envelope when null.
	 * @return array
	 */
	public static function response_metadata( ?string $status = null ): array {
		$metadata = array(
			'version'      => self::VERSION,
			'capabilities' => array(
				'dev.ucp.shopping.cart'           => array(
					array(
						'version' => self::VERSION,
						'spec'    => 'https://ucp.dev/2026-04-08/specification/cart',
						'schema'  => 'https://ucp.dev/2026-04-08/schemas/shopping/cart.json',
					),
				),
				'dev.ucp.shopping.catalog.search' => array(
					array(
						'version' => self::VERSION,
						'spec'    => 'https://ucp.dev/2026-04-08/specification/catalog/search',
						'schema'  => 'https://ucp.dev/2026-04-08/schemas/shopping/catalog_search.json',
					),
				),
				'dev.ucp.shopping.catalog.lookup' => array(
					array(
						'version' => self::VERSION,
						'spec'    => 'https://ucp.dev/2026-04-08/specification/catalog/lookup',
						'schema'  => 'https://ucp.dev/2026-04-08/schemas/shopping/catalog_lookup.json',
					),
				),
			),
		);

		if ( null !== $status ) {
			$metadata['status'] = $status;
		}

		return $metadata;
	}
}
