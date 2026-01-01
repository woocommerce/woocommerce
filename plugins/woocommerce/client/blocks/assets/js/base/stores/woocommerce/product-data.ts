/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

type ProductRef = {
	productId: number;
	variationId: number | null;
};

export type Context = ProductRef;

type ServerState = {
	templateState: ProductRef;
};

const productDataStore = store< {
	state: ProductRef & ServerState;
	actions: {
		setVariationId: ( variationId: number | null ) => void;
	};
} >(
	'woocommerce/product-data',
	{
		state: {
			get productId(): number {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.productId ??
					productDataStore?.state?.templateState?.productId
				);
			},
			get variationId(): number | null {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.variationId ??
					productDataStore?.state?.templateState?.variationId
				);
			},
		},
		actions: {
			setVariationId: ( variationId: number | null ) => {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				if ( context?.variationId !== undefined ) {
					context.variationId = variationId;
				} else if (
					productDataStore?.state?.templateState?.variationId !==
					undefined
				) {
					productDataStore.state.templateState.variationId =
						variationId;
				}
			},
		},
	},
	{ lock: true }
);

export type ProductDataStore = typeof productDataStore;
