/**
 * External dependencies
 */
import { SITE_CURRENCY } from '@woocommerce/settings';
import type {
	Currency,
	CurrencyResponse,
	CartShippingPackageShippingRate,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { formatPriceWithCurrency, normalizeCurrencyResponse } from './currency';

/**
 * Gets currency information in normalized format from an API response or the server.
 *
 * If no currency was provided, or currency_code is empty, the default store currency will be used.
 */
export const getCurrencyFromPriceResponse = (
	currencyData?:
		| CurrencyResponse
		| Record< string, never >
		| CartShippingPackageShippingRate
): Currency => {
	return normalizeCurrencyResponse( currencyData, SITE_CURRENCY );
};

/**
 * Gets currency information in normalized format, allowing overrides.
 */
export const getCurrency = (
	currencyData: Partial< Currency > = {}
): Currency => {
	return {
		...SITE_CURRENCY,
		...currencyData,
	};
};

/**
 * Format a price, provided using the smallest unit of the currency, as a
 * decimal complete with currency symbols using current store settings.
 */
export const formatPrice = (
	price: number | string,
	currencyData?: Currency
): string => {
	const currency: Currency = getCurrency( currencyData );
	return formatPriceWithCurrency( price, currency );
};
