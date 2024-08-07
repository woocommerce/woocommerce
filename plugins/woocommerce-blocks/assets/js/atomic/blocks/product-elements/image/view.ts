/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';
import { triggerViewedProductEvent } from '@woocommerce/base-utils';

interface Store {
	actions: {
		triggerEvent: () => void;
	};
}

interface Context {
	productId: number;
}

store< Store >( 'woocommerce/product-image', {
	actions: {
		*triggerEvent() {
			const context = getContext< Context >();
			const { productId } = context;

			triggerViewedProductEvent( { productId } );
		},
	},
} );
