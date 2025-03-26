/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */

export type ChipsContext = {
	showAll: boolean;
};

store( 'woocommerce/product-filters', {
	actions: {
		showAllChips: () => {
			const context = getContext< ChipsContext >();
			context.showAll = true;
		},
	},
} );
