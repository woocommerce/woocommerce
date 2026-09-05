<?php
/**
 * CartLogoutBehavior class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Cart;

use Automattic\WooCommerce\Enums\CartBehaviorOnLogout;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Cart;
use WC_Session;

/**
 * Carries the cart over to the guest session created when a shopper logs out.
 *
 * `WC_Session_Handler::destroy_session()` runs on `wp_logout` and empties the cart along with the
 * rest of the session, so a shopper who logs out mid-shop loses what they had on that device. When
 * the `woocommerce_cart_behavior_on_logout` option is set to 'preserve', this class takes the cart
 * contents before that teardown and writes them into the fresh guest session afterwards.
 *
 * The saved cart in `_woocommerce_persistent_cart_{blog_id}` user meta is untouched either way, so
 * logging back in still restores the cart as it always has.
 *
 * @internal Just for internal use.
 *
 * @since 11.2.0
 */
class CartLogoutBehavior implements RegisterHooksInterface {

	/**
	 * Cart contents captured before the session was destroyed, in the shape stored under the
	 * session's 'cart' key. Null when there is nothing to carry over.
	 *
	 * @var array|null
	 */
	private $captured_cart = null;

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		// WC_Session_Handler::destroy_session() is hooked to wp_logout at the default priority of 10,
		// so the cart has to be read before that and written back after it.
		add_action( 'wp_logout', array( $this, 'handle_wp_logout_capture' ), 5 );
		add_action( 'wp_logout', array( $this, 'handle_wp_logout_restore' ), 15 );
	}

	/**
	 * Handle the wp_logout hook, before the session is destroyed, by taking a copy of the cart.
	 *
	 * @internal
	 */
	public function handle_wp_logout_capture(): void {
		$this->captured_cart = null;

		if ( ! $this->cart_should_be_preserved() ) {
			return;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		// Nothing has been read out of the session yet, so there is no cart to carry over. Reading one
		// now would make WC_Cart::get_cart() load the session from under a request that never wanted a
		// cart, on top of warning that it ran too early. Logouts from wp-login.php and the My account
		// endpoint both happen well after this fires.
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) ) {
			return;
		}

		$cart_for_session = $cart->get_cart_for_session();
		if ( empty( $cart_for_session ) ) {
			return;
		}

		$this->captured_cart = $cart_for_session;
	}

	/**
	 * Handle the wp_logout hook, after the session is destroyed, by seeding the new guest session
	 * with the captured cart.
	 *
	 * @internal
	 */
	public function handle_wp_logout_restore(): void {
		$captured_cart       = $this->captured_cart;
		$this->captured_cart = null;

		if ( empty( $captured_cart ) ) {
			return;
		}

		$session = WC()->session;
		if ( ! $session instanceof WC_Session ) {
			return;
		}

		$session->set( 'cart', $captured_cart );

		// Without a cookie for the newly generated guest customer ID the session is written but never
		// read back on the next request. The session handler can be swapped via the
		// 'woocommerce_session_handler' filter, so the method is not guaranteed to exist.
		if ( method_exists( $session, 'set_customer_session_cookie' ) ) {
			$session->set_customer_session_cookie( true );
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		// Rebuild the in-memory cart, which destroy_session() emptied. Skipping this would leave the
		// request finishing with an empty cart, and the shutdown handler would clear the cart cookies
		// that front-end caches rely on.
		$cart->get_cart_from_session();
	}

	/**
	 * Check whether the store is configured to carry the cart over on logout.
	 *
	 * @return bool True if the cart should be preserved, false if it should be emptied.
	 */
	private function cart_should_be_preserved(): bool {
		$behavior = get_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );

		return CartBehaviorOnLogout::PRESERVE === $behavior;
	}
}
