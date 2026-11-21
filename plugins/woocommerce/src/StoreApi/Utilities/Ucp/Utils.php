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
	 * Extension of the cart and checkout capabilities that takes discount codes.
	 */
	const CAPABILITY_DISCOUNT = 'dev.ucp.shopping.discount';

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
	 * UCP requires `capabilities` to name only what is relevant to the operation being
	 * answered: its own capability, and the extensions declared on it.
	 *
	 * @param string $capability One of the CAPABILITY_* constants.
	 * @param string $status Response status, `success` or `error`.
	 * @return array
	 */
	public static function response_metadata( string $capability, string $status ): array {
		$declarations = self::capability_declarations();
		$capabilities = array( $capability => array( $declarations[ $capability ] ?? array( 'version' => self::VERSION ) ) );

		foreach ( $declarations as $name => $declaration ) {
			if ( in_array( $capability, $declaration['extends'] ?? array(), true ) ) {
				$capabilities[ $name ] = array( $declaration );
			}
		}

		return array(
			'version'      => self::VERSION,
			'status'       => $status,
			'capabilities' => $capabilities,
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

		// A store with coupons turned off has no code to apply.
		if ( wc_coupons_enabled() ) {
			$declarations[ self::CAPABILITY_DISCOUNT ] = array(
				'version' => self::VERSION,
				'extends' => array_values( array_intersect( array( self::CAPABILITY_CART, self::CAPABILITY_CHECKOUT ), array_keys( $declarations ) ) ),
				'spec'    => 'https://ucp.dev/' . self::VERSION . '/specification/discount',
				'schema'  => 'https://ucp.dev/' . self::VERSION . '/schemas/shopping/discount.json',
			);
		}

		return $declarations;
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
	 * Split a variant id into its product id and the values it carries for "Any" attributes.
	 *
	 * A variation leaving an attribute as "Any" is sold once per value, so its variants are
	 * named `<variation id>:<attribute key>=<value>`, with further pairs joined by `&`.
	 *
	 * @param string $id Variant id.
	 * @return array{0: int, 1: array<string, string>}|null Product id and values by attribute key, or null when malformed.
	 */
	public static function parse_variant_id( string $id ): ?array {
		$parts = explode( ':', $id, 2 );

		if ( ! ctype_digit( $parts[0] ) ) {
			return null;
		}

		$values = array();

		foreach ( isset( $parts[1] ) ? explode( '&', $parts[1] ) : array() as $pair ) {
			$pair = explode( '=', $pair, 2 );

			if ( 2 !== count( $pair ) || '' === $pair[0] || '' === $pair[1] ) {
				return null;
			}

			$values[ rawurldecode( $pair[0] ) ] = rawurldecode( $pair[1] );
		}

		return array( (int) $parts[0], $values );
	}

	/**
	 * Build a variant id from a variation id and the values chosen for its "Any" attributes.
	 *
	 * @param int                   $variation_id Variation id.
	 * @param array<string, string> $values Chosen values by attribute key, in the product's attribute order.
	 * @return string
	 */
	public static function build_variant_id( int $variation_id, array $values ): string {
		$pairs = array();

		foreach ( $values as $key => $value ) {
			$pairs[] = rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
		}

		return empty( $pairs ) ? (string) $variation_id : $variation_id . ':' . implode( '&', $pairs );
	}

	/**
	 * Build the variant id of a variation holding the given attribute values.
	 *
	 * Only the attributes the variation leaves as "Any" enter the id; a value for any other is ignored.
	 *
	 * @param \WC_Product_Variation $variation The variation.
	 * @param array                 $values Attribute values by key, with or without the `attribute_` prefix.
	 * @return string
	 */
	public static function variant_id( \WC_Product_Variation $variation, array $values ): string {
		$chosen = array();

		foreach ( $variation->get_attributes() as $key => $own_value ) {
			$value = (string) ( $values[ $key ] ?? $values[ 'attribute_' . $key ] ?? '' );

			if ( '' === (string) $own_value && '' !== $value ) {
				$chosen[ (string) $key ] = $value;
			}
		}

		return self::build_variant_id( (int) $variation->get_id(), $chosen );
	}

	/**
	 * Load the product a variant id names, with the id in its canonical form.
	 *
	 * @param string $id Product or variant id.
	 * @return array{product: \WC_Product, id: string}|null Null when malformed, unknown, or carrying a
	 *                                                      value for something other than an "Any" attribute.
	 */
	public static function resolve_variant_id( string $id ): ?array {
		$parsed  = self::parse_variant_id( $id );
		$product = null === $parsed ? null : wc_get_product( $parsed[0] );

		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		$canonical = $product instanceof \WC_Product_Variation ? self::variant_id( $product, $parsed[1] ) : (string) $product->get_id();

		if ( count( self::parse_variant_id( $canonical )[1] ) !== count( $parsed[1] ) ) {
			return null;
		}

		return array(
			'product' => $product,
			'id'      => $canonical,
		);
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
