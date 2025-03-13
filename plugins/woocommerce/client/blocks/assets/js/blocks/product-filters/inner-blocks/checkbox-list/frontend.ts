/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

type CheckboxListContext = {
	showAll: boolean;
};

store( 'woocommerce/product-filter-checkbox-list', {
	actions: {
		showAllItems: () => {
			const context = getContext< CheckboxListContext >();
			context.showAll = true;
		},
	},
} );
