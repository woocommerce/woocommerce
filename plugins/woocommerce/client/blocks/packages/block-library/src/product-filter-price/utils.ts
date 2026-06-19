type PriceRangeResponse = {
	min_price?: string;
	max_price?: string;
	currency_minor_unit?: number;
};

type CollectionData = {
	price_range?: PriceRangeResponse;
};

const objectHasProp = < T extends string >(
	object: unknown,
	property: T
): object is Record< T, unknown > =>
	typeof object === 'object' && object !== null && property in object;

function formatPriceInt( price: string | number, minorUnit: number ) {
	const priceInt = typeof price === 'number' ? price : parseInt( price, 10 );
	return priceInt / 10 ** minorUnit;
}

export function getPriceFilterData( results: CollectionData ) {
	if ( ! objectHasProp( results, 'price_range' ) ) {
		return {
			currentMin: 0,
			currentMax: 0,
			min: 0,
			max: 0,
		};
	}

	const priceRange = results.price_range;
	const minorUnit =
		typeof priceRange.currency_minor_unit === 'number'
			? priceRange.currency_minor_unit
			: 2;

	const minPrice =
		objectHasProp( priceRange, 'min_price' ) &&
		typeof priceRange.min_price === 'string'
			? formatPriceInt( priceRange.min_price, minorUnit )
			: 0;
	const maxPrice =
		objectHasProp( priceRange, 'max_price' ) &&
		typeof priceRange.max_price === 'string'
			? formatPriceInt( priceRange.max_price, minorUnit )
			: 0;

	return {
		currentMin: minPrice,
		currentMax: maxPrice,
		min: minPrice,
		max: maxPrice,
	};
}
