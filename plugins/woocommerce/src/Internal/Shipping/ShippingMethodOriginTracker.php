<?php
/**
 * ShippingMethodOriginTracker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Shipping;

/**
 * Tracks who picked the currently chosen shipping method for a package: the
 * auto-defaulter in `wc_get_chosen_shipping_method_for_package()` ('auto') or the
 * customer via an explicit selection path like the Store API select-shipping-rate
 * route, the shortcode cart/checkout AJAX, or a coupon-driven free-shipping
 * override ('manual').
 *
 * This signal lets `wc_get_default_shipping_method_for_package()` distinguish
 * between "the customer chose Local Pickup" (sticky on purpose) and "WC defaulted
 * to Local Pickup because no zone matched yet" (should re-evaluate once a real
 * shipping rate becomes available, e.g. after an Apple Pay / Google Pay wallet
 * supplies an address).
 *
 * @since 11.1.0
 */
class ShippingMethodOriginTracker {

	/**
	 * Session key holding the recorded origins, parallel to `chosen_shipping_methods`.
	 *
	 * @var string
	 */
	public const SESSION_KEY = 'chosen_shipping_method_origins';

	/**
	 * Origin value for a method assigned by the auto-defaulter.
	 *
	 * @var string
	 */
	public const ORIGIN_AUTO = 'auto';

	/**
	 * Origin value for a method explicitly selected by the customer.
	 *
	 * @var string
	 */
	public const ORIGIN_MANUAL = 'manual';

	/**
	 * Records the origin of the currently chosen shipping method for a package.
	 *
	 * Origin is tied to the rate_id it was recorded against, so that a third-party
	 * caller writing to `chosen_shipping_methods` directly (bypassing the Store API
	 * and the AJAX endpoints) implicitly invalidates a stale 'auto' marker — the
	 * recorded rate_id no longer matches the current chosen rate, and the reader
	 * falls back to 'manual'.
	 *
	 * @since 11.1.0
	 *
	 * @param int|string $key     Package key.
	 * @param string     $origin  Either 'auto' or 'manual'.
	 * @param string     $rate_id The rate_id this origin applies to.
	 * @return void
	 */
	public function set_origin( $key, $origin, $rate_id ) {
		if ( ! is_callable( array( WC()->session, 'get' ) ) ) {
			return;
		}

		if ( ! in_array( $origin, array( self::ORIGIN_AUTO, self::ORIGIN_MANUAL ), true ) ) {
			return;
		}

		$origins         = WC()->session->get( self::SESSION_KEY, array() );
		$origins[ $key ] = array(
			'rate_id' => (string) $rate_id,
			'origin'  => $origin,
		);
		WC()->session->set( self::SESSION_KEY, $origins );
	}

	/**
	 * Records a 'manual' origin for a rate the customer explicitly selected, unless the
	 * selection is a repeat of the package's currently chosen rate.
	 *
	 * Selection UIs re-assert the current choice without a customer decision — the block
	 * checkout's pickup-options block re-posts it on mount, and the classic checkout
	 * re-posts it on every order-review refresh (page load, address field edits). Recording
	 * such a repeat as 'manual' would launder an auto-defaulted Local Pickup and defeat the
	 * unstick (and the selected_rate_origin field gateways consume), so only a change of
	 * rate counts; repeats keep the current origin.
	 *
	 * Call this BEFORE writing the new rate to `chosen_shipping_methods` — the repeat check
	 * compares against the rate currently in the session.
	 *
	 * @since 11.1.0
	 *
	 * @param int|string $key     Package key.
	 * @param string     $rate_id The rate_id the customer selected.
	 * @return void
	 */
	public function record_manual_selection( $key, $rate_id ) {
		if ( ! is_callable( array( WC()->session, 'get' ) ) ) {
			return;
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		$current_rate   = isset( $chosen_methods[ $key ] ) && is_string( $chosen_methods[ $key ] ) ? $chosen_methods[ $key ] : '';

		if ( (string) $rate_id === $current_rate ) {
			return;
		}

		$this->set_origin( $key, self::ORIGIN_MANUAL, $rate_id );
	}

	/**
	 * Returns the recorded origin ('auto' or 'manual') of the chosen shipping method
	 * for a package.
	 *
	 * Falls back to 'manual' when:
	 *
	 * - no origin has been recorded yet (preserves the historical sticky-Local-Pickup
	 *   behavior for sessions that pre-date origin tracking); or
	 * - the recorded rate_id no longer matches the chosen rate currently in
	 *   `chosen_shipping_methods` (something other than the tracked write paths
	 *   overwrote the choice, so the recorded 'auto' marker is stale and we treat
	 *   the new choice as deliberate).
	 *
	 * @since 11.1.0
	 *
	 * @param int|string $key Package key.
	 * @return string 'auto' or 'manual'.
	 */
	public function get_origin( $key ) {
		if ( ! is_callable( array( WC()->session, 'get' ) ) ) {
			return self::ORIGIN_MANUAL;
		}

		$origins = WC()->session->get( self::SESSION_KEY, array() );
		$entry   = isset( $origins[ $key ] ) ? $origins[ $key ] : null;

		if ( ! is_array( $entry ) || ! isset( $entry['rate_id'], $entry['origin'] ) ) {
			return self::ORIGIN_MANUAL;
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		$current_rate   = isset( $chosen_methods[ $key ] ) ? (string) $chosen_methods[ $key ] : '';

		// If the chosen rate has been overwritten by something that didn't go through
		// `set_origin()`, treat the new choice as manual.
		if ( $current_rate !== (string) $entry['rate_id'] ) {
			return self::ORIGIN_MANUAL;
		}

		return self::ORIGIN_AUTO === $entry['origin'] ? self::ORIGIN_AUTO : self::ORIGIN_MANUAL;
	}

	/**
	 * Returns the origin of the package's currently chosen shipping rate, or null when
	 * no rate is chosen for the package (or no session is available).
	 *
	 * Reads the session directly and is side-effect free, unlike
	 * `wc_get_chosen_shipping_method_for_package()`, which can write a new default into
	 * the session as it resolves.
	 *
	 * @since 11.1.0
	 *
	 * @param int|string $key Package key.
	 * @return string|null 'auto', 'manual', or null when no rate is chosen.
	 */
	public function get_origin_for_chosen_rate( $key ) {
		if ( ! is_callable( array( WC()->session, 'get' ) ) ) {
			return null;
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );

		if ( empty( $chosen_methods[ $key ] ) ) {
			return null;
		}

		return $this->get_origin( $key );
	}
}
