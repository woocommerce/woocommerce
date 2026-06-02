<?php
/**
 * PosRouteTrait file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WP_Error;
use WP_REST_Request;

/**
 * Shared POS-specific overrides for routes that subclass Store API concrete
 * route classes (e.g. `StoreApi\Routes\V1\CartAddItem`).
 *
 * The routes can't share a base class (each extends a different Store API
 * parent), so a trait keeps the POS-specific surface in one file. Each consumer
 * declares a `REQUIRED_CAPABILITY` constant, resolved via `static::` in
 * {@see self::check_permission()}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
trait PosRouteTrait {

	/**
	 * Capability-based permission check replacing the Store API's `__return_true`
	 * default. Resolves {@see REQUIRED_CAPABILITY} from the subclass via late
	 * static binding.
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
	 * POS requests are not cookie-authenticated, so CSRF isn't a vector and
	 * the Store API nonce check is moot. Mirrors how agentic commerce opts
	 * out of the same check in
	 * {@see \Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\CheckoutSessions::requires_nonce}.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	protected function requires_nonce( WP_REST_Request $request ) {
		unset( $request );
		return false;
	}

	/**
	 * Detect a Cart-Token sent as the `cart_token` URL parameter or the
	 * `Cart-Token` header, and inject it as the header so the Store API's
	 * header-based session swap picks it up. Accepting the URL parameter lets
	 * mobile clients participate without custom-header support — mirroring the
	 * `checkout_session_id` pattern used by agentic commerce.
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

	/**
	 * Apply POS overrides to the parent's {@see get_args()} return value: swap
	 * the permission callback and add the `cart_token` URL parameter. Only the
	 * int-indexed endpoint definitions are touched, not the string-keyed
	 * metadata (`schema`, `allow_batch`).
	 *
	 * @param array  $endpoints              Result of `parent::get_args()`.
	 * @param string $cart_token_description Per-route description for the `cart_token` URL parameter.
	 * @return array
	 */
	protected function apply_pos_endpoint_overrides( array $endpoints, string $cart_token_description ): array {
		foreach ( $endpoints as $key => &$endpoint ) {
			if ( ! is_int( $key ) || ! is_array( $endpoint ) || ! isset( $endpoint['methods'] ) ) {
				continue;
			}
			$endpoint['permission_callback'] = array( $this, 'check_permission' );
			$endpoint['args']                = array_merge(
				$endpoint['args'] ?? array(),
				array(
					'cart_token' => array(
						'description' => $cart_token_description,
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
				)
			);
		}
		unset( $endpoint );
		return $endpoints;
	}
}
