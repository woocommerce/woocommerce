/**
 * External dependencies
 */
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { store } from '@woocommerce/interactivity';
import { triggerViewedProductEvent } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';

export default withFilteredAttributes( attributes )( Block );

interface Store {
	actions: {
		triggerEvent: () => void;
	};
}

store< Store >( 'woocommerce/product-image', {
	actions: {
		*triggerEvent() {
			yield triggerViewedProductEvent();
		},
	},
} );
