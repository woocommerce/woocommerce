/** @format */
/**
 * External dependencies
 */
import { sprintf } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { numberFormat } from '@woocommerce/number';

export default class Currency {
	constructor( currency = null ) {
		if ( ! this.code ) {
			this.setCurrency( currency );
		}
	}

	/**
	 * Set the currency configuration to use for the class.
	 *
	 * @param {Object} currency An object containing currency configuration settings.
	 */
	setCurrency( currency ) {
		const defaultCurrency = getCurrencyData().US;
		const config = { ...defaultCurrency, ...currency };

		this.code = config.code.toString();
		this.symbol = config.symbol.toString();
		this.symbolPosition = config.symbolPosition.toString();
		this.decimalSeparator = config.decimalSeparator.toString();
		this.priceFormat = this.getPriceFormat( config );
		this.thousandSeparator = config.thousandSeparator.toString();

		const precisionNumber = parseInt( config.precision, 10 );
		this.precision = precisionNumber;
	}

	stripTags( str ) {
		const tmp = document.createElement( 'DIV' );
		tmp.innerHTML = str;
		return tmp.textContent || tmp.innerText || '';
	}

	/**
	 * Get the default price format from a currency.
	 *
	 * @param {Object} config Currency configuration.
	 * @return {String} Price format.
	 */
	getPriceFormat( config ) {
		if ( config.priceFormat ) {
			return this.stripTags( config.priceFormat.toString() );
		}

		switch ( config.symbolPosition ) {
			case 'left':
				return '%1$s%2$s';
			case 'right':
				return '%2$s%1$s';
			case 'left_space':
				return '%1$s&nbsp;%2$s';
			case 'right_space':
				return '%2$s&nbsp;%1$s';
		}

		return '%1$s%2$s';
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
 * Returns currency data by country/region. Contains code, symbol, position, thousands separator, decimal separator, and precision.
 *
 * Dev Note: When adding new currencies below, the exchange rate array should also be updated in WooCommerce Admin's `business-details.js`.
 * @format
 * @return {object} Curreny data.
 */
export function getCurrencyData() {
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
