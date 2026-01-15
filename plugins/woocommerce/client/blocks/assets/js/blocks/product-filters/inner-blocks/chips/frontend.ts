/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import setStyles from './set-styles';

export type ChipsContext = {
	showAll: boolean;
};

// Set selected chips styles for proper contrast.
setStyles();

store( 'woocommerce/product-filters', {
	actions: {
		showAllChips: () => {
			const context = getContext< ChipsContext >();
			context.showAll = true;
		},
	},
} );
