<?php
/**
 * NonceCheckPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Disables the Store API Cart-route nonce check for POS requests.
 *
 * AbstractCartRoute enforces a CSRF nonce on write operations unless a
 * valid Cart-Token header is present (see AbstractCartRoute::requires_nonce).
 * This is the correct default for cookie-authenticated browser callers, but
 * is not a meaningful protection for POS: requests are authenticated via
 * Application Password / WPCOM bearer (not cookies), so CSRF is not a
 * vector, and the mobile client cannot mint a Cart-Token until after the
 * first server-side session is created.
 *
 * The Store API exposes `woocommerce_store_api_disable_nonce_check` for
 * exactly this kind of out-of-band caller. We opt POS in via that hook,
 * gated on Context::is_pos_request() so web callers are unaffected.
 *
 * Lives in PolicyHooks/ alongside the other request-context-aware
 * filter wiring (stock override, session handler swap).
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class NonceCheckPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_disable_nonce_check', array( $this, 'maybe_disable_nonce_check' ) );
	}

	/**
	 * Return true to disable the Store API nonce check for POS requests.
	 *
	 * @param bool $disable Original value supplied by the filter chain.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_disable_nonce_check( bool $disable ): bool {
		if ( Context::is_pos_request() ) {
			return true;
		}

		return $disable;
	}
}
