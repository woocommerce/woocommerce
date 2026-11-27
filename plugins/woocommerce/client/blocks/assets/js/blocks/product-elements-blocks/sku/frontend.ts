/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

type Context = {
	initialSkuText: string;
	fallbackText: string;
};

store(
	'woocommerce/product-sku',
	{
		state: {
			get skuText(): string {
				const { fallbackText, initialSkuText } =
					getContext< Context >();
				const sku = productsState.productInContext?.sku;
				return ( sku ?? initialSkuText ) || fallbackText;
			},
		},
	},
	{ lock: true }
);
