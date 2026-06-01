<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the `woocommerce_tax_based_on` option,
 * which determines which address is used to calculate tax.
 *
 * @since 10.8.0
 */
final class TaxBasedOn {
	/**
	 * Tax is calculated based on the customer's shipping address.
	 *
	 * @var string
	 */
	public const SHIPPING = 'shipping';

	/**
	 * Tax is calculated based on the customer's billing address.
	 *
	 * @var string
	 */
	public const BILLING = 'billing';

	/**
	 * Tax is calculated based on the shop's base address.
	 *
	 * @var string
	 */
	public const BASE = 'base';
}
