/**
 * External dependencies
 */
import { store } from '@woocommerce/interactivity';
import { triggerViewedProductEvent } from '@woocommerce/base-utils';

interface Store {
	actions: {
		triggerEvent: () => void;
	};
}

store< Store >( 'woocommerce/product-image', {
	actions: {
		*triggerEvent() {
			triggerViewedProductEvent();
		},
	},
} );
