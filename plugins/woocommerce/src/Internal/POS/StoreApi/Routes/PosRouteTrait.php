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
 * Shared POS-specific behaviour for the adapter-style POS routes, all of which
 * extend {@see \Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute}
 * (the designed extension point, as agentic commerce does) and own their own
 * endpoint shape.
 *
 * Two kinds of shared surface live here:
 *
 * - Seams the Store API base class calls back on the route instance:
 *   {@see self::is_cookie_authenticated()} (consulted by `requires_nonce()`) and
 *   {@see self::has_cart_token()} (consulted by `AbstractCartRoute::load_cart_session()`).
 * - Helpers each route's own `get_args()` composes in: {@see self::check_permission()}
 *   (the permission callback), {@see self::pos_cart_token_arg()} (the `cart_token`
 *   parameter), and {@see self::pos_relax_address_required()} (the checkout
 *   address relaxation). Keeping these in the trait means each route declares its
 *   shape directly — there is no central registration-time rewriting step — while
 *   the POS-common bits stay defined once.
 *
 * Each consumer declares a `REQUIRED_CAPABILITY` constant, resolved via
 * `static::` in {@see self::check_permission()}.
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
	 * Whether this request was authenticated by the WordPress auth cookie.
	 *
	 * The Store API nonce only protects cookie-authenticated requests against
	 * CSRF. A POS client authenticating out-of-band — application password,
	 * OAuth/REST token, Jetpack tunnel — is not a CSRF target, because a browser
	 * won't replay those credentials cross-site, so the nonce can be skipped.
	 *
	 * We must not merely *assume* that, though: if a request actually arrives
	 * with a valid auth cookie (e.g. a logged-in store manager driving the
	 * routes from a browser), it IS a CSRF target and the nonce must stand.
	 * Rather than hard-coding the opt-out, we detect the real auth method via
	 * the `$wp_rest_auth_cookie` global — the same signal WordPress core uses in
	 * `rest_cookie_check_errors()` — which is set to true only when a valid auth
	 * cookie was presented for this request.
	 *
	 * @see \Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute::is_cookie_authenticated()
	 *
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool
	 */
	protected function is_cookie_authenticated( WP_REST_Request $request ) {
		unset( $request );

		global $wp_rest_auth_cookie;

		return true === $wp_rest_auth_cookie;
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

	/**
	 * The `cart_token` endpoint argument every POS route accepts, so mobile
	 * clients can carry the cart session as a URL/body parameter when they can't
	 * set the `Cart-Token` header (e.g. through the Jetpack tunnel). Routes merge
	 * this into their own `get_args()`; {@see self::has_cart_token()} reads it back.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function pos_cart_token_arg(): array {
		return array(
			'cart_token' => array(
				'description' => __( 'Cart session token returned by a prior POS Store API response. Pass it back on subsequent requests to keep the cart scoped to the same transaction.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		);
	}

	/**
	 * Relax the schema-level `required` flag on the billing/shipping address args
	 * so POS can submit an order with empty addresses at parse time (an in-person
	 * sale usually has neither). The deeper address validation is relaxed
	 * separately by {@see \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutAddressPolicy}.
	 * A no-op for arg sets without those keys.
	 *
	 * @param array $args Endpoint args (typically from `get_endpoint_args_for_item_schema()`).
	 * @return array
	 */
	protected function pos_relax_address_required( array $args ): array {
		foreach ( array( 'billing_address', 'shipping_address' ) as $address_arg ) {
			if ( isset( $args[ $address_arg ] ) && is_array( $args[ $address_arg ] ) ) {
				$args[ $address_arg ]['required'] = false;
			}
		}
		return $args;
	}
}
