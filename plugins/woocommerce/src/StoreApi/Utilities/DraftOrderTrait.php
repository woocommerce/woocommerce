<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * DraftOrderTrait
 *
 * Shared functionality for getting and setting draft order IDs from session.
 */
trait DraftOrderTrait {
	/**
	 * Gets draft order data from the customer session.
	 *
	 * @return integer
	 */
	protected function get_draft_order_id() {
		if ( ! wc()->session ) {
			wc()->initialize_session();
		}
		return wc()->session->get( 'store_api_draft_order', 0 );
	}

	/**
	 * Updates draft order data in the customer session.
	 *
	 * @param integer $order_id Draft order ID.
	 */
	protected function set_draft_order_id( $order_id ) {
		if ( ! wc()->session ) {
			wc()->initialize_session();
		}
		wc()->session->set( 'store_api_draft_order', $order_id );
	}

	/**
	 * Removes the order from the session when it is the draft order and its last save failed.
	 *
	 * A failed save can leave the order partly written, and resuming it would reuse that state.
	 *
	 * @since 11.2.2
	 * @param \WC_Order $order Order object.
	 */
	protected function forget_draft_order_if_not_saved( \WC_Order $order ): void {
		if ( null !== $order->get_last_save_error() && (int) $this->get_draft_order_id() === $order->get_id() ) {
			$this->set_draft_order_id( 0 );
		}
	}

	/**
	 * Uses the draft order ID to return an order object, if valid.
	 *
	 * @return \WC_Order|null;
	 */
	protected function get_draft_order() {
		$draft_order_id = $this->get_draft_order_id();
		$draft_order    = $draft_order_id ? wc_get_order( $draft_order_id ) : false;

		return $this->is_valid_draft_order( $draft_order ) ? $draft_order : null;
	}

	/**
	 * Whether the argument can serve as the session's draft order: a checkout-draft, or a pending/failed order
	 * the shopper can still pay as placed (its cart hash matches, the cart keeps its counted coupons, a priced order
	 * that needs shipping still ships to the customer's destination, and its tax location has not moved).
	 *
	 * @param \WC_Order $order_object Order object to check.
	 * @return boolean Whether the order is valid as a draft order.
	 */
	protected function is_valid_draft_order( $order_object ) {
		if ( ! $order_object instanceof \WC_Order ) {
			return false;
		}

		// Draft orders are okay.
		if ( $order_object->has_status( 'checkout-draft' ) ) {
			return true;
		}

		// Pending and failed orders can be retried if the cart hasn't changed. The cart hash covers the cart
		// lines and total only, not the shipping destination the order's shipping lines were rated for nor the
		// location its tax is worked out for, so both are checked separately to stop a placed order being
		// re-addressed without going through checkout again. As on the pay-for-order page, an order that has no
		// address yet and did not come from the Store API may take its first one.
		if (
			$order_object->needs_payment()
			&& $order_object->has_cart_hash( wc()->cart->get_cart_hash() )
			&& $this->cart_keeps_counted_coupons( $order_object )
			&& (
				$this->order_awaits_first_address( $order_object )
				|| ( $this->order_ships_to_customer_destination( $order_object ) && $this->order_taxed_at_customer_location( $order_object ) )
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the order's shipping lines are still rated for the session customer's destination, which the cart
	 * hash does not cover. Compared through the pay-for-order guard's helper, reading a state the session filled
	 * in by default as empty; the taxable location is checked by order_taxed_at_customer_location().
	 *
	 * @since 11.3.1
	 *
	 * @param \WC_Order $order_object Order object to check.
	 * @return boolean True when the destinations match or the cart needs no shipping; false without a customer,
	 *                 or when only one of the order and the customer has a shipping country.
	 */
	protected function order_ships_to_customer_destination( \WC_Order $order_object ) {
		$customer = wc()->customer;

		if ( ! $customer ) {
			return false;
		}

		if ( ! wc()->cart->needs_shipping() ) {
			return true;
		}

		// Shipping is rated for the shipping address, or the billing address when there is none.
		$destination = static function ( $address_holder ): array {
			$type = $address_holder->get_shipping_country() ? 'shipping' : 'billing';

			return array(
				'country'  => $address_holder->{"get_{$type}_country"}(),
				'state'    => $address_holder->{"get_{$type}_state"}(),
				'postcode' => $address_holder->{"get_{$type}_postcode"}(),
				'city'     => $address_holder->{"get_{$type}_city"}(),
			);
		};

		// When one side has a shipping country and the other does not, the destinations above would be read from
		// different address types. Clearing the shipping country is itself a move away from the destination the
		// order's shipping lines were rated for, so the order is not reused.
		if ( (bool) $order_object->get_shipping_country() !== (bool) $customer->get_shipping_country() ) {
			return false;
		}

		return $this->locations_match( $destination( $order_object ), $destination( $customer ) );
	}

	/**
	 * Whether the order would still be taxed for the same location once it takes the session customer's addresses,
	 * which the cart hash does not cover either. The sync re-prices tax from the order's own addresses, so the old
	 * tax is never charged, but the new address skips the checks checkout makes (allowed countries, state codes).
	 * Refusing reuse sends the change through checkout instead. Same before-and-after comparison as the
	 * pay-for-order guard, OrderController::validate_location_change(), except that a state the session filled in
	 * by default is read as empty; its exemption for an order awaiting its first address is applied by
	 * is_valid_draft_order().
	 *
	 * @since 11.3.1
	 *
	 * @param \WC_Order $order_object Order object to check.
	 * @return boolean True when the tax location is unchanged or the store collects no tax; false without a customer,
	 *                 or when either filtered tax location is not an array.
	 */
	protected function order_taxed_at_customer_location( \WC_Order $order_object ) {
		$customer = wc()->customer;

		if ( ! $customer ) {
			return false;
		}

		if ( ! wc_tax_enabled() ) {
			return true;
		}

		// A copy carrying the customer's addresses, which is what the sync would write. Nothing is saved.
		$readdressed = clone $order_object;
		$props       = array();

		foreach ( array( 'billing', 'shipping' ) as $type ) {
			foreach ( array( 'country', 'state', 'postcode', 'city' ) as $field ) {
				$props[ "{$type}_{$field}" ] = $customer->{"get_{$type}_{$field}"}();
			}
		}

		$readdressed->set_props( $props );

		// get_taxable_location() is filtered (woocommerce_order_get_tax_location), so a callback can return a
		// non-array. Don't reuse rather than pass a bad value to the array-typed locations_match().
		$order_location       = $order_object->get_taxable_location();
		$readdressed_location = $readdressed->get_taxable_location();

		if ( ! is_array( $order_location ) || ! is_array( $readdressed_location ) ) {
			return false;
		}

		return $this->locations_match( $order_location, $readdressed_location );
	}

	/**
	 * Whether two locations match, reading a state the customer session filled in by default as empty.
	 *
	 * @param array $order_location    Location the order was priced for.
	 * @param array $customer_location Location the session customer would give it.
	 * @return bool
	 */
	private function locations_match( array $order_location, array $customer_location ): bool {
		if ( ! OrderController::locations_differ( $order_location, $customer_location ) ) {
			return true;
		}

		return ! OrderController::locations_differ( $this->without_default_state( $order_location ), $this->without_default_state( $customer_location ) );
	}

	/**
	 * Returns a location without the state the customer session fills in by default.
	 *
	 * The session gives a customer with no billing state the default customer location's state, whatever their
	 * country (WC_Customer_Data_Store_Session::set_defaults()), and saves it. A shopper who left an optional state
	 * empty has it on later requests while the order they placed has none. On another country, where it is not
	 * one of that country's states, such a state is read as empty so the same address still compares as equal.
	 *
	 * @param array $location Location with country and state keys.
	 * @return array
	 */
	private function without_default_state( array $location ): array {
		$country = strtoupper( (string) ( $location['country'] ?? '' ) );
		$state   = strtoupper( (string) ( $location['state'] ?? '' ) );

		if ( '' === $state ) {
			return $location;
		}

		$states = WC()->countries->get_states( $country );

		if ( is_array( $states ) && isset( $states[ $state ] ) ) {
			return $location;
		}

		$is_default_state = static function ( $candidate ) use ( $country, $state ): bool {
			return is_array( $candidate ) && strtoupper( (string) ( $candidate['state'] ?? '' ) ) === $state && strtoupper( (string) ( $candidate['country'] ?? '' ) ) !== $country;
		};

		// The default location is the shop base unless geolocation or a filter changes it. The base is checked first
		// because it needs no lookup, and because a state saved on an earlier request can outlive the default.
		if ( $is_default_state( wc_get_base_location() ) || $is_default_state( wc_get_customer_default_location() ) ) {
			$location['state'] = '';
		}

		return $location;
	}

	/**
	 * Whether a sync from the cart would keep the coupons the order counted. The cart hash does not cover coupons, and
	 * a sync that swapped or dropped a counted coupon would leave it counted with no order carrying it. An order that
	 * counted nothing, e.g. a failed one, may follow the cart. When the cart holds a coupon an extension supplies
	 * without a stored record, such as the one WooCommerce Subscriptions puts in place of a parent order's coupons, the
	 * order may follow the cart as long as the cart adds no stored coupon the order lacks. Compares formatted codes,
	 * not `_coupons_hash`, which only the Store API sync writes. The flag is read through the data store, because
	 * the order object may be one held since before its usage was counted.
	 *
	 * @param \WC_Order $order_object Order object to check.
	 * @return bool
	 */
	private function cart_keeps_counted_coupons( \WC_Order $order_object ): bool {
		$data_store = $order_object->get_data_store();

		if ( ! $data_store instanceof \WC_Data_Store || ! $data_store->has_callable( 'get_recorded_coupon_usage_counts' ) ) {
			return true;
		}

		// @phpstan-ignore method.notFound (the call is proxied by WC_Data_Store::__call() and guarded by has_callable() above)
		if ( ! $data_store->get_recorded_coupon_usage_counts( $order_object ) ) {
			return true;
		}

		$normalize = static function ( array $codes ): array {
			$codes = array_map( fn( $code ) => (string) wc_format_coupon_code( (string) $code ), $codes );
			$codes = array_unique( array_filter( $codes, fn( $code ) => ! StringUtil::is_null_or_whitespace( $code ) ) );
			sort( $codes, SORT_STRING );

			return $codes;
		};

		$order_codes = $normalize( $order_object->get_coupon_codes() );

		if ( $normalize( wc()->cart->get_applied_coupons() ) === $order_codes ) {
			return true;
		}

		$cart_stored_codes  = array();
		$has_extension_code = false;

		foreach ( wc()->cart->get_applied_coupons() as $code ) {
			if ( StringUtil::is_null_or_whitespace( (string) $code ) ) {
				continue;
			}

			if ( $this->is_stored_coupon_code( (string) $code ) ) {
				$cart_stored_codes[] = $code;
			} else {
				$has_extension_code = true;
			}
		}

		return $has_extension_code && ! array_diff( $normalize( $cart_stored_codes ), $order_codes );
	}

	/**
	 * Whether a coupon with this code is stored: one wc_get_coupon_id_by_code() finds, or a coupon post with the code
	 * in any status. wc_get_coupon_id_by_code() only finds published coupons, so a coupon trashed after the order was
	 * placed would otherwise pass for an extension's code.
	 *
	 * @param string $code Coupon code as applied to the cart.
	 * @return bool
	 */
	private function is_stored_coupon_code( string $code ): bool {
		global $wpdb;

		if ( wc_get_coupon_id_by_code( $code ) ) {
			return true;
		}

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE LOWER(post_title) = LOWER(%s) AND post_type = 'shop_coupon' LIMIT 1",
				wc_sanitize_coupon_code( $code )
			)
		);
	}

	/**
	 * Whether the order has no address yet and was not created through the Store API, e.g. one a merchant made for
	 * the shopper to fill in. It is taxed at the shop's base address and has no shipping zone yet, so, as in
	 * OrderController::validate_location_change(), its first address is allowed.
	 *
	 * @param \WC_Order $order_object Order object to check.
	 * @return bool
	 */
	private function order_awaits_first_address( \WC_Order $order_object ): bool {
		return ! $order_object->get_shipping_country() && ! $order_object->get_billing_country() && 'store-api' !== $order_object->get_created_via();
	}
}
