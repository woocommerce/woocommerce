<?php
namespace Automattic\WooCommerce\StoreApi\Formatters;

use Automattic\WooCommerce\Enums\CurrencyPosition;
use Automattic\WooCommerce\Internal\Utilities\CurrencySymbol;
use Automattic\WooCommerce\Internal\Utilities\PriceSeparators;
use Automattic\WooCommerce\Currency\ActiveCurrency;

/**
 * Currency Formatter.
 *
 * Formats an array of monetary values by inserting currency data.
 */
class CurrencyFormatter implements FormatterInterface {
	/**
	 * Format a given value and return the result.
	 *
	 * @param array $value Value to format.
	 * @param array $options Options that influence the formatting.
	 * @return array
	 */
	public function format( $value, array $options = [] ) {
		$currency = ActiveCurrency::get() ?? get_woocommerce_currency();
		$position = CurrencySymbol::get_position();
		$symbol   = CurrencySymbol::get_symbol( $currency );
		$prefix   = '';
		$suffix   = '';

		switch ( $position ) {
			case CurrencyPosition::LEFT_SPACE:
				$prefix = $symbol . ' ';
				break;
			case CurrencyPosition::LEFT:
				$prefix = $symbol;
				break;
			case CurrencyPosition::RIGHT_SPACE:
				$suffix = ' ' . $symbol;
				break;
			case CurrencyPosition::RIGHT:
				$suffix = $symbol;
				break;
		}

		return array_merge(
			(array) $value,
			[
				'currency_code'               => $currency,
				'currency_symbol'             => $symbol,
				'currency_minor_unit'         => wc_get_price_decimals(),
				'currency_decimal_separator'  => PriceSeparators::get_decimal(),
				'currency_thousand_separator' => PriceSeparators::get_thousand(),
				'currency_prefix'             => $prefix,
				'currency_suffix'             => $suffix,
			]
		);
	}
}
