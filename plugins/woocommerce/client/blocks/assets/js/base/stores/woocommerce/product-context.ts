/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

type ProductRef = {
	currentProductId: number;
};

export type Context = ProductRef;

type ServerState = {
	templateState: ProductRef;
};

const productDataStore = store< {
	state: ProductRef & ServerState;
} >(
	'woocommerce/product-context',
	{
		state: {
			get currentProductId(): number {
				const productContext = getContext< Context >(
					'woocommerce/product-context'
				);

				return (
					productContext?.currentProductId ??
					productDataStore?.state?.templateState?.currentProductId
				);
			},
		},
	},
	{ lock: true }
);

export type ProductDataStore = typeof productDataStore;
