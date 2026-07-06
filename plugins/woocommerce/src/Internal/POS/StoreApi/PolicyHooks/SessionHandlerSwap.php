<?php
/**
 * SessionHandlerSwap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Swaps WC_Session_Handler for POSSessionHandler on POS Store API requests.
 *
 * The filter is registered unconditionally and the POS context is evaluated
 * lazily inside the callback — the same shape as the Store API's own
 * Authentication::maybe_use_store_api_session_handler(). Deciding at
 * registration time would freeze the answer before later-loading code (e.g. a
 * theme filtering `rest_url_prefix`) has had its say, and would silently
 * disable POS session isolation on such sites.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class SessionHandlerSwap implements RegisterHooksInterface {

	/**
	 * POS transaction/token lifetime.
	 *
	 * The web default (48h) is shaped for browser carts; a register
	 * transaction lives minutes to hours, and a token that outlives its sale
	 * is only attack surface. Applies to both the session row and the
	 * Cart-Token `exp` (both derive from the same filter).
	 */
	private const SESSION_LIFETIME_SECONDS = 6 * HOUR_IN_SECONDS;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_session_handler', array( $this, 'swap_session_handler' ) );
		add_filter( 'wc_session_expiration', array( $this, 'maybe_shorten_session_lifetime' ) );
	}

	/**
	 * Shorten the session/token lifetime for POS requests.
	 *
	 * Untyped parameter on purpose — see swap_session_handler().
	 *
	 * @param mixed $expiration Session lifetime in seconds.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_shorten_session_lifetime( $expiration ) {
		return Context::is_pos_request() ? self::SESSION_LIFETIME_SECONDS : $expiration;
	}

	/**
	 * Return the POS session handler for POS requests, pass through otherwise.
	 *
	 * No parameter type on purpose: filter callbacks receive whatever earlier
	 * callbacks returned, and a scalar type would turn a third party's sloppy
	 * return value into a fatal TypeError.
	 *
	 * @param mixed $handler Session handler class name supplied by the previous filter or default.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function swap_session_handler( $handler ) {
		return Context::is_pos_request() ? POSSessionHandler::class : $handler;
	}
}
