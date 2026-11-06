<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * Splits shipping tax across the tax classes of the items shipped, in proportion to their value.
 *
 * @since 11.4.0
 */
class ShippingTaxSplit {

	/**
	 * Key for untaxed costs, in the shipping split and in the negative fee proration.
	 *
	 * The colon keeps it apart from tax class slugs, which sanitize_title() strips colons from, so a class slugged 'non-taxable' is still taxed.
	 */
	public const NON_TAXABLE = ':non-taxable';

	/**
	 * Meta key on a shipping rate, and the order line made from it, for its cost before tax per tax class.
	 */
	public const COSTS_META_KEY = '_tax_class_costs';

	/**
	 * Meta key on an order's shipping line for the value per tax class of the order's items when the line was split: at checkout, or on the last recalculation that split it.
	 */
	public const VALUES_META_KEY = '_tax_class_values';

	/**
	 * Option that turns the split on, while the shipping tax class is based on the cart items.
	 */
	public const OPTION = 'woocommerce_split_shipping_tax_by_item_value';

	/**
	 * Whether shipping tax is split by cart item value: the split is ticked and the shipping tax class is based on the cart items.
	 *
	 * @since 11.4.0
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === get_option( self::OPTION ) && 'inherit' === get_option( 'woocommerce_shipping_tax_class' );
	}

	/**
	 * Get the value per tax class of the items in a shipping package.
	 *
	 * @since 11.4.0
	 *
	 * @param array|false $package Package the rate is for, or false to use the cart.
	 * @return array<string, float>|null Value per tax class, or null when the split is off or the items have no value.
	 */
	public function get_package_values( $package ): ?array {
		if ( ! $this->is_enabled() ) {
			return null;
		}

		if ( isset( $package['contents'] ) && is_array( $package['contents'] ) ) {
			$contents = $package['contents'];
		} elseif ( WC()->cart instanceof \WC_Cart ) {
			$contents = WC()->cart->get_cart();
		} else {
			return null;
		}

		$values = array();

		foreach ( $contents as $cart_item ) {
			$product = $cart_item['data'] ?? null;

			if ( $product instanceof \WC_Product && $product->needs_shipping() ) {
				$tax_class            = $product->is_shipping_taxable() ? $product->get_tax_class() : self::NON_TAXABLE;
				$values[ $tax_class ] = ( $values[ $tax_class ] ?? 0 ) + self::get_item_value( $cart_item );
			}
		}

		return self::get_positive_values( $values );
	}

	/**
	 * Get the value per tax class of the items in an order.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Abstract_Order $order Order.
	 * @return array<string, float>|null Value per tax class, or null when the split is off or the items have no value.
	 */
	public function get_order_values( \WC_Abstract_Order $order ): ?array {
		if ( ! $this->is_enabled() ) {
			return null;
		}

		$values = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();

			if ( ! $product instanceof \WC_Product || $product->needs_shipping() ) {
				$is_shipping_taxable  = in_array( $item->get_tax_status(), array( ProductTaxStatus::TAXABLE, ProductTaxStatus::SHIPPING ), true );
				$tax_class            = $is_shipping_taxable ? $item->get_tax_class() : self::NON_TAXABLE;
				$values[ $tax_class ] = ( $values[ $tax_class ] ?? 0 ) + self::get_item_value( $item );
			}
		}

		return self::get_positive_values( $values );
	}

	/**
	 * Get the value per tax class a shipping rate was split by.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Shipping_Rate $rate Shipping rate.
	 * @return array<string, float>|null Value per tax class, or null when the split is off or the rate was not split.
	 */
	public function get_rate_values( \WC_Shipping_Rate $rate ): ?array {
		if ( ! $this->is_enabled() ) {
			return null;
		}

		$costs = $rate->get_meta_data()[ self::COSTS_META_KEY ] ?? null;

		return is_array( $costs ) ? self::get_positive_values( $costs ) : null;
	}

	/**
	 * Save the order's item values on a shipping line made at checkout, so a recalculation can tell when the items change.
	 *
	 * The order's line items are made from the cart first, so these are the cart's values. A line whose saved costs no longer add up to its
	 * cost came from a rate changed after it was split, such as in 'woocommerce_package_rates'. If its tax is nearer to what the split scaled to the
	 * new cost charges than to one class, the costs are scaled to match. Otherwise they are dropped and the line is taxed in one class, as the cart taxed it.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Order_Item_Shipping $item  Shipping line made from the chosen rate.
	 * @param \WC_Abstract_Order      $order Order being created.
	 * @return void
	 */
	public function save_checkout_values( \WC_Order_Item_Shipping $item, \WC_Abstract_Order $order ): void {
		$costs = $item->get_meta( self::COSTS_META_KEY );

		if ( is_array( $costs ) && NumberUtil::round( abs( NumberUtil::array_sum( $costs ) - (float) $item->get_total() ), wc_get_price_decimals() ) > 0 ) {
			$scaled_costs = $this->get_costs_scaled_to_line_tax( $costs, $item, $order );

			if ( null === $scaled_costs ) {
				$item->delete_meta_data( self::COSTS_META_KEY );
			} else {
				$item->update_meta_data( self::COSTS_META_KEY, $scaled_costs );
			}
		}

		$order_values = $this->get_order_values( $order );

		if ( null !== $order_values ) {
			$item->update_meta_data( self::VALUES_META_KEY, $order_values );
		}
	}

	/**
	 * Save the order's item values again on the shipping lines it already has, after its line items are made again from the cart.
	 *
	 * The Store API makes a checkout draft's line items again when the cart changes, and keeps its shipping lines while their rates are unchanged.
	 * Those lines still hold what the cart charged, so they take the new values rather than being split again by the order's items.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Abstract_Order $order Order whose line items were made from the cart.
	 * @return void
	 */
	public function save_checkout_values_on_shipping_lines( \WC_Abstract_Order $order ): void {
		if ( ! $order->get_id() || ! $this->is_enabled() ) {
			return;
		}

		foreach ( $order->get_shipping_methods() as $item ) {
			$this->save_checkout_values( $item, $order );
		}
	}

	/**
	 * Get the value per tax class to split an order's shipping line by, for a negative fee's share of it.
	 *
	 * While the order's items are as they were when the line was split, a line uses the costs it saved, as the cart did.
	 * Otherwise it is split by the order's items, as the cart splits a rate with no costs of its own.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Order_Item_Shipping $item  Shipping line.
	 * @param \WC_Abstract_Order      $order Order the line is on.
	 * @return array<string, float>|null Value per tax class, or null when the split is off or there is nothing to split by.
	 */
	public function get_shipping_line_values( \WC_Order_Item_Shipping $item, \WC_Abstract_Order $order ): ?array {
		if ( ! $this->is_enabled() ) {
			return null;
		}

		$order_values = $this->get_order_values( $order );

		if ( $this->has_items_as_split( $item, $order_values ) ) {
			return $this->get_saved_costs( $item ) ?? $order_values;
		}

		return $order_values;
	}

	/**
	 * Get the value per tax class to tax an order's shipping line by, when the order's taxes are recalculated.
	 *
	 * While the order's items are as they were when the line was split, a line uses the costs it saved, and a line with no
	 * costs of its own, such as a rate rebuilt by an extension, is taxed in one class as before the split. Both match the cart.
	 * Otherwise the line is split by the order's items, and the costs and item values used are saved on the line.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Order_Item_Shipping $item  Shipping line.
	 * @param \WC_Abstract_Order      $order Order the line is on.
	 * @return array<string, float>|null Value per tax class, or null to tax the line in one class.
	 */
	public function split_shipping_line( \WC_Order_Item_Shipping $item, \WC_Abstract_Order $order ): ?array {
		if ( ! $this->is_enabled() ) {
			$this->clear_split( $item );
			return null;
		}

		$order_values = $this->get_order_values( $order );

		if ( $this->has_items_as_split( $item, $order_values ) ) {
			$saved_costs = $this->get_saved_costs( $item );

			if ( null !== $saved_costs || ! is_array( $item->get_meta( self::COSTS_META_KEY ) ) ) {
				return $saved_costs;
			}
		}

		if ( null === $order_values ) {
			$this->clear_split( $item );
			return null;
		}

		$item->update_meta_data( self::COSTS_META_KEY, self::split_cost( (float) $item->get_total(), $order_values, false ) );
		$item->update_meta_data( self::VALUES_META_KEY, $order_values );

		return $order_values;
	}

	/**
	 * Split a cost in proportion to the values, rounded to the price decimals with any remainder on the largest part, or unrounded.
	 *
	 * An order line's cost is split unrounded: with shipping entered with tax its net parts have more decimals, and rounding them first can put the tax 0.01 off the cart.
	 *
	 * @since 11.4.0
	 *
	 * @param float                $cost   Cost to split.
	 * @param array<string, float> $values Positive value per tax class.
	 * @param bool                 $round  Whether to round the parts to the price decimals.
	 * @return array<string, float> Cost per tax class.
	 */
	public static function split_cost( float $cost, array $values, bool $round = true ): array {
		$total = NumberUtil::array_sum( $values );

		if ( ! $values || $total <= 0 ) {
			return array();
		}

		if ( ! $round ) {
			return array_map(
				static function ( $value ) use ( $cost, $total ): float {
					return $cost * (float) $value / $total;
				},
				$values
			);
		}

		$decimals = wc_get_price_decimals();
		$costs    = array();
		$largest  = array_key_first( $values );

		foreach ( $values as $tax_class => $value ) {
			$costs[ $tax_class ] = NumberUtil::round( $cost * $value / $total, $decimals );

			if ( $value > $values[ $largest ] ) {
				$largest = $tax_class;
			}
		}

		$costs[ $largest ] = NumberUtil::round( $costs[ $largest ] + $cost - NumberUtil::array_sum( $costs ), $decimals );

		return $costs;
	}

	/**
	 * Get the net cost per tax class a shipping line was taxed on, scaled to the line's total.
	 *
	 * Scaling keeps the parts in step with a total edited after the split, and gives a refund line its negative share.
	 * A refund line falls back to the costs saved on the line it refunds. Reads saved data only, so it works with the split off.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Order_Item_Shipping $item Shipping line or refunded shipping line.
	 * @return array<string, float>|null Cost per tax class, or null when the line was taxed in one class.
	 */
	public function get_charged_costs( \WC_Order_Item_Shipping $item ): ?array {
		$costs = $this->get_saved_costs( $item );

		if ( null === $costs && $item->get_meta( '_refunded_item_id' ) ) {
			$refunded_item = \WC_Order_Factory::get_order_item( absint( $item->get_meta( '_refunded_item_id' ) ) );
			$costs         = $refunded_item instanceof \WC_Order_Item_Shipping ? $this->get_saved_costs( $refunded_item ) : null;
		}

		if ( null === $costs ) {
			return null;
		}

		$scale = (float) $item->get_total() / NumberUtil::array_sum( $costs );

		return array_map(
			static function ( float $cost ) use ( $scale ): float {
				return $cost * $scale;
			},
			$costs
		);
	}

	/**
	 * Remove the split saved on a shipping line taxed in one class, so its costs no longer describe its taxes.
	 *
	 * @param \WC_Order_Item_Shipping $item Shipping line.
	 * @return void
	 */
	private function clear_split( \WC_Order_Item_Shipping $item ): void {
		$item->delete_meta_data( self::COSTS_META_KEY );
		$item->delete_meta_data( self::VALUES_META_KEY );
	}

	/**
	 * Whether the order's items have the values per tax class they had when the shipping line was split.
	 *
	 * @param \WC_Order_Item_Shipping   $item         Shipping line.
	 * @param array<string, float>|null $order_values Value per tax class of the order's items.
	 * @return bool
	 */
	private function has_items_as_split( \WC_Order_Item_Shipping $item, ?array $order_values ): bool {
		$decimals     = wc_get_price_decimals();
		$split_values = $item->get_meta( self::VALUES_META_KEY );

		if ( null === $order_values || ! is_array( $split_values ) || count( $split_values ) !== count( $order_values ) ) {
			return false;
		}

		foreach ( $order_values as $tax_class => $value ) {
			if ( ! isset( $split_values[ $tax_class ] ) || NumberUtil::round( (float) $split_values[ $tax_class ], $decimals ) !== NumberUtil::round( $value, $decimals ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Scale a rate's saved costs to its line's cost, when the line's tax is nearer to what the scaled costs are taxed than to what one class charges.
	 *
	 * A callback that scales a rate's cost and taxes, such as for a surcharge or another currency, keeps the split. One that taxes the new cost in one class does not.
	 * The cart charged the rate's taxes, so they tell the two apart. The one class is the one the order taxes the line in without the split.
	 *
	 * @param array                   $costs Cost per tax class saved on the rate.
	 * @param \WC_Order_Item_Shipping $item  Shipping line made from the rate.
	 * @param \WC_Abstract_Order      $order Order being created, with its line items.
	 * @return array<string, float>|null Scaled cost per tax class, or null when the line is taxed in one class.
	 */
	private function get_costs_scaled_to_line_tax( array $costs, \WC_Order_Item_Shipping $item, \WC_Abstract_Order $order ): ?array {
		$costs = self::get_positive_values( $costs );
		$total = (float) $item->get_total();

		if ( null === $costs || $total <= 0 ) {
			return null;
		}

		$scaled_costs = self::split_cost( $total, $costs, false );
		$split_tax    = 0.0;

		foreach ( $scaled_costs as $tax_class => $cost ) {
			if ( self::NON_TAXABLE !== $tax_class ) {
				$split_tax += self::get_net_shipping_tax( $cost, (string) $tax_class );
			}
		}

		$order_classes = array_intersect( array_merge( array( '' ), \WC_Tax::get_tax_class_slugs() ), $order->get_items_tax_classes() );
		$one_class_tax = $order_classes ? self::get_net_shipping_tax( $total, (string) current( $order_classes ) ) : 0.0;
		$line_tax      = (float) $item->get_total_tax();

		return abs( $line_tax - $split_tax ) <= abs( $line_tax - $one_class_tax ) ? $scaled_costs : null;
	}

	/**
	 * Get the shipping tax on a net cost in a tax class, as an order's shipping line is taxed, also with shipping entered with tax.
	 *
	 * @param float  $cost      Net cost.
	 * @param string $tax_class Tax class.
	 * @return float
	 */
	private static function get_net_shipping_tax( float $cost, string $tax_class ): float {
		return (float) NumberUtil::array_sum( \WC_Tax::calc_tax( $cost, \WC_Tax::get_shipping_tax_rates( $tax_class ), false ) );
	}

	/**
	 * Get the positive costs per tax class saved on a shipping line.
	 *
	 * @param \WC_Order_Item_Shipping $item Shipping line.
	 * @return array<string, float>|null Cost per tax class, or null when none are saved.
	 */
	private function get_saved_costs( \WC_Order_Item_Shipping $item ): ?array {
		$costs = $item->get_meta( self::COSTS_META_KEY );

		return is_array( $costs ) ? self::get_positive_values( $costs ) : null;
	}

	/**
	 * Get an item's value for the split: its price before tax, after discounts.
	 *
	 * @param array|\WC_Order_Item_Product $item Cart item or order line item.
	 * @return float
	 */
	private static function get_item_value( $item ): float {
		return (float) ( $item instanceof \WC_Order_Item_Product ? $item->get_total() : ( $item['line_total'] ?? 0 ) );
	}

	/**
	 * Keep the positive values.
	 *
	 * @param array $values Value per tax class.
	 * @return array<string, float>|null The positive values, or null when there are none.
	 */
	private static function get_positive_values( array $values ): ?array {
		$values = array_filter(
			array_map( 'floatval', $values ),
			static function ( float $value ): bool {
				return $value > 0;
			}
		);

		return $values ? $values : null;
	}
}
