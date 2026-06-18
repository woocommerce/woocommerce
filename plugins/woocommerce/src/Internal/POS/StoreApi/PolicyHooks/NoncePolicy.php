<?php
/**
 * NoncePolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Opts POS requests out of the Store API nonce check.
 *
 * The Store API nonce exists to stop a browser from being tricked into replaying
 * a logged-in shopper's cookie cross-site (CSRF). A POS client authenticating
 * out-of-band — application password, OAuth/REST token, the Jetpack tunnel — is
 * not a CSRF target, because a browser won't replay those credentials, so the
 * nonce only gets in its way. The inheritance spike expressed this by overriding
 * the route's `is_cookie_authenticated()` seam; with no POS route subclass to
 * override, the shared-routes approach uses the existing
 * `woocommerce_store_api_disable_nonce_check` filter instead.
 *
 * The opt-out is conditional: if a request actually arrives with a valid auth
 * cookie (a store manager driving the routes straight from a browser) it IS a
 * CSRF target, so the nonce must stand. We detect that via the
 * `$wp_rest_auth_cookie` global — the same signal core sets in
 * `rest_cookie_check_errors()` — and only disable the nonce when it is absent.
 *
 * The filter is installed for every request and the POS check runs in the
 * callback; see {@see Context} for why detection is deferred to call time.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class NoncePolicy implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_store_api_disable_nonce_check', array( $this, 'maybe_disable_nonce_check' ) );
	}

	/**
	 * Disable the nonce check for non-cookie-authenticated POS requests, leaving
	 * web (and cookie-authenticated POS) behaviour untouched.
	 *
	 * @param bool $disabled Whether the nonce check is already disabled.
	 * @return bool
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_disable_nonce_check( $disabled ) {
		if ( ! Context::is_pos_request() ) {
			return $disabled;
		}

		// A real auth cookie makes the request a CSRF target — keep the nonce.
		global $wp_rest_auth_cookie;
		if ( true === $wp_rest_auth_cookie ) {
			return $disabled;
		}

		return true;
	}
}
