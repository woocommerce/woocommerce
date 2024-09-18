/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { sprintf } from '@wordpress/i18n';
import { NumberConfig, numberFormat } from '@woocommerce/number';
import deprecated from '@wordpress/deprecated';

/**
 * @typedef {import('@woocommerce/number').NumberConfig} NumberConfig
 */
/**
 * @typedef {Object} CurrencyProps
 * @property {string} code           Currency ISO code.
 * @property {string} symbol         Symbol, can be multi-character.
 * @property {string} symbolPosition Where the symbol should be relative to the amount. One of `'left' | 'right' | 'left_space | 'right_space'`.
 * @typedef {NumberConfig & CurrencyProps} CurrencyConfig
 */

export type SymbolPosition = 'left' | 'right' | 'left_space' | 'right_space';

export type CurrencyProps = {
	code: string;
	symbol: string;
	symbolPosition: SymbolPosition;
	priceFormat?: string;
};

export type CurrencyConfig = Partial< NumberConfig & CurrencyProps >;

export type Currency = {
	code: string;
	symbol: string;
	symbolPosition: string;
	decimalSeparator: string;
	priceFormat: string;
	thousandSeparator: string;
	precision: number;
};

export type CountryInfo = {
	// https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/i18n/locale-info.php#L15-L28
	currency_code: string;
	currency_pos: SymbolPosition;
	thousand_sep: string;
	decimal_sep: string;
	num_decimals: number;
	weight_unit: string;
	dimension_unit: string;
	direction: string;
	default_locale: string;
	name: string;
	singular: string;
	plural: string;
	short_symbol: string;
	locales: string[];
};

/**
 *
 * @param {CurrencyConfig} currencySetting
 * @return {Object} currency object
 */
const CurrencyFactoryBase = function ( currencySetting?: CurrencyConfig ) {
	let currency: Currency;

	function stripTags( str: string ) {
		// sanitize Polyfill - see https://github.com/WordPress/WordPress/blob/master/wp-includes/js/wp-sanitize.js
		const strippedStr = str
			.replace( /<!--[\s\S]*?(-->|$)/g, '' )
			.replace( /<(script|style)[^>]*>[\s\S]*?(<\/\1>|$)/gi, '' )
			.replace( /<\/?[a-z][\s\S]*?(>|$)/gi, '' );

		if ( strippedStr !== str ) {
			return stripTags( strippedStr );
		}

		return strippedStr;
	}

	/**
	 * Get the default price format from a currency.
	 *
	 * @param {CurrencyConfig} config Currency configuration.
	 * @return {string} Price format.
	 */
	function getPriceFormat( config: CurrencyConfig ) {
		if ( config.priceFormat ) {
			return stripTags( config.priceFormat.toString() );
		}

		switch ( config.symbolPosition ) {
			case 'left':
				return '%1$s%2$s';
			case 'right':
				return '%2$s%1$s';
			case 'left_space':
				return '%1$s %2$s';
			case 'right_space':
				return '%2$s %1$s';
		}

		return '%1$s%2$s';
	}

	function setCurrency( setting?: CurrencyConfig ) {
		const defaultCurrency = {
			code: 'USD',
			symbol: '$',
			symbolPosition: 'left' as const,
			thousandSeparator: ',',
			decimalSeparator: '.',
			precision: 2,
		};
		const config = { ...defaultCurrency, ...setting };

		let precision = config.precision;
		if ( precision === null ) {
			// eslint-disable-next-line no-console
			console.warn( 'Currency precision is null' );
			// eslint-enable-next-line no-console

			precision = NaN;
		} else if ( typeof precision === 'string' ) {
			precision = parseInt( precision, 10 );
		}

		currency = {
			code: config.code.toString(),
			symbol: config.symbol.toString(),
			symbolPosition: config.symbolPosition.toString(),
			decimalSeparator: config.decimalSeparator.toString(),
			priceFormat: getPriceFormat( config ),
			thousandSeparator: config.thousandSeparator.toString(),
			precision,
		};
	}

	/**
	 * Formats money value.
	 *
	 * @param {number|string} number          number to format
	 * @param {boolean}       [useCode=false] Set to `true` to use the currency code instead of the symbol.
	 * @return {?string} A formatted string.
	 */
	function formatAmount( number: number | string, useCode = false ) {
		const formattedNumber = numberFormat( currency, number );

		if ( formattedNumber === '' ) {
			return formattedNumber;
		}

		const { priceFormat, symbol, code } = currency;

		// eslint-disable-next-line @wordpress/valid-sprintf
		return sprintf( priceFormat, useCode ? code : symbol, formattedNumber );
	}

	/**
	 * Formats money value.
	 *
	 * @deprecated
	 * @param {number|string} number number to format
	 * @return {?string} A formatted string.
	 */
	function formatCurrency( number: number | string ) {
		deprecated( 'Currency().formatCurrency', {
			version: '5.0.0',
			alternative: 'Currency().formatAmount',
			plugin: 'WooCommerce',
			hint: '`formatAmount` accepts the same arguments as formatCurrency',
		} );
		return formatAmount( number );
	}

	/**
	 * Get formatted data for a country from supplied locale and symbol info.
	 *
	 * @param {string} countryCode     Country code.
	 * @param {Object} localeInfo      Locale info by country code.
	 * @param {Object} currencySymbols Currency symbols by symbol code.
	 * @return {CurrencyConfig | {}} Formatted currency data for country.
	 */
	function getDataForCountry(
		countryCode: string,
		localeInfo: Record< string, CountryInfo | undefined > = {},
		currencySymbols: Record< string, string | undefined > = {}
	): CurrencyConfig | Record< string, never > {
		const countryInfo = localeInfo[ countryCode ];
		if ( ! countryInfo ) {
			return {};
		}

		const symbol = currencySymbols[ countryInfo.currency_code ];
		if ( ! symbol ) {
			return {};
		}

		return {
			code: countryInfo.currency_code,
			symbol: decodeEntities( symbol ),
			symbolPosition: countryInfo.currency_pos,
			thousandSeparator: countryInfo.thousand_sep,
			decimalSeparator: countryInfo.decimal_sep,
			precision: countryInfo.num_decimals,
		};
	}

	setCurrency( currencySetting );

	return {
		getCurrencyConfig: () => {
			return { ...currency };
		},
		getDataForCountry,
		setCurrency,
		formatAmount,
		formatCurrency,
		getPriceFormat,

		/**
		 * Get the rounded decimal value of a number at the precision used for the current currency.
		 * This is a work-around for fraction-cents, meant to be used like `wc_format_decimal`
		 *
		 * @param {number|string} number A floating point number (or integer), or string that converts to a number
		 * @return {number} The original number rounded to a decimal point
		 */
		formatDecimal( number: number | string ) {
			if ( typeof number !== 'number' ) {
				number = parseFloat( number );
			}
			if ( Number.isNaN( number ) ) {
				return 0;
			}
			const { precision } = currency;
			return (
				Math.round( number * Math.pow( 10, precision ) ) /
				Math.pow( 10, precision )
			);
		},

		/**
		 * Get the string representation of a floating point number to the precision used by the current currency.
		 * This is different from `formatAmount` by not returning the currency symbol.
		 *
		 * @param {number|string} number A floating point number (or integer), or string that converts to a number
		 * @return {string}               The original number rounded to a decimal point
		 */
		formatDecimalString( number: number | string ) {
			if ( typeof number !== 'number' ) {
				number = parseFloat( number );
			}
			if ( Number.isNaN( number ) ) {
				return '';
			}
			const { precision } = currency;
			return number.toFixed( precision );
		},

		/**
		 * Render a currency for display in a component.
		 *
		 * @param {number|string} number A floating point number (or integer), or string that converts to a number
		 * @return {Node|string} The number formatted as currency and rendered for display.
		 */
		render( number: number | string ) {
			if ( typeof number !== 'number' ) {
				number = parseFloat( number );
			}
			if ( number < 0 ) {
				return (
					<span className="is-negative">
						{ formatAmount( number ) }
					</span>
				);
			}
			return formatAmount( number );
		},
	};
};

export const CurrencyFactory = CurrencyFactoryBase;

/**
 * Returns currency data by country/region. Contains code, symbol, position, thousands separator, decimal separator, and precision.
 *
 * Dev Note: When adding new currencies below, the exchange rate array should also be updated in WooCommerce Admin's `business-details.js`.
 *
 * @deprecated
 * @return {Object} Currency data.
 */
export function getCurrencyData() {
	deprecated( 'getCurrencyData', {
		version: '3.1.0',
		alternative: 'CurrencyFactory.getDataForCountry',
		plugin: 'WooCommerce Admin',
		hint: 'Pass in the country, locale data, and symbol info to use getDataForCountry',
	} );

	// See https://github.com/woocommerce/woocommerce-admin/issues/3101.
	return {
		US: {
			code: 'USD',
			symbol: '$',
			symbolPosition: 'left',
			thousandSeparator: ',',
			decimalSeparator: '.',
			precision: 2,
		},
		EU: {
			code: 'EUR',
			symbol: '€',
			symbolPosition: 'left',
			thousandSeparator: '.',
			decimalSeparator: ',',
			precision: 2,
		},
		IN: {
			code: 'INR',
			symbol: '₹',
			symbolPosition: 'left',
			thousandSeparator: ',',
			decimalSeparator: '.',
			precision: 2,
		},
		GB: {
			code: 'GBP',
			symbol: '£',
			symbolPosition: 'left',
			thousandSeparator: ',',
			decimalSeparator: '.',
			precision: 2,
		},
		BR: {
			code: 'BRL',
			symbol: 'R$',
			symbolPosition: 'left',
			thousandSeparator: '.',
			decimalSeparator: ',',
			precision: 2,
		},
		VN: {
			code: 'VND',
			symbol: '₫',
			symbolPosition: 'right',
			thousandSeparator: '.',
			decimalSeparator: ',',
			precision: 1,
		},
		ID: {
			code: 'IDR',
			symbol: 'Rp',
			symbolPosition: 'left',
			thousandSeparator: '.',
			decimalSeparator: ',',
			precision: 0,
		},
		BD: {
			code: 'BDT',
			symbol: '৳',
			symbolPosition: 'left',
			thousandSeparator: ',',
			decimalSeparator: '.',
			precision: 0,
		},
		PK: {
			code: 'PKR',
			symbol: '₨',
			symbolPosition: 'left',
			thousandSeparator: ',',
			decimalSeparator: '.',
			precision: 2,
		},
		RU: {
			code: 'RUB',
			symbol: '₽',
			symbolPosition: 'right',
			thousandSeparator: ' ',
			decimalSeparator: ',',
			precision: 2,
		},
		TR: {
			code: 'TRY',
			symbol: '₺',
			symbolPosition: 'left',
			thousandSeparator: '.',
			decimalSeparator: ',',
			precision: 2,
		},
		MX: {
			code: 'MXN',
			symbol: '$',
			symbolPosition: 'left',
			thousandSeparator: ',',
			decimalSeparator: '.',
			precision: 2,
		},
		CA: {
			code: 'CAD',
			symbol: '$',
			symbolPosition: 'left',
			thousandSeparator: ',',
			decimalSeparator: '.',
			precision: 2,
		},
	};
}
