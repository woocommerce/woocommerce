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

type ServerState = {
	templateState: {
		originalProductData: ProductData;
		productData: ProductData;
	};
};

const productDataStore = store< {
	state: {
		productData: ProductData;
		originalProductData: ProductData;
	} & ServerState;
	actions: {
		setProductData: (
			key: keyof ProductData,
			value: string | null
		) => void;
	};
} >(
	'woocommerce/product-data',
	{
		state: {
			get productData(): ProductData {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.productData ||
					productDataStore?.state?.templateState?.productData
				);
			},
			get originalProductData(): ProductData {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.originalProductData ||
					productDataStore?.state?.templateState?.originalProductData
				);
			},
		},
		actions: {
			setProductData: ( key, value ) => {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				if ( context?.productData ) {
					context.productData[ key ] = value;
				} else if (
					productDataStore?.state?.templateState?.productData
				) {
					productDataStore.state.templateState.productData[ key ] =
						value;
				}
			},
		},
	},
	{ lock: true }
);

export type ProductDataStore = typeof productDataStore;
