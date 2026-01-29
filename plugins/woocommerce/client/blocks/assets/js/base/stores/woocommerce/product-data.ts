/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

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
	{ lock: universalLock }
);

export type ProductDataStore = typeof productDataStore;
