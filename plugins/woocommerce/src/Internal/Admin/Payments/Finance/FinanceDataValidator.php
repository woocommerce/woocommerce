<?php
/**
 * Finance data validator.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Payments\Finance;

defined( 'ABSPATH' ) || exit;

/**
 * Validation helpers shared by the finance data value objects.
 *
 * @internal
 */
class FinanceDataValidator {

	/**
	 * Normalize a currency code to uppercase and check it is a three-letter ISO 4217 code.
	 *
	 * @param string $currency The currency code.
	 * @return string The uppercase currency code.
	 * @throws \InvalidArgumentException When the currency code is not three letters.
	 *
	 * @since 11.2.0
	 */
	public static function normalize_currency( string $currency ): string {
		$currency = strtoupper( trim( $currency ) );
		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) {
			throw new \InvalidArgumentException( 'The currency must be a three-letter ISO 4217 code.' );
		}

		return $currency;
	}

	/**
	 * Check that an amount is a decimal string such as "1234.56" or "-10".
	 *
	 * @param string $amount The amount.
	 * @return string The amount, unchanged.
	 * @throws \InvalidArgumentException When the amount is not a decimal string.
	 *
	 * @since 11.2.0
	 */
	public static function validate_amount( string $amount ): string {
		if ( ! preg_match( '/^-?\d+(\.\d+)?$/', $amount ) ) {
			throw new \InvalidArgumentException( 'The amount must be a decimal string in major currency units, such as "1234.56".' );
		}

		return $amount;
	}
}
