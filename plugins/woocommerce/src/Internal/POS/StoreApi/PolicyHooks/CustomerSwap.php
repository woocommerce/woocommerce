<?php
/**
 * CustomerSwap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Customer;

/**
 * Swaps {@see WC()->customer} to a fresh guest object with no address for
 * POS requests.
 *
 * With {@see CurrentUserSwap} setting `wp_set_current_user( 0 )` right
 * after the permission check, the eventual {@see WC::initialize_cart}
 * call would already construct `WC()->customer` as a guest because
 * `get_current_user_id()` is 0. That alone is enough to keep the
 * cashier's saved profile out of the customer object, but the session
 * data store's `set_defaults` then populates `billing_country`,
 * `billing_state`, `shipping_country`, `shipping_state` from
 * `wc_get_customer_default_location()` — i.e. the store's base address.
 *
 * For an in-store POS sale the order must carry no customer address at
 * all (the cashier never entered one and the customer is anonymous), so
 * we still need to explicitly clear those four store-base defaults.
 * Tax computation is unaffected — {@see TaxLocationPolicy} provides the
 * store base address directly via the `woocommerce_customer_taxable_address`
 * filter, independently of any property on the customer object.
 *
 * Hooked on `rest_dispatch_request` at priority 11 so it runs after
 * `CurrentUserSwap` (priority 10) — by then `is_user_logged_in()` is
 * false, so `set_defaults` skips the email fallback that would
 * otherwise have populated `billing_email` from the cashier's WP user.
 * {@see WC::initialize_cart} only creates `WC()->customer` when it is
 * null, so pre-setting it here keeps our blank-guest object in place
 * for the rest of the request.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CustomerSwap implements RegisterHooksInterface {

	/**
	 * Priority deliberately one above CurrentUserSwap (which runs at the
	 * default 10) so the WP user is already 0 by the time we construct
	 * the customer.
	 */
	private const PRIORITY = 11;

	/**
	 * Register hooks. No-op on non-POS requests.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'rest_dispatch_request', array( $this, 'swap_customer' ), self::PRIORITY, 2 );
	}

	/**
	 * Replace {@see WC()->customer} with a fresh guest customer with no
	 * address. Returns the dispatch result unchanged to decline
	 * short-circuiting the route's actual callback.
	 *
	 * @param mixed $dispatch_result Existing dispatch short-circuit value.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function swap_customer( $dispatch_result ) {
		if ( function_exists( 'WC' ) ) {
			$customer = new WC_Customer( 0, true );

			// `set_defaults` populated these from `wc_get_customer_default_location()`
			// (store-base country/state). Strip them so the order's billing/shipping
			// address is truly blank — TaxLocationPolicy supplies the store base
			// address for tax computation via a separate filter.
			$customer->set_billing_country( '' );
			$customer->set_billing_state( '' );
			$customer->set_shipping_country( '' );
			$customer->set_shipping_state( '' );

			WC()->customer = $customer;
		}

		return $dispatch_result;
	}
}
