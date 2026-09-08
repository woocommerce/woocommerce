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

		try {
			$cart_for_session = $cart->get_cart_for_session();
		} catch ( \Throwable $error ) {
			$this->log_failure( 'read the cart before the session was destroyed', $error );
			return;
		}

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

		try {
			$session->set( 'cart', $captured_cart );

			// Without a cookie for the newly generated guest customer ID the session is written but never
			// read back on the next request. The session handler can be swapped via the
			// 'woocommerce_session_handler' filter, so the method is not guaranteed to exist.
			if ( method_exists( $session, 'set_customer_session_cookie' ) ) {
				$session->set_customer_session_cookie( true );
			}

			$cart = WC()->cart;
			if ( $cart instanceof WC_Cart ) {
				// Rebuild the in-memory cart, which destroy_session() emptied. Skipping this would leave the
				// request finishing with an empty cart, and the shutdown handler would clear the cart cookies
				// that front-end caches rely on.
				$cart->get_cart_from_session();
			}

			// Write the session now instead of leaving it to the handler's own shutdown callback, which
			// runs at priority 20, behind the cart cookies, the customer save, the deferred product sync
			// and the webhook queue. wp_logout ends in a redirect and an exit, so anything that fatals or
			// exits among those would drop the cart this class exists to keep. save_data() only writes
			// when the session is dirty and clears the flag, so the shutdown call becomes a no-op rather
			// than a second write.
			if ( method_exists( $session, 'save_data' ) ) {
				$session->save_data();
			}
		} catch ( \Throwable $error ) {
			$this->log_failure( 'restore the cart into the new session', $error );
		}
	}

	/**
	 * Record a failure that must not be allowed to escape into the logout request.
	 *
	 * Rebuilding the cart runs third-party code, through the product data store and the actions that
	 * `WC_Cart_Session::get_cart_from_session()` fires, and `wp_logout` has no error boundary of its
	 * own: an escaping error would replace the redirect with a fatal error page and leave the shopper
	 * unable to tell whether they had logged out. Preserving a cart is never worth that, so failures
	 * are logged and the logout is allowed to finish.
	 *
	 * A failure part-way through the restore still leaves the captured cart in the session, so the
	 * next request reads it back and re-sets the cart cookies.
	 *
	 * @param string     $attempted What the class was trying to do, for the log message.
	 * @param \Throwable $error     The error that was raised.
	 */
	private function log_failure( string $attempted, \Throwable $error ): void {
		wc_get_logger()->error(
			sprintf(
				'Could not %1$s on logout: %2$s in %3$s:%4$d',
				$attempted,
				$error->getMessage(),
				$error->getFile(),
				$error->getLine()
			),
			array(
				'source'    => 'cart-logout-behavior',
				'exception' => $error,
			)
		);
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
