/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

store( 'woocommerce/add-to-cart-with-options', {
	state: {},
	actions: {
		onSelect() {
			const context = getContext();
			if ( context.selected === context.value ) {
				context.selected = null;
			} else {
				context.selected = context.value;
			}

			console.log( 'toggleAttributePill', { ...context } );
		},
	},
	callbacks: {
		checkSelected() {
			const context = getContext();
			context.isSelected = context.selected === context.value;
		},
	},
} );
