/**
 * External dependencies
 */
import type { ProductResponseItem, CartResponseItem } from '@woocommerce/types';

interface ImpressionItem extends Gtag.Item {
	// eslint-disable-next-line camelcase
	list_name?: string;
}

/**
 * Formats data into the productFieldObject shape.
 *
 * @see https://developers.google.com/analytics/devguides/collection/gtagjs/enhanced-ecommerce#product-data
 */
export const getProductFieldObject = (
	product: ProductResponseItem | CartResponseItem,
	quantity: number | undefined
): Gtag.Item => {
	const productIdentifier = product.sku ? product.sku : '#' + product.id;
	const productCategory =
		'categories' in product && product.categories.length
			? product.categories[ 0 ].name
			: '';
	return {
		id: productIdentifier,
		name: product.name,
		quantity,
		category: productCategory,
		price: (
			parseInt( product.prices.price, 10 ) /
			10 ** product.prices.currency_minor_unit
		).toString(),
	};
};

/**
 * Formats data into the impressionFieldObject shape.
 *
 * @see https://developers.google.com/analytics/devguides/collection/gtagjs/enhanced-ecommerce#impression-data
 */
export const getProductImpressionObject = (
	product: ProductResponseItem,
	listName: string
): ImpressionItem => {
	const productIdentifier = product.sku ? product.sku : '#' + product.id;
	const productCategory = product.categories.length
		? product.categories[ 0 ].name
		: '';
	return {
		id: productIdentifier,
		name: product.name,
		list_name: listName,
		category: productCategory,
		price: (
			parseInt( product.prices.price, 10 ) /
			10 ** product.prices.currency_minor_unit
		).toString(),
	};
};

/**
 * Track an event using the global gtag function.
 */
export const trackEvent = (
	eventName: Gtag.EventNames | string,
	eventParams?: Gtag.ControlParams | Gtag.EventParams | Gtag.CustomParams
): void => {
	if ( typeof gtag !== 'function' ) {
		throw new Error( 'Function gtag not implemented.' );
	}
	// eslint-disable-next-line no-console
	console.log( `Tracking event ${ eventName }` );
	window.gtag( 'event', eventName, eventParams );
};
