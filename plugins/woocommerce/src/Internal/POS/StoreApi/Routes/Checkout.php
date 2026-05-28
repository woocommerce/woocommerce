<?php
/**
 * Checkout class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout as StoreApiCheckout;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WP_Error;
use WP_REST_Request;

/**
 * POS /checkout route.
 *
 * Extends the Store API's concrete Checkout so the full checkout pipeline
 * (and therefore `woocommerce_store_api_checkout_order_processed` and all
 * extension hooks that depend on it) runs unchanged. POS-specific
 * behaviour added here is:
 *
 *   - Capability-based permission check.
 *   - Nonce check disabled (POS is not cookie-authenticated; mirrors agentic).
 *   - `cart_token` URL parameter accepted as an alternative transport to
 *     the `Cart-Token` HTTP header, so mobile clients that can't easily
 *     send arbitrary request headers can still resolve to the cart they
 *     just built via the POS cart routes.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Checkout extends StoreApiCheckout {

	/**
	 * Capability required for any POS request.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * Endpoint arguments.
	 *
	 * Reuses the Store API definition and substitutes the permission
	 * callback. The `cart_token` URL parameter is added so the mobile
	 * client can replay the token captured from a prior POS cart-route
	 * response without needing custom HTTP header support.
	 *
	 * @return array
	 */
	public function get_args() {
		$endpoints = parent::get_args();

		// The parent's array mixes numerically-indexed endpoint definitions
		// with string-keyed metadata ('schema', 'allow_batch') — only the
		// endpoint definitions should be mutated.
		foreach ( $endpoints as $key => &$endpoint ) {
			if ( ! is_int( $key ) || ! is_array( $endpoint ) || ! isset( $endpoint['methods'] ) ) {
				continue;
			}
			$endpoint['permission_callback'] = array( $this, 'check_permission' );
			$endpoint['args']                = array_merge(
				$endpoint['args'] ?? array(),
				array(
					'cart_token' => array(
						'description' => __( 'Cart session token returned by a prior POS Store API response. Pass it back here to check out the cart you previously built.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
				)
			);
			// Drop the schema-level required flag on billing/shipping address.
			// The Store API marks these as required for web checkout, but for
			// in-store POS sales the cashier often doesn't have customer
			// address info to capture (cash sale of physical goods that the
			// customer is leaving with). Mirrors the deferred-payment-method
			// pattern: POS opts out of a UX-safety guard that doesn't fit the
			// retail use case. For product types that genuinely need an
			// address (downloadables, gift cards, shipped goods sold for
			// delivery), the cashier still captures it and sends it through.
			// A smarter cart-aware per-product-type requirements model is a
			// follow-up; see DECISIONS.md.
			foreach ( array( 'billing_address', 'shipping_address' ) as $address_arg ) {
				if ( isset( $endpoint['args'][ $address_arg ] ) && is_array( $endpoint['args'][ $address_arg ] ) ) {
					$endpoint['args'][ $address_arg ]['required'] = false;
				}
			}
		}
		unset( $endpoint );

		return $endpoints;
	}

	/**
	 * POS permission check.
	 *
	 * @return bool|WP_Error
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function check_permission() {
		if ( current_user_can( static::REQUIRED_CAPABILITY ) ) {
			return true;
		}

		return new WP_Error(
			'woocommerce_pos_rest_forbidden',
			__( 'Sorry, you are not allowed to access POS resources.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * POS requests are not cookie-authenticated, so CSRF is not a vector
	 * and the nonce check is moot. Mirrors agentic commerce's approach.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	protected function requires_nonce( WP_REST_Request $request ) {
		// Request parameter is unused; signature is required by the parent.
		unset( $request );
		return false;
	}

	/**
	 * Look for a Cart-Token sent by the client either as the `cart_token`
	 * URL parameter or the `Cart-Token` HTTP header. If valid, inject it
	 * as the HTTP header so the Store API session swap mechanism picks
	 * it up and resolves the cart that was built by prior POS calls.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	protected function has_cart_token( WP_REST_Request $request ) {
		if ( ! is_null( $this->has_cart_token ) ) {
			return $this->has_cart_token;
		}

		$cart_token = (string) ( $request->get_param( 'cart_token' ) ?? $request->get_header( 'Cart-Token' ) ?? '' );

		if ( '' === $cart_token || ! CartTokenUtils::validate_cart_token( $cart_token ) ) {
			$this->has_cart_token = false;
			return false;
		}

		$request->set_header( 'Cart-Token', $cart_token );
		$_SERVER['HTTP_CART_TOKEN'] = $cart_token;
		$this->has_cart_token       = true;

		return true;
	}
}
