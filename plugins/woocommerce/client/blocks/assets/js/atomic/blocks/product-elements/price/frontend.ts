/**
 * External dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';
import type { ProductData } from '@woocommerce/type-defs/product';
import type { SingleProductTemplateStore } from '@woocommerce/base-stores/single-product-template';
import { sanitize } from 'dompurify'; // eslint-disable-line import/named

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< SingleProductTemplateStore >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const ALLOWED_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'span',
	'bdi',
	'del',
	'ins',
];
const ALLOWED_ATTR = [
	'class',
	'target',
	'href',
	'rel',
	'name',
	'download',
	'aria-hidden',
];

const getProductData = ( key: keyof ProductData ) => {
	const singleProductContext = getContext< {
		productData: ProductData | null;
		originalProductData: ProductData;
	} >( 'woocommerce/single-product' );

	let newValue = '';
	if ( singleProductContext ) {
		newValue =
			singleProductContext?.productData?.[ key ] ||
			singleProductContext?.originalProductData?.[ key ];
	} else {
		newValue =
			wooState?.singleProductTemplate?.productData?.[ key ] ||
			wooState?.singleProductTemplate?.originalProductData?.[ key ];
	}

	return sanitize( newValue, {
		ALLOWED_TAGS,
		ALLOWED_ATTR,
	} );
};

const productPriceStore = store(
	'woocommerce/product-price',
	{
		callbacks: {
			setNewPrice: () => {
				const element = getElement();

				if ( ! element.ref ) {
					return;
				}

				const newPrice = getProductData( 'price_html' );

				if ( newPrice ) {
					element.ref.innerHTML = newPrice;
				}
			},
		},
	},
	{ lock: true }
);

export type ProductPriceStore = typeof productPriceStore;
