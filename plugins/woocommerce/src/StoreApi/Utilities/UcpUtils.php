<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
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
	 * Capability served by the `/carts` routes.
	 */
	const CAPABILITY_CART = 'dev.ucp.shopping.cart';

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
	 * @param string $capability One of the CAPABILITY_* constants.
	 * @param string $status Response status, `success` or `error`.
	 * @return array
	 */
	public static function response_metadata( string $capability, string $status ): array {
		return array(
			'version'      => self::VERSION,
			'status'       => $status,
			'capabilities' => array(
				$capability => array( self::capability_declaration( $capability ) ),
			),
		);
	}

	/**
	 * Spec and schema references for every capability these routes serve, keyed by capability name.
	 *
	 * @return array
	 */
	public static function capability_declarations(): array {
		return array(
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
			self::CAPABILITY_CART           => array(
				'version' => self::VERSION,
				'spec'    => 'https://ucp.dev/' . self::VERSION . '/specification/cart',
				'schema'  => 'https://ucp.dev/' . self::VERSION . '/schemas/shopping/cart.json',
			),
		);
	}

	/**
	 * Spec and schema references for a capability.
	 *
	 * @param string $capability One of the CAPABILITY_* constants.
	 * @return array
	 */
	private static function capability_declaration( string $capability ): array {
		return self::capability_declarations()[ $capability ] ?? array( 'version' => self::VERSION );
	}

	/**
	 * Build a UCP error response.
	 *
	 * The envelope shape every route shares. `$status` follows the UCP split: a malformed
	 * request keeps a non-2xx status, a valid request that ends in a refusal is a business
	 * outcome reported at 200 through `ucp.status` and `messages`.
	 *
	 * @param string $capability One of the CAPABILITY_* constants.
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param string $severity Error severity.
	 * @param int    $status HTTP status.
	 * @param string $path JSONPath of the offending field.
	 * @return \WP_REST_Response
	 */
	public static function error_response( string $capability, string $code, string $message, string $severity, int $status, string $path = '$' ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'ucp'      => self::response_metadata( $capability, 'error' ),
				'messages' => array(
					array(
						'type'         => 'error',
						'code'         => $code,
						'content'      => $message,
						'severity'     => $severity,
						'path'         => $path,
						'content_type' => 'plain',
					),
				),
			),
			$status
		);
	}

	/**
	 * Convert a WooCommerce amount to UCP minor units.
	 *
	 * @param MoneyFormatter $money Money formatter configured for the store.
	 * @param mixed          $amount Amount in major units, as WooCommerce reports it.
	 * @return int
	 */
	public static function to_minor_units( MoneyFormatter $money, $amount ): int {
		return (int) $money->format( is_scalar( $amount ) ? (string) $amount : '0' );
	}
}
