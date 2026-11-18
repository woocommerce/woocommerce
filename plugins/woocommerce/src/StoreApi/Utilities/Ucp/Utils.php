<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities\Ucp;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Shared helpers for the UCP (Universal Commerce Protocol) routes.
 */
class Utils {
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
	 * Capability served by the `/checkout-sessions` routes.
	 */
	const CAPABILITY_CHECKOUT = 'dev.ucp.shopping.checkout';

	/**
	 * Whether the UCP routes are registered.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}

	/**
	 * Whether the checkout routes are served.
	 *
	 * They take guest orders only and create no account, so a store that requires one serves none of them.
	 *
	 * @return bool
	 */
	public static function is_checkout_enabled(): bool {
		return ! WC()->checkout()->is_registration_required();
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
	 * Spec and schema references for every capability the store serves, keyed by capability name.
	 *
	 * @return array
	 */
	public static function capability_declarations(): array {
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
			self::CAPABILITY_CART           => array(
				'version' => self::VERSION,
				'spec'    => 'https://ucp.dev/' . self::VERSION . '/specification/cart',
				'schema'  => 'https://ucp.dev/' . self::VERSION . '/schemas/shopping/cart.json',
			),
			self::CAPABILITY_CHECKOUT       => array(
				'version' => self::VERSION,
				'spec'    => 'https://ucp.dev/' . self::VERSION . '/specification/checkout',
				'schema'  => 'https://ucp.dev/' . self::VERSION . '/schemas/shopping/checkout.json',
			),
		);

		if ( ! self::is_checkout_enabled() ) {
			unset( $declarations[ self::CAPABILITY_CHECKOUT ] );
		}

		return $declarations;
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
	 * Refuse a request that brought a body or form fields under anything other than a JSON content type.
	 *
	 * A UCP request is a JSON document, so a form or query payload is never read in its
	 * place. A bare request, such as a `cancel`, carries nothing and is not refused.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $capability One of the CAPABILITY_* constants.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|null The 415 refusal, or null when the request may be read.
	 */
	public static function refuse_non_json_payload( \WP_REST_Request $request, string $capability ): ?\WP_REST_Response {
		if ( $request->is_json_content_type() ) {
			return null;
		}

		if ( '' === trim( (string) $request->get_body() ) && array() === $request->get_body_params() ) {
			return null;
		}

		return self::error_response( $capability, 'VALIDATION_ERROR', __( 'UCP requests must be sent as application/json.', 'woocommerce' ), 'recoverable', 415 );
	}

	/**
	 * Read a parameter from the JSON document alone.
	 *
	 * `get_param()` would also resolve it from the form body or the query string. UCP defines
	 * the request as the document, so nothing carried elsewhere is allowed to act.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $name Parameter name.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return mixed|null The value, or null when the document has none.
	 */
	public static function json_param( \WP_REST_Request $request, string $name ) {
		$params = $request->get_json_params();

		return is_array( $params ) && array_key_exists( $name, $params ) ? $params[ $name ] : null;
	}

	/**
	 * Translate a cart error code into the UCP code closest to it.
	 *
	 * The cart names more reasons than UCP does, so the ones an agent can act on
	 * differently — retry smaller, fix the quantity, or re-read the cart — stay apart
	 * and the rest collapse.
	 *
	 * @param string $code Store API or stock reservation error code.
	 * @param string $fallback UCP code for a cart error UCP has no closer name for.
	 * @return string One of `out_of_stock`, `not_found`, `invalid_quantity` or the fallback.
	 */
	public static function cart_error_code( string $code, string $fallback = 'item_unavailable' ): string {
		switch ( $code ) {
			case 'woocommerce_rest_product_out_of_stock':
			case 'woocommerce_rest_product_partially_out_of_stock':
			case 'woocommerce_rest_product_too_many_in_cart':
			case 'woocommerce_product_out_of_stock':
			case 'woocommerce_product_not_enough_stock':
				return 'out_of_stock';
			case 'woocommerce_rest_cart_invalid_key':
				return 'not_found';
			case 'woocommerce_rest_product_invalid_quantity':
			case 'invalid_quantity':
			case 'readonly_quantity':
				return 'invalid_quantity';
			default:
				return $fallback;
		}
	}
}
