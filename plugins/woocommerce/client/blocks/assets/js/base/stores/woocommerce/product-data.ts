/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

export type Context = {
	productId: number | null;
	variationId: number | null;
};

type ServerState = {
	templateState: {
		productId: number | null;
		variationId: number | null;
	};
};

const productDataStore = store< {
	state: {
		productId: number | null;
		variationId: number | null;
	} & ServerState;
	actions: {
		setVariationId: ( variationId: number | null ) => void;
	};
} >(
	'woocommerce/product-data',
	{
		state: {
			get productId(): number | null {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.productId ||
					productDataStore?.state?.templateState?.productId
				);
			},
			get variationId(): number | null {
				const context = getContext< Context >(
					'woocommerce/single-product'
				);

				return (
					context?.variationId ||
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
