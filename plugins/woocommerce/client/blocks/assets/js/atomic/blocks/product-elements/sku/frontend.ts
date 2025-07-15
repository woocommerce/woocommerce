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

const productSkuStore = store(
	'woocommerce/product-sku',
	{
		callbacks: {
			updateSku: () => {
				const element = getElement();

				if ( ! element.ref ) {
					return;
				}

				const newSku =
					productDataState?.productData?.sku ??
					productDataState?.originalProductData?.sku;

				if ( newSku !== null ) {
					element.ref.textContent = sanitize( newSku );
				}
			},
		},
	},
	{ lock: true }
);

export type ProductSkuStore = typeof productSkuStore;
