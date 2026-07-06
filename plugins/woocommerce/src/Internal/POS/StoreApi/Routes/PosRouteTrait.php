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
 * Shared POS-specific behaviour for the POS Store API routes, all of which
 * extend {@see \Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute}
 * and are registered under the `wc/internal/pos/v1` namespace.
 *
 * Each consumer declares a `REQUIRED_CAPABILITY` constant, resolved via
 * `static::` in {@see self::check_permission()}.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
trait PosRouteTrait {

	/**
	 * Fail loud when the client presents a Cart-Token that cannot resume a
	 * POS transaction.
	 *
	 * The session handler itself degrades silently to a fresh session (the
	 * safe default at that layer), but from the client's perspective that
	 * would mean: token expired overnight → next scan returns 201 with a cart
	 * missing every previously scanned item → checkout silently undercharges.
	 * A presented-but-unusable token is a client-visible error, not a fresh
	 * start.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_response( WP_REST_Request $request ) {
		$cart_token = (string) ( $request->get_header( 'Cart-Token' ) ?? '' );

		if ( '' !== $cart_token && ! $this->is_resumable_pos_cart_token( $cart_token ) ) {
			return $this->error_to_response(
				$this->get_route_error_response(
					'woocommerce_pos_rest_invalid_cart_token',
					__( 'The cart token is invalid or expired. Start a new transaction.', 'woocommerce' ),
					401
				)
			);
		}

		return $this->dispatch_pos_response( $request );
	}

	/**
	 * The underlying response dispatch the token pre-check wraps.
	 *
	 * Defaults to the cart route base implementation; routes composing another
	 * trait's get_response (the checkout) alias theirs in here instead.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function dispatch_pos_response( WP_REST_Request $request ) {
		return parent::get_response( $request );
	}

	/**
	 * Whether a presented Cart-Token identifies a resumable POS transaction.
	 *
	 * @param string $cart_token The presented token.
	 * @return bool
	 */
	private function is_resumable_pos_cart_token( string $cart_token ): bool {
		if ( ! CartTokenUtils::validate_cart_token( $cart_token ) ) {
			return false;
		}

		$customer_id = (string) ( CartTokenUtils::get_cart_token_payload( $cart_token )['user_id'] ?? '' );

		return 0 === strpos( $customer_id, 'pos_' );
	}

	/**
	 * Capability-based permission check replacing the Store API's `__return_true`
	 * default. Resolves the REQUIRED_CAPABILITY constant from the using class via
	 * late static binding.
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
	 * POS routes never require the Store API `Nonce` header.
	 *
	 * That nonce exists to protect *cookie-authenticated* Store API requests
	 * against CSRF, and the Store API needs it because its own Authentication
	 * class deliberately bypasses WordPress core's cookie/nonce check for
	 * `wc/store/*` requests (so guests can use the cart). Neither applies here:
	 *
	 * - The POS namespace is outside `wc/store/*`, so core's standard REST
	 *   authentication runs in full: a cookie-authenticated request without a
	 *   valid `_wpnonce`/`X-WP-Nonce` is demoted to "not logged in"
	 *   (see `rest_cookie_check_errors()`), and then fails
	 *   {@see self::check_permission()}. Cookie-based CSRF is already dead.
	 * - The POS clients authenticate out-of-band — application password,
	 *   Jetpack blog token — and are not CSRF targets, because a browser won't
	 *   replay those credentials cross-site.
	 *
	 * Same pattern as the agentic checkout routes
	 * (see \Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\CheckoutSessions::requires_nonce()).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool
	 */
	protected function requires_nonce( WP_REST_Request $request ) {
		unset( $request );
		return false;
	}

	/**
	 * Load the cart session without the parent's late session-handler swap.
	 *
	 * In POS context {@see \Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler}
	 * is installed up front (see PolicyHooks\SessionHandlerSwap) and resumes
	 * the transaction from the Cart-Token header itself, whichever moment the
	 * session initializes — including eagerly-initialized `?rest_route=`
	 * requests, which the parent's dispatch-time swap is too late for. Adding
	 * the parent's Store API SessionHandler filter on top would just move
	 * later requests onto a second, non-POS handler mid-surface.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return void
	 */
	protected function load_cart_session( WP_REST_Request $request ) {
		unset( $request );
		$this->cart_controller->load_cart();
		$this->cart_controller->normalize_cart();
	}
}
