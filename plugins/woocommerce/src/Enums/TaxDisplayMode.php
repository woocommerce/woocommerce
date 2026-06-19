<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the `woocommerce_tax_display_shop` and
 * `woocommerce_tax_display_cart` options, which control whether prices are
 * shown including or excluding tax.
 *
 * @since 11.0.0
 */
final class TaxDisplayMode {
	/**
	 * Prices displayed including tax.
	 *
	 * @var string
	 */
	public const INCLUSIVE = 'incl';

	/**
	 * Prices displayed excluding tax.
	 *
	 * @var string
	 */
	public const EXCLUSIVE = 'excl';
}
