/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { ProductFiltersContext, ProductFiltersStore } from '../../frontend';

type ProductFilterActiveContext = {
	removeLabelTemplate: string;
};

const productFilterActiveStore = store( 'woocommerce/product-filter-active', {
	state: {
		get items() {
			const context = getContext< ProductFilterActiveContext >();
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);

			return productFiltersStore.state.activeFilters.map( ( item ) => ( {
				...item,
				removeLabel: context.removeLabelTemplate.replace(
					'{{label}}',
					item.label
				),
			} ) );
		},
		get hasSelectedFilters() {
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);
			return productFiltersStore.state.activeFilters.length > 0;
		},
	},
	actions: {
		removeFilter: () => {
			const { attributes } = getElement();
			let filterItem = attributes[ 'data-filter-item' ];

			if ( typeof filterItem === 'string' )
				filterItem = JSON.parse( filterItem );

			const { type, value } = filterItem;

			if ( ! type || ! value ) return;

			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);

			productFiltersStore.actions.removeActiveFilter( type, value );

			productFiltersStore.actions.navigate();
		},
		clearFilters: () => {
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			productFiltersContext.activeFilters = [];

			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);
			productFiltersStore.actions.navigate();
		},
	},
} );

export type ProductFilterActiveStore = typeof productFilterActiveStore;
