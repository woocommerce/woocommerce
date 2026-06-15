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
 * Shared POS-specific behaviour for routes that subclass Store API concrete
 * route classes (e.g. `StoreApi\Routes\V1\CartAddItem`).
 *
 * This trait holds only what the Store API base classes call back on the route
 * instance itself: {@see self::requires_nonce()} and {@see self::has_cart_token()}
 * are invoked inside `AbstractCartRoute::get_response()`, and
 * {@see self::check_permission()} is wired up as the permission callback by
 * {@see Controller}. The cross-cutting endpoint-shape changes (permission
 * callback, the `cart_token` parameter, schema relaxations) live in the
 * Controller's registration loop, so the route subclasses stay near-empty and
 * there is a single place to reason about how POS diverges from the web Store
 * API.
 *
 * The routes can't share a base class (each extends a different Store API
 * parent), so a trait keeps this shared surface in one file. Each consumer
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
	 * POS requests are authenticated by the route's capability check, not the
	 * WordPress auth cookie, so they are not a CSRF target and opt out of the
	 * Store API nonce requirement through the base-class seam. Agentic commerce
	 * opts out the same way for its Jetpack-token-authenticated routes.
	 *
	 * @see \Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute::is_cookie_authenticated()
	 *
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool
	 */
	protected function is_cookie_authenticated( WP_REST_Request $request ) {
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
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
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
