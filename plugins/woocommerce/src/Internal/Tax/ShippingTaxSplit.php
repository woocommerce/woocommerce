<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Enums\ShippingTaxClass;
use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * Splits shipping tax across the tax classes of the items shipped, in proportion to their value.
 *
 * @since 11.4.0
 */
class ShippingTaxSplit {

	/**
	 * Key for the value of items whose shipping is not taxed, as in the negative fee proration.
	 */
	const NON_TAXABLE = 'non-taxable';

	/**
	 * Meta key on a shipping rate, and the order line made from it, for its cost before tax per tax class.
	 */
	const COSTS_META_KEY = '_tax_class_costs';

	/**
	 * Whether the shipping tax class is set to split by cart item value.
	 *
	 * @since 11.4.0
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return ShippingTaxClass::SPLIT_BY_ITEM_VALUE === get_option( 'woocommerce_shipping_tax_class' );
	}

	/**
	 * Whether a shipping tax class setting takes the class from the items, by picking one or by splitting.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $shipping_tax_class Value of the woocommerce_shipping_tax_class option.
	 * @return bool
	 */
	public static function is_based_on_items( $shipping_tax_class ): bool {
		return in_array( $shipping_tax_class, array( ShippingTaxClass::INHERIT, ShippingTaxClass::SPLIT_BY_ITEM_VALUE ), true );
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
	 * Get the value per tax class to split an order's shipping line by.
	 *
	 * A line made at checkout keeps the split of its package. Any other line is split by the order's items.
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

		$costs = $item->get_meta( self::COSTS_META_KEY );

		return ( is_array( $costs ) ? self::get_positive_values( $costs ) : null ) ?? $this->get_order_values( $order );
	}

	/**
	 * Split a cost in proportion to the values, rounded to the price decimals, with any remainder on the largest part.
	 *
	 * @since 11.4.0
	 *
	 * @param float                $cost   Cost to split.
	 * @param array<string, float> $values Positive value per tax class.
	 * @return array<string, float> Cost per tax class.
	 */
	public static function split_cost( float $cost, array $values ): array {
		$total = NumberUtil::array_sum( $values );

		if ( ! $values || $total <= 0 ) {
			return array();
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
