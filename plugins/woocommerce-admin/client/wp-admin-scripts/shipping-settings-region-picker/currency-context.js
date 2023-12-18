/**
 * External dependencies
 */
import { useContext, useEffect } from '@wordpress/element';
import { CurrencyContext } from '@woocommerce/currency';
import { numberFormat } from '@woocommerce/number';

/**
 * Format all numbers with respect to shipping formula.
 */
export const numberFormatWithShippingFormula = ( config, number ) => {
	if ( typeof number === 'number' ) {
		return numberFormat( config, number );
	}

	if ( typeof number === 'string' ) {
		/**
		 * \b: Ensures that we're capturing whole numbers (word boundaries).
		 * \d+: Matches one or more digits.
		 * (\.\d+)?: Optionally matches a decimal point followed by one or more digits.
		 * (?![^[]*\]): A negative lookahead to avoid numbers inside square brackets.
		 * g: Global flag to match all instances in the string.
		 */
		return number.replace( /(\b\d+(\.\d+)?\b)(?![^[]*\])/g, ( n ) =>
			numberFormat( config, n )
		);
	}

	return number;
};

export const ShippingCurrencyContext = () => {
	const context = useContext( CurrencyContext );

	useEffect( () => {
		window.wc.ShippingCurrencyContext =
			window.wc.ShippingCurrencyContext || context;
		window.wc.ShippingCurrencyNumberFormat =
			window.wc.ShippingCurrencyNumberFormat ||
			numberFormatWithShippingFormula;
	}, [ context ] );

	return null;
};
