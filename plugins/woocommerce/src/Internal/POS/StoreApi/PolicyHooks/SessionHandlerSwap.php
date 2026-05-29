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
 * Registration itself is gated on {@see Context::is_pos_request()}, so the
 * filter is installed only when the current request is POS. The callback
 * therefore needs no per-invocation context check.
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
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'woocommerce_session_handler', array( $this, 'swap_session_handler' ) );
	}

	/**
	 * Return the POS session handler.
	 *
	 * @param string $handler Class name supplied by the previous filter or default.
	 * @return string
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function swap_session_handler( string $handler ): string {
		unset( $handler );
		return POSSessionHandler::class;
	}
}
