/**
 * External dependencies
 */
import { getElement, store } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-data';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';
import { sanitize } from 'dompurify'; // eslint-disable-line import/named

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

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

const productStockIndicatorStore = store(
	'woocommerce/product-stock-indicator',
	{
		callbacks: {
			updateStockQuantity: () => {
				const element = getElement();

				if ( ! element.ref || ! productDataState?.productId ) {
					return;
				}
				const availabilityHtml =
					wooState?.products?.[ productDataState?.productId ]
						?.variations?.[ productDataState?.variationId || 0 ]
						?.availability_html ||
					wooState?.products?.[ productDataState?.productId ]
						?.availability_html;

				if ( typeof availabilityHtml === 'string' ) {
					element.ref.innerHTML = sanitize( availabilityHtml, {
						ALLOWED_TAGS,
						ALLOWED_ATTR,
					} );
				}
			},
		},
	},
	{ lock: true }
);

export type ProductStockIndicatorStore = typeof productStockIndicatorStore;
