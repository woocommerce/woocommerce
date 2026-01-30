<?php

namespace Automattic\WooCommerce\Enums;

/**
 * Defines the valid values for the WooCommerce option
 * `woocommerce_tax_based_on`.
 *
 * This option determines which address is used
 * as the basis for tax calculation.
 *
 * Values supported by the WooCommerce core:
 * - base
 * - billing
 * - shipping
 *
 * @since 1.0.0
 */
final class Tax_Based_On {

	/**
	 * Taxes are calculated based on the shop base address.
	 *
	 * WooCommerce → Settings → Tax → Based on → Shop base address
	 */
	public const BASE = 'base';

	/**
	 * Taxes are calculated based on the customer's billing address.
	 *
	 * WooCommerce → Settings → Tax → Based on → Customer billing address
	 */
	public const BILLING = 'billing';

	/**
	 * Taxes are calculated based on the customer's shipping address.
	 *
	 * WooCommerce → Settings → Tax → Based on → Customer shipping address
	 */
	public const SHIPPING = 'shipping';
}
