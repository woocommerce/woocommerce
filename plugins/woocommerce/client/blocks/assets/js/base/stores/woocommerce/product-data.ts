/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

type ProductData = {
	price_html: string | null;
};

export type Context = {
	originalProductData: ProductData;
	productData: ProductData;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< {
	state: { singleProductTemplate: Context };
} >( 'woocommerce', {}, { lock: universalLock } );

const productDataStore = store(
	'woocommerce/product-data',
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
				value: string | null
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

export type ProductDataStore = typeof productDataStore;
