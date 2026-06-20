<?php
/**
 * WooPaymentsCurrencyUtils class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * WooPayments currency helpers for provider-boundary amount handling.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
final class WooPaymentsCurrencyUtils {

	/**
	 * Stripe zero-decimal currencies.
	 *
	 * @var string[]
	 */
	private const ZERO_DECIMAL_CURRENCIES = array(
		'bif',
		'clp',
		'djf',
		'gnf',
		'jpy',
		'kmf',
		'krw',
		'mga',
		'pyg',
		'rwf',
		'vnd',
		'vuv',
		'xaf',
		'xof',
		'xpf',
	);

	/**
	 * Tell whether the currency uses zero decimal places at the provider boundary.
	 *
	 * @param string $currency Currency code.
	 * @return bool
	 */
	public static function is_zero_decimal_currency( string $currency ): bool {
		return in_array( strtolower( $currency ), self::ZERO_DECIMAL_CURRENCIES, true );
	}

	/**
	 * Get the Stripe minor-unit decimal count for a currency.
	 *
	 * Returns 0 for true zero-decimal currencies and 2 for everything else,
	 * including Stripe special-case currencies that WooCommerce can display
	 * without decimals while Stripe still expects two-decimal minor units.
	 *
	 * @param string $currency Currency code.
	 * @return int
	 */
	public static function get_stripe_minor_unit_for_currency( string $currency ): int {
		return self::is_zero_decimal_currency( $currency ) ? 0 : 2;
	}
}
