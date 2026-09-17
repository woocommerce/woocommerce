<?php
/**
 * CartLogoutBehavior class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Cart;

use Automattic\WooCommerce\Enums\CartBehaviorOnLogout;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Cart;
use WC_Customer;
use WC_Session_Handler;

/**
 * Carries the cart over to the guest session created when a shopper logs out.
 *
 * `WC_Session_Handler::destroy_session()` runs on `wp_logout` and empties the cart with the rest of
 * the session. When the `woocommerce_cart_behavior_on_logout` option is 'preserve', this class copies
 * the cart items before that teardown and writes them into the new guest session afterwards. The next
 * request loads them like any other guest cart; nothing is rebuilt during the logout request itself.
 *
 * The saved cart in `_woocommerce_persistent_cart_{blog_id}` user meta is untouched either way, so
 * logging back in still restores the cart as it always has.
 *
 * @internal Just for internal use.
 *
 * @since 11.3.0
 */
class CartLogoutBehavior implements RegisterHooksInterface {

	/**
	 * Cart items copied before the session was destroyed, in the shape stored under the session's
	 * 'cart' key. Null when there is nothing to carry over.
	 *
	 * @var array|null
	 */
	private $captured_cart = null;

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		// WC_Session_Handler::init_hooks() attaches destroy_session() to wp_logout at priority 10, so the
		// cart has to be read before that and written back after it.
		add_action( 'wp_logout', array( $this, 'handle_wp_logout_capture' ), 5 );
		add_action( 'wp_logout', array( $this, 'handle_wp_logout_restore' ), 15 );
	}

	/**
	 * Handle the wp_logout hook, before the session is destroyed, by copying the cart items.
	 *
	 * @internal
	 */
	public function handle_wp_logout_capture(): void {
		$this->captured_cart = null;

		if ( ! $this->should_cart_be_preserved() ) {
			return;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof WC_Cart || ! did_action( 'woocommerce_load_cart_from_session' ) ) {
			// No cart has been read out of the session on this request, so there is nothing to keep.
			return;
		}

		$items = $cart->get_cart_for_session();
		if ( ! empty( $items ) ) {
			$this->captured_cart = $items;
		}
	}

	/**
	 * Handle the wp_logout hook, after the session is destroyed, by writing the copied items into the
	 * new guest session.
	 *
	 * @internal
	 */
	public function handle_wp_logout_restore(): void {
		$captured_cart       = $this->captured_cart;
		$this->captured_cart = null;

		$session = WC()->session;
		if ( empty( $captured_cart ) || ! $session instanceof WC_Session_Handler ) {
			return;
		}

		try {
			$this->replace_customer_with_guest();

			$items = $this->remove_items_a_guest_cannot_add( $captured_cart );
			if ( empty( $items ) ) {
				return;
			}

			$session->set( 'cart', $items );
			// Without the cookie for the new guest ID the session would never be read back.
			$session->set_customer_session_cookie( true );
			// wp_logout() ends in a redirect and exit, so don't rely on the shutdown save.
			$session->save_data();
		} catch ( \Throwable $error ) {
			// wp_logout has no error boundary, so an escaping error would replace the redirect with a
			// fatal error page. Keeping a cart is never worth that.
			wc_get_logger()->error(
				sprintf( 'Could not preserve the cart on logout: %s', $error->getMessage() ),
				array(
					'source'    => 'cart-logout-behavior',
					'exception' => $error,
				)
			);
		}
	}

	/**
	 * Swap the logged-out shopper's customer object for a guest one.
	 *
	 * `WC()->customer` still holds the shopper's addresses, and its save runs at shutdown through the
	 * session data store. Once the new guest session has a cookie that save would write the previous
	 * shopper's data into it, where the next visitor's requests could read it.
	 */
	private function replace_customer_with_guest(): void {
		$customer = WC()->customer;
		if ( $customer instanceof WC_Customer ) {
			remove_action( 'shutdown', array( $customer, 'save' ), 10 );
		}

		WC()->customer = new WC_Customer( 0, true );
	}

	/**
	 * Re-run add-to-cart validation for each item as the guest the shopper has just become.
	 *
	 * Extensions use this filter to restrict products to logged-in users or roles. Those items could
	 * not be added to a guest cart, so they should not survive into one either.
	 *
	 * @param array $items Cart items in session shape.
	 * @return array The items a guest is allowed to hold.
	 */
	private function remove_items_a_guest_cannot_add( array $items ): array {
		foreach ( $items as $key => $item ) {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in WC_Form_Handler::add_to_cart_action().
			$passed = apply_filters(
				'woocommerce_add_to_cart_validation',
				true,
				(int) ( $item['product_id'] ?? 0 ),
				(int) ( $item['quantity'] ?? 0 ),
				(int) ( $item['variation_id'] ?? 0 ),
				(array) ( $item['variation'] ?? array() )
			);

			if ( true !== $passed ) {
				unset( $items[ $key ] );
			}
		}

		// The session was just emptied, so any notice present now came from a validation callback and
		// was written for an add-to-cart request, not a logout.
		wc_clear_notices();

		return $items;
	}

	/**
	 * Check whether the store is configured to carry the cart over on logout.
	 *
	 * @return bool True if the cart should be preserved, false if it should be emptied.
	 */
	private function should_cart_be_preserved(): bool {
		return CartBehaviorOnLogout::PRESERVE === get_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::CLEAR );
	}
}
