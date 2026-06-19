/**
 * External dependencies
 */
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	objectHasProp,
	Currency,
	isString,
	type CurrencyResponse,
	type WCStoreV1ProductsCollectionProps,
} from '@woocommerce/types';

function formatPriceInt( price: string | number, currency: Currency ) {
	const priceInt = typeof price === 'number' ? price : parseInt( price, 10 );
	return priceInt / 10 ** currency.minorUnit;
}

export function getPriceFilterData(
	results: WCStoreV1ProductsCollectionProps
) {
	if ( ! objectHasProp( results, 'price_range' ) ) {
		return {
			currentMin: 0,
			currentMax: 0,
			min: 0,
			max: 0,
		};
	}

	const currency = getCurrencyFromPriceResponse(
		results.price_range as CurrencyResponse
	);

	const minPrice =
		objectHasProp( results.price_range, 'min_price' ) &&
		isString( results.price_range.min_price )
			? formatPriceInt( results.price_range.min_price, currency )
			: 0;
	const maxPrice =
		objectHasProp( results.price_range, 'max_price' ) &&
		isString( results.price_range.max_price )
			? formatPriceInt( results.price_range.max_price, currency )
			: 0;

	return {
		currentMin: minPrice,
		currentMax: maxPrice,
		min: minPrice,
		max: maxPrice,
	};
}
