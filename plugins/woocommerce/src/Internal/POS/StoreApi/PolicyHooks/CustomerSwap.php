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
 * Swaps {@see WC()->customer} to a fresh guest object with no address for POS
 * requests.
 *
 * {@see CurrentUserSwap} already makes the customer a guest, but the session's
 * `set_defaults` then fills billing/shipping country and state from the store's
 * base address. An in-store POS order must carry no customer address at all, so
 * we replace the customer with a blank guest and clear those defaults. Tax is
 * unaffected — {@see TaxLocationPolicy} supplies the store base address via a
 * separate filter.
 *
 * Hooked on `rest_dispatch_request` at priority 11 (after CurrentUserSwap at
 * 10), so by the time we build the customer `is_user_logged_in()` is already
 * false. {@see WC::initialize_cart} only creates the customer when null, so
 * pre-setting it here keeps our blank guest in place for the request.
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
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'rest_dispatch_request', array( $this, 'swap_customer' ), self::PRIORITY, 1 );
	}

	/**
	 * On POS requests, replace {@see WC()->customer} with a fresh guest customer
	 * with no address. Returns the dispatch result unchanged to decline
	 * short-circuiting the route's actual callback.
	 *
	 * Runs at priority 11, after {@see CapabilityGate} (priority 5) has authorised
	 * the request and {@see CurrentUserSwap} (priority 10) has dropped the user to
	 * a guest.
	 *
	 * @param mixed $dispatch_result Existing dispatch short-circuit value.
	 * @return mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function swap_customer( $dispatch_result ) {
		// CapabilityGate (priority 5) rejected this request — don't touch the customer.
		if ( is_wp_error( $dispatch_result ) ) {
			return $dispatch_result;
		}

		if ( ! Context::is_pos_request() ) {
			return $dispatch_result;
		}

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

			// A fresh POS guest carries no contact details either. Clearing the
			// email matters because the constructor above loads any session-backed
			// customer data, so a reused session must not let a prior sale's email
			// satisfy this order's requirements (e.g. the downloadable email gate).
			$customer->set_billing_email( '' );

			WC()->customer = $customer;
		}

		return $dispatch_result;
	}
}
