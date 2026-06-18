<?php
/**
 * CartTokenParamPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WP_REST_Request;

/**
 * Lets POS clients carry the cart session as a `cart_token` request parameter
 * instead of the `Cart-Token` header.
 *
 * The Store API resumes a cart session from the `Cart-Token` header, which is
 * the natural mechanism for the shared routes. But some POS transports can't set
 * arbitrary request headers — most notably the Jetpack tunnel, which forwards a
 * fixed header allowlist — so this bridges a `cart_token` body/query parameter
 * onto the header (and `$_SERVER['HTTP_CART_TOKEN']`) that the Store API's
 * session handler and {@see SessionHandlerSwap} both read. Mirrors the
 * `checkout_session_id` parameter agentic commerce accepts for the same reason.
 *
 * Runs on `rest_dispatch_request` (before the route callback initialises the
 * session) and only for valid tokens on POS requests, so a stray parameter on a
 * web request is ignored.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CartTokenParamPolicy implements RegisterHooksInterface {

	/**
	 * Priority below {@see CurrentUserSwap} (10) so the token is in place before
	 * anything downstream needs it; the early call also helps latch POS context
	 * while the cashier is still authenticated.
	 */
	private const PRIORITY = 1;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'rest_dispatch_request', array( $this, 'bridge_cart_token_param' ), self::PRIORITY, 2 );
	}

	/**
	 * Copy a valid `cart_token` parameter onto the Cart-Token header so the Store
	 * API session handler resumes the matching guest session. Returns the
	 * dispatch result unchanged.
	 *
	 * @param mixed           $dispatch_result Existing dispatch short-circuit value.
	 * @param WP_REST_Request $request         Request being dispatched.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function bridge_cart_token_param( $dispatch_result, $request ) {
		if ( ! Context::is_pos_request() ) {
			return $dispatch_result;
		}

		$cart_token = (string) ( $request->get_param( 'cart_token' ) ?? '' );

		if ( '' !== $cart_token && CartTokenUtils::validate_cart_token( $cart_token ) ) {
			$request->set_header( 'Cart-Token', $cart_token );
			$_SERVER['HTTP_CART_TOKEN'] = $cart_token;
		}

		return $dispatch_result;
	}
}
