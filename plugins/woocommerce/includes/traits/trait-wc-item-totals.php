<?php
/**
 * This ongoing trait will have shared calculation logic between WC_Abstract_Order and WC_Cart_Totals classes.
 *
 * @package WooCommerce\Traits
 * @version 3.9.0
 */

use Automattic\WooCommerce\Utilities\NumberUtil;

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
	 * @param array $values Values to round. Should be with precision.
	 *
	 * @return float|int Appropriately rounded value.
	 */
	public static function get_rounded_items_total( $values ) {
		return array_sum(
			array_map(
				array( self::class, 'round_item_subtotal' ),
				$values
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
	public static function round_item_subtotal( $value ) {
		if ( ! self::round_at_subtotal() ) {
			$value = NumberUtil::round( $value );
		}
		return $value;
	}

	/**
	 * Should always round at subtotal?
	 *
	 * @since 3.9.0
	 * @return bool
	 */
	protected static function round_at_subtotal() {
		return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
	}

	/**
	 * Apply rounding to an array of taxes before summing. Rounds to store DP setting, ignoring precision.
	 *
	 * The `$context` argument is forwarded to the `wc_round_tax_total` filter
	 * so consumers can distinguish line tax calls (which may be made with
	 * values multiplied by 100 for precision) from cart-facing totals. When
	 * `$in_cents` is true, the context is suffixed with `_in_cents` to make
	 * the unit explicit to filter callbacks.
	 *
	 * @since  3.2.6
	 * @since  10.9.0 Added the `$context` argument and `_in_cents` suffix.
	 * @param  float  $value    Tax value.
	 * @param  bool   $in_cents Whether precision of value is in cents.
	 * @param  string $context  Optional. Call site identifier passed through to the
	 *                          `wc_round_tax_total` filter. Defaults to `'line_tax'`.
	 * @return float
	 */
	protected static function round_line_tax( $value, $in_cents = true, $context = 'line_tax' ) {
		if ( ! self::round_at_subtotal() ) {
			$value = wc_round_tax_total( $value, $in_cents ? 0 : null, $context . ( $in_cents ? '_in_cents' : '' ) );
		}
		return $value;
	}

}
