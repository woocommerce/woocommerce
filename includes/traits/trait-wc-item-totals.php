<?php
/**
 * This ongoing trait will have shared calculation logic between WC_Abstract_Order and WC_Cart_Totals classes.
 *
 * @package WooCommerce/Traits
 * @version 3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait WC_Item_Totals.
 *
 * Right now this do not have much, but plan is to eventually move all shared calculation logic between Orders and Cart in this file.
 *
 * @since 3.9.0
 */
trait WC_Item_Totals {

	/**
	 * Line items to calculate. Define in child class.
	 *
	 * @since 3.9.0
	 * @param string $field Field name to calculate upon.
	 *
	 * @return array having `total`|`subtotal` property.
	 */
	abstract protected function get_values_for_total( $field );

	/**
	 * Return rounded total based on settings. Will be used by Cart and Orders.
	 *
	 * @since 3.9.0
	 *
	 * @param string $field Field to round and sum based on setting. Will likely be `total` or `subtotal`. Values must be without precision.
	 *
	 * @return float|int Appropriately rounded value.
	 */
	protected function get_rounded_items_total( $field = 'total' ) {
		return array_sum(
			array_map(
				array( $this, 'round_item_subtotal' ),
				$this->get_values_for_total( $field )
			)
		);
	}

	/**
	 * Apply rounding to item subtotal before summing.
	 *
	 * @since 3.9.0
	 * @param float $value Item subtotal value.
	 * @return float
	 */
	protected function round_item_subtotal( $value ) {
		if ( ! $this->round_at_subtotal() ) {
			$value = round( $value );
		}
		return $value;
	}

	/**
	 * Should always round at subtotal?
	 *
	 * @since 3.9.0
	 * @return bool
	 */
	protected function round_at_subtotal() {
		return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
	}
}
