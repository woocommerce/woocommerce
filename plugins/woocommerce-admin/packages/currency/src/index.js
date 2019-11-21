/** @format */
/**
 * External dependencies
 */
import { sprintf } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { numberFormat } from '@woocommerce/number';

const DEFAULTS = {
	code: 'USD',
	precision: 2,
	symbol: '$',
	symbolPosition: 'left',
	decimalSeparator: '.',
	priceFormat: '%1$s%2$s',
	thousandSeparator: ',',
};

export default class Currency {
	constructor( config = {} ) {
		this.code = ( config.code || DEFAULTS.code ).toString();
		this.symbol = ( config.symbol || DEFAULTS.symbol ).toString();
		this.symbolPosition = ( config.symbolPosition || DEFAULTS.symbolPosition ).toString();
		this.decimalSeparator = ( config.decimalSeparator || DEFAULTS.decimalSeparator ).toString();
		this.priceFormat = ( config.priceFormat || DEFAULTS.priceFormat ).toString();
		this.thousandSeparator = ( config.thousandSeparator || DEFAULTS.thousandSeparator ).toString();

		const precisionNumber = parseInt( config.precision, 10 );
		this.precision = isNaN( precisionNumber ) ? DEFAULTS.precision : precisionNumber;

		Object.freeze( this );
	}

	/**
	 * Formats money value.
	 *
	 * @param   {Number|String} number number to format
	 * @returns {?String} A formatted string.
	 */
	formatCurrency( number ) {
		const formattedNumber = numberFormat( this, number );

		if ( '' === formattedNumber ) {
			return formattedNumber;
		}

		return sprintf( this.priceFormat, this.symbol, formattedNumber );
	}

	/**
	 * Get the rounded decimal value of a number at the precision used for the current currency.
	 * This is a work-around for fraction-cents, meant to be used like `wc_format_decimal`
	 *
	 * @param {Number|String} number A floating point number (or integer), or string that converts to a number
	 * @return {Number} The original number rounded to a decimal point
	 */
	formatDecimal( number ) {
		if ( 'number' !== typeof number ) {
			number = parseFloat( number );
		}
		if ( Number.isNaN( number ) ) {
			return 0;
		}
		return Math.round( number * Math.pow( 10, this.precision ) ) / Math.pow( 10, this.precision );
	}

	/**
	 * Get the string representation of a floating point number to the precision used by the current currency.
	 * This is different from `formatCurrency` by not returning the currency symbol.
	 *
	 * @param  {Number|String} number A floating point number (or integer), or string that converts to a number
	 * @return {String}               The original number rounded to a decimal point
	 */
	formatDecimalString( number ) {
		if ( 'number' !== typeof number ) {
			number = parseFloat( number );
		}
		if ( Number.isNaN( number ) ) {
			return '';
		}
		return number.toFixed( this.precision );
	}

	/**
	 * Render a currency for display in a component.
	 *
	 * @param  {Number|String} number A floating point number (or integer), or string that converts to a number
	 * @return {Node|String} The number formatted as currency and rendered for display.
	 */
	render( number ) {
		if ( 'number' !== typeof number ) {
			number = parseFloat( number );
		}
		if ( number < 0 ) {
			return <span className="is-negative">{ this.formatCurrency( number ) }</span>;
		}
		return this.formatCurrency( number );
	}
}

/**
 * Returns currency data by country/region. Contains code, position, thousands separator, decimal separator, and precision.
 *
 * @format
 * @return {object} Curreny data.
 */
export function getCurrencyData() {
	// See https://github.com/woocommerce/woocommerce-admin/issues/3101.
	return {
		US: {
			code: 'USD',
			position: 'left',
			grouping: ',',
			decimal: '.',
			precision: 2,
		},
		EU: {
			code: 'EUR',
			position: 'left',
			grouping: '.',
			decimal: ',',
			precision: 2,
		},
		IN: {
			code: 'INR',
			position: 'left',
			grouping: ',',
			decimal: '.',
			precision: 2,
		},
		GB: {
			code: 'GBP',
			position: 'left',
			grouping: ',',
			decimal: '.',
			precision: 2,
		},
		BR: {
			code: 'BRL',
			position: 'left',
			grouping: '.',
			decimal: ',',
			precision: 2,
		},
		VN: {
			code: 'VND',
			position: 'right',
			grouping: '.',
			decimal: ',',
			precision: 1,
		},
		ID: {
			code: 'IDR',
			position: 'left',
			grouping: '.',
			decimal: ',',
			precision: 0,
		},
		BD: {
			code: 'BDT',
			position: 'left',
			grouping: ',',
			decimal: '.',
			precision: 0,
		},
		PK: {
			code: 'PKR',
			position: 'left',
			grouping: ',',
			decimal: '.',
			precision: 2,
		},
		RU: {
			code: 'RUB',
			position: 'right',
			grouping: ' ',
			decimal: ',',
			precision: 2,
		},
		TR: {
			code: 'TRY',
			position: 'left',
			grouping: '.',
			decimal: ',',
			precision: 2,
		},
		MX: {
			code: 'MXN',
			position: 'left',
			grouping: ',',
			decimal: '.',
			precision: 2,
		},
		CA: {
			code: 'CAD',
			position: 'left',
			grouping: ',',
			decimal: '.',
			precision: 2,
		},
	};
}
