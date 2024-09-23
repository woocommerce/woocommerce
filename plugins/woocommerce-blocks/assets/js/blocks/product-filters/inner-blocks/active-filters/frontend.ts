/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import { ProductFiltersContext } from '../../frontend';

store( 'woocommerce/product-filter-active', {
	actions: {
		clearAll: () => {
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			productFiltersContext.params = {};
		},
	},
} );
