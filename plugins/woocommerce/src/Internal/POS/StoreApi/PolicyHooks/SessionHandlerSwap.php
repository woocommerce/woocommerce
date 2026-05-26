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
 * Lives in PolicyHooks/ alongside other request-context-aware filter wiring.
 * The filter is registered unconditionally; the swap itself is gated on
 * {@see Context::is_pos_request()} so non-POS requests pay only a single
 * URI check.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class SessionHandlerSwap implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_session_handler', array( $this, 'maybe_swap_session_handler' ) );
	}

	/**
	 * Return POSSessionHandler when the current request is POS.
	 *
	 * @param string $handler Class name supplied by the previous filter or default.
	 * @return string
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_swap_session_handler( string $handler ): string {
		if ( Context::is_pos_request() ) {
			return POSSessionHandler::class;
		}

		return $handler;
	}
}
