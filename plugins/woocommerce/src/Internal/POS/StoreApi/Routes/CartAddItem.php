<?php
/**
 * CartAddItem class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\CartAddItem as StoreApiCartAddItem;
use WP_Error;
use WP_REST_Request;

/**
 * POS cart/add-item route.
 *
 * Extends the Store API's concrete CartAddItem so the full add-to-cart
 * pipeline (and therefore the checkout-time extension hooks downstream)
 * runs unchanged. The only POS-specific behaviour added here is:
 *
 *   - A capability-based permission check, replacing the Store API's
 *     `__return_true` default (the Store API is unauthenticated; POS is
 *     authenticated by Application Password / WPCOM bearer).
 *   - Nonce check disabled — POS requests are not cookie-authenticated,
 *     so CSRF is not a vector. Mirrors how agentic commerce handles the
 *     same situation in
 *     {@see \Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\CheckoutSessions::requires_nonce}.
 *
 * The Store API's `AbstractCartRoute::add_response_headers` already emits
 * a `Cart-Token` HTTP response header, so mobile clients get session
 * continuity for free by replaying that header on subsequent requests.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartAddItem extends StoreApiCartAddItem {

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * Endpoint arguments.
	 *
	 * Reuses the Store API definition (so request schema, sanitisation and
	 * the underlying callback stay in lockstep with web) and substitutes
	 * only the permission callback.
	 *
	 * @return array
	 */
	public function get_args() {
		$endpoints = parent::get_args();

		foreach ( $endpoints as &$endpoint ) {
			if ( is_array( $endpoint ) ) {
				$endpoint['permission_callback'] = array( $this, 'check_permission' );
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
}
