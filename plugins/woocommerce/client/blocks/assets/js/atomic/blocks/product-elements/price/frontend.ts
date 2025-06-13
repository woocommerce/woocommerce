/**
 * External dependencies
 */
import { getElement, store } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-data';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';
import { sanitize } from 'dompurify'; // eslint-disable-line import/named

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productDataState } = store< ProductDataStore >(
	'woocommerce/product-data',
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

const productPriceStore = store(
	'woocommerce/product-price',
	{
		callbacks: {
			updatePrice: () => {
				const element = getElement();

				if ( ! element.ref ) {
					return;
				}

				const newPriceHTML =
					productDataState?.productData?.price_html ||
					productDataState?.originalProductData?.price_html;

				if ( newPriceHTML ) {
					element.ref.innerHTML = sanitize( newPriceHTML, {
						ALLOWED_TAGS,
						ALLOWED_ATTR,
					} );
				}
			},
		},
	},
	{ lock: true }
);

export type ProductPriceStore = typeof productPriceStore;
