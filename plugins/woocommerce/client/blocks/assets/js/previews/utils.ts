/**
 * External dependencies
 */
import { SITE_CURRENCY } from '@woocommerce/settings';

/**
 * Put site currency back in API format for the responses.
 */
export const API_SITE_CURRENCY = {
	currency_code: SITE_CURRENCY.code,
	currency_symbol: SITE_CURRENCY.symbol,
	currency_minor_unit: SITE_CURRENCY.minorUnit,
	currency_decimal_separator: SITE_CURRENCY.decimalSeparator,
	currency_thousand_separator: SITE_CURRENCY.thousandSeparator,
	currency_prefix: SITE_CURRENCY.prefix,
	currency_suffix: SITE_CURRENCY.suffix,
};

/**
 * Preview data is defined with 2dp. This converts to selected currency settings.
 */
export const displayForMinorUnit = ( value: string, precision = 2 ): string => {
	const minorUnit = SITE_CURRENCY.minorUnit;

	// Preview data is defined with 2 dp.
	if ( minorUnit === precision || ! value ) {
		return value;
	}

	const multiplier = Math.pow( 10, minorUnit );
	const intValue = Math.round(
		parseInt( value, 10 ) / Math.pow( 10, precision )
	);

	return ( intValue * multiplier ).toString();
};
