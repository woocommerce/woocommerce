<?php
/**
 * OrderPriceFormatter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

use WC_Abstract_Order;
use WC_Order_Item;

/**
 * Helper class for formatting prices in order emails.
 *
 * @internal Just for internal use.
 */
class OrderPriceFormatter {

	/**
	 * Gets item subtotal - formatted for display in emails.
	 *
	 * @param WC_Abstract_Order $order Order instance.
	 * @param WC_Order_Item     $item Item to get unit price from.
	 * @param string            $tax_display 'incl' or 'excl' tax display mode.
	 * @return string Formatted item subtotal.
	 */
	public static function get_formatted_item_subtotal( WC_Abstract_Order $order, WC_Order_Item $item, string $tax_display ): string {
		$includes_tax  = 'excl' !== $tax_display;
		$item_subtotal = $order->get_item_subtotal( $item, $includes_tax );
		return self::format_price( $order, $item_subtotal, $includes_tax );
	}

	/**
	 * Helper method to format price with or without tax.
	 *
	 * @param WC_Abstract_Order $order Order instance.
	 * @param float             $amount The amount to format.
	 * @param bool              $includes_tax Whether to include tax in the formatted price.
	 * @return string Formatted price string.
	 */
	private static function format_price( WC_Abstract_Order $order, float $amount, bool $includes_tax ): string {
		return wc_price(
			$amount,
			array(
				'ex_tax_label' => ( ! $includes_tax && $order->get_prices_include_tax() ) ? 1 : 0,
				'currency'     => $order->get_currency(),
			)
		);
	}
}
