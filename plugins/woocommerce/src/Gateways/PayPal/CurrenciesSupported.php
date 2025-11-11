<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways\PayPal;

/**
 * Class CurrenciesSupported
 *
 * This helper class checks whether a currency is supported by PayPal.
 * This was built based on {@see https://developer.paypal.com/docs/reports/reference/paypal-supported-currencies/}
 */
class CurrenciesSupported {
	/**
	 * The single instance of the class.
	 *
	 * @var CurrenciesSupported
	 */
	protected static $instance = null;

	/**
	 * List of currencies supported by PayPal.
	 *
	 * @var array<string>
	 */
	const SUPPORTED_CURRENCIES = [
		'AUD', // Australian Dollar
		'BRL', // Brazilian Real
		'CAD', // Canadian Dollar
		'CNY', // Chinese Renminbi
		'CZK', // Czech Koruna
		'DKK', // Danish Krone
		'EUR', // Euro
		'HKD', // Hong Kong Dollar
		'HUF', // Hungarian Forint
		'ILS', // Israeli New Shekel
		'JPY', // Japanese Yen
		'MYR', // Malaysian Ringgit
		'MXN', // Mexican Peso
		'TWD', // New Taiwan Dollar
		'NZD', // New Zealand Dollar
		'NOK', // Norwegian Krone
		'PHP', // Philippine Peso
		'PLN', // Polish Złoty
		'GBP', // Pound Sterling
		'SGD', // Singapore Dollar
		'SEK', // Swedish Krona
		'CHF', // Swiss Franc
		'THB', // Thai Baht
		'USD', // United States Dollar
	];

	/**
	 * Get class instance.
	 *
	 * @return CurrenciesSupported Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Check if PayPal supports a currency code.
	 *
	 * @param string $currency_code The currency code to check.
	 * @return bool
	 */
	public function is_currency_supported( string $currency_code ): bool {
		return in_array( strtoupper( $currency_code ), self::SUPPORTED_CURRENCIES, true );
	}
}
