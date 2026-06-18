<?php
/**
 * RateLimitPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Exempts POS requests from Store API rate limiting.
 *
 * Running POS over the shared `wc/store/v1` routes means the Store API's request
 * throttling (including the checkout limit some stores enable) now applies to
 * POS too. A busy register legitimately fires many cart and checkout requests in
 * quick succession from a single trusted, authenticated operator, so the limiter
 * would only get in the way. The inheritance spike sidestepped this by giving
 * POS its own namespace that the limiter never inspected; here we instead opt
 * POS out explicitly via the `woocommerce_store_api_rate_limit_options` filter,
 * which keeps the limiter fully in force for the public storefront.
 *
 * The filter is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class RateLimitPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_rate_limit_options', array( $this, 'maybe_disable_rate_limiting' ) );
	}

	/**
	 * Disable rate limiting on POS requests, leaving web behaviour untouched.
	 *
	 * @param array $options Rate limit options.
	 * @return array
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_disable_rate_limiting( $options ) {
		if ( Context::is_pos_request() && is_array( $options ) ) {
			$options['enabled'] = false;
		}
		return $options;
	}
}
