<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the fixed values of the `woocommerce_shipping_tax_class` option.
 * Any other value is the slug of the tax class that all shipping gets.
 *
 * @since 11.4.0
 */
final class ShippingTaxClass {
	/**
	 * Shipping gets one tax class picked from the cart items.
	 *
	 * @var string
	 */
	public const INHERIT = 'inherit';

	/**
	 * Shipping tax is split across the tax classes of the cart items, in proportion to their value.
	 *
	 * The colon keeps it apart from tax class slugs, which sanitize_title() strips colons from, so a class slugged 'split_by_item_value' can still be the shipping tax class.
	 *
	 * @var string
	 */
	public const SPLIT_BY_ITEM_VALUE = ':split_by_item_value';
}
