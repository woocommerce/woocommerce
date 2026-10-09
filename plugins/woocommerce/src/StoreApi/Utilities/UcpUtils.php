<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Shared helpers for the UCP (Universal Commerce Protocol) routes.
 */
class UcpUtils {
	/**
	 * UCP specification version these routes implement.
	 */
	const VERSION = '2026-04-08';

	/**
	 * Feature id gating the UCP routes, stored as `woocommerce_feature_ucp_enabled`.
	 */
	const FEATURE_ID = 'ucp';

	/**
	 * Capability served by `POST /catalog/search`.
	 */
	const CAPABILITY_CATALOG_SEARCH = 'dev.ucp.shopping.catalog.search';

	/**
	 * Capability served by `POST /catalog/lookup` and `POST /catalog/product`.
	 */
	const CAPABILITY_CATALOG_LOOKUP = 'dev.ucp.shopping.catalog.lookup';

	/**
	 * Whether the UCP routes are registered.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}

	/**
	 * Build the `ucp` envelope every UCP response carries.
	 *
	 * UCP requires `capabilities` to name only the capability relevant to the
	 * operation being answered, so each response declares exactly one.
	 *
	 * @param string      $capability One of the CAPABILITY_* constants.
	 * @param string|null $status Response status, omitted from the envelope when null.
	 * @return array
	 */
	public static function response_metadata( string $capability, ?string $status = null ): array {
		$metadata = array(
			'version'      => self::VERSION,
			'capabilities' => array(
				$capability => array( self::capability_declaration( $capability ) ),
			),
		);

		if ( null !== $status ) {
			$metadata['status'] = $status;
		}

		return $metadata;
	}

	/**
	 * Spec and schema references for a capability.
	 *
	 * @param string $capability One of the CAPABILITY_* constants.
	 * @return array
	 */
	private static function capability_declaration( string $capability ): array {
		$declarations = array(
			self::CAPABILITY_CATALOG_SEARCH => array(
				'version' => self::VERSION,
				'spec'    => 'https://ucp.dev/' . self::VERSION . '/specification/catalog/search',
				'schema'  => 'https://ucp.dev/' . self::VERSION . '/schemas/shopping/catalog_search.json',
			),
			self::CAPABILITY_CATALOG_LOOKUP => array(
				'version' => self::VERSION,
				'spec'    => 'https://ucp.dev/' . self::VERSION . '/specification/catalog/lookup',
				'schema'  => 'https://ucp.dev/' . self::VERSION . '/schemas/shopping/catalog_lookup.json',
			),
		);

		return $declarations[ $capability ] ?? array( 'version' => self::VERSION );
	}
}
