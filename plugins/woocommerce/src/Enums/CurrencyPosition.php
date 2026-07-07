<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the `woocommerce_currency_pos` option,
 * which determines where the currency symbol appears relative to the price.
 *
 * @since 11.0.0
 */
final class CurrencyPosition {
	/**
	 * Display the currency symbol to the left of the price.
	 *
	 * @var string
	 */
	public const LEFT = 'left';

	/**
	 * Display the currency symbol to the right of the price.
	 *
	 * @var string
	 */
	public const RIGHT = 'right';

	/**
	 * Display the currency symbol to the left of the price, separated by a space.
	 *
	 * @var string
	 */
	public const LEFT_SPACE = 'left_space';

	/**
	 * Display the currency symbol to the right of the price, separated by a space.
	 *
	 * @var string
	 */
	public const RIGHT_SPACE = 'right_space';
}
