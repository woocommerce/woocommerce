<?php
/**
 * SessionHandlerSwap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;

/**
 * Swaps WC_Session_Handler for POSSessionHandler on the first request of a POS
 * transaction.
 *
 * Because POS now runs over the shared `wc/store/v1` routes, the Store API's own
 * cart-token session handler ({@see \Automattic\WooCommerce\StoreApi\Authentication::maybe_use_store_api_session_handler},
 * hooked at priority 0) already kicks in whenever a valid `Cart-Token` is
 * present and loads the correct guest session from it. That covers every
 * continuation request in a transaction for free.
 *
 * The gap it leaves is the *first* request, which carries no token yet: the
 * default handler would key the cart to the logged-in cashier — and since every
 * cashier shares the store-manager account, two concurrent first-requests would
 * collide on one cart. {@see POSSessionHandler} closes that gap by minting a
 * fresh guest id and ignoring cookies, so the first request starts a clean
 * isolated session whose id is then handed back as the Cart-Token.
 *
 * So this swap deliberately fires only when there is no valid cart token; with
 * one present it leaves the Store API's token handler in place.
 *
 * The filter is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class SessionHandlerSwap implements RegisterHooksInterface {

	/**
	 * Priority just above the Store API's own handler (priority 0) so we observe
	 * the value it set and only override it for the tokenless first request.
	 */
	private const PRIORITY = 1;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_session_handler', array( $this, 'maybe_swap_session_handler' ), self::PRIORITY );
	}

	/**
	 * Use the POS session handler for the tokenless first request of a POS
	 * transaction; otherwise leave the supplied handler untouched.
	 *
	 * @param string $handler Class name supplied by the previous filter or default.
	 * @return string
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_swap_session_handler( $handler ) {
		if ( ! Context::is_pos_request() || $this->has_valid_cart_token() ) {
			return $handler;
		}
		return POSSessionHandler::class;
	}

	/**
	 * Whether the request carries a valid Cart-Token (read the same way the
	 * Store API reads it), meaning a guest session already exists to resume.
	 *
	 * @return bool
	 */
	private function has_valid_cart_token(): bool {
		$cart_token = wc_clean( wp_unslash( $_SERVER['HTTP_CART_TOKEN'] ?? '' ) );
		$cart_token = is_string( $cart_token ) ? $cart_token : '';

		return '' !== $cart_token && CartTokenUtils::validate_cart_token( $cart_token );
	}
}
