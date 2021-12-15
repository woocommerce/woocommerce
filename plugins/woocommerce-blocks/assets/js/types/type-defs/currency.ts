export interface Currency {
	/**
	 * ISO 4217 Currency Code
	 */
	code: string; // @todo create a list of allowed currency codes
	/**
	 * String which separates the decimals from the integer
	 */
	decimalSeparator: string;
	/**
	 * @todo Description of this currently unknown
	 */
	minorUnit: number;
	/**
	 * String to prefix the currency with.
	 *
	 * This property is generally exclusive with `suffix`.
	 */
	prefix: string;
	/**
	 * String to suffix the currency with.
	 *
	 * This property is generally exclusive with `prefix`.
	 */
	suffix: string;
	/**
	 * Currency symbol
	 */
	symbol: string; // @todo create a list of allowed currency symbols
	/**
	 * String which separates the thousands
	 */
	thousandSeparator: string;
}

export interface CurrencyResponse {
	currency_code: string;
	currency_symbol: string;
	currency_minor_unit: number;
	currency_decimal_separator: string;
	currency_thousand_separator: string;
	currency_prefix: string;
	currency_suffix: string;
}

export type SymbolPosition = 'left' | 'left_space' | 'right' | 'right_space';
