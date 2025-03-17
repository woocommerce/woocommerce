/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { ProductFilterActiveStore } from '../active-filters/frontend';

store( 'woocommerce/product-filter-removable-chips', {
	state: {
		get items() {
			const productFilterActiveStore = store< ProductFilterActiveStore >(
				'woocommerce/product-filter-active'
			);

			return productFilterActiveStore.state.items.map( ( item ) => ( {
				...item,
				label: item.label.replace( /\s*\(\d+\)$/, '' ),
			} ) );
		},
	},
} );
