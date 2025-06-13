/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';
import type { ProductData } from '@woocommerce/type-defs/product';
import type { SingleProductTemplateStore } from '@woocommerce/base-stores/single-product-template';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

export type Context = {
	originalProductData: ProductData;
	productData: ProductData;
};
const { state: wooState } = store< SingleProductTemplateStore >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const singleProductStore = store(
	'woocommerce/single-product',
	{
		state: {
			get productData(): ProductData | null {
				const context = getContext< Context >();

				return (
					context?.productData ||
					wooState?.singleProductTemplate?.productData
				);
			},
			get originalProductData(): ProductData | null {
				const context = getContext< Context >();

				return (
					context?.originalProductData ||
					wooState?.singleProductTemplate?.originalProductData
				);
			},
		},
		actions: {
			setProductData: (
				key: keyof ProductData,
				value: string | number | null
			) => {
				const context = getContext< Context >();

				if ( context?.productData ) {
					context.productData[ key ] = value;
				} else if ( wooState?.singleProductTemplate?.productData ) {
					wooState.singleProductTemplate.productData[ key ] = value;
				}
			},
		},
	},
	{ lock: true }
);

export type SingleProductStore = typeof singleProductStore;
