/**
 * External dependencies
 */
import { getContext, store, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { ProductFiltersStore } from '../../frontend';

type ProductFilterRatingContext = {
	hasFilterOptions: boolean;
	activeLabelTemplate: string;
};

const { state, actions } = store( 'woocommerce/product-filter-rating', {
	state: {
		get selectedFilters() {
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);

			return ( productFiltersStore.state.activeFilters || [] )
				.filter( ( item ) => item.type === 'rating' )
				.map( ( item ) => item.value )
				.filter( Boolean );
		},
		get hasSelectedFilters(): boolean {
			return state.selectedFilters.length > 0;
		},
		get isItemSelected(): boolean {
			const { attributes } = getElement();

			if ( ! attributes.value ) return false;

			return state.selectedFilters.includes( attributes.value );
		},
	},
	actions: {
		getActiveLabel( label: string ) {
			const { activeLabelTemplate } =
				getContext< ProductFilterRatingContext >();
			return activeLabelTemplate.replace( '{{label}}', label );
		},
		toggleFilter: () => {
			const { attributes } = getElement();
			let filterItem = attributes[ 'data-filter-item' ];

			if ( typeof filterItem === 'string' )
				filterItem = JSON.parse( filterItem );

			const { ariaLabel, value } = filterItem;

			if ( ! value || ! ariaLabel ) return;

			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);

			if ( state.selectedFilters.includes( value ) ) {
				productFiltersStore.actions.removeActiveFilter(
					'rating',
					value
				);
			} else {
				productFiltersStore.actions.setActiveFilter( {
					type: 'rating',
					value,
					label: actions.getActiveLabel( ariaLabel ),
				} );
			}

			productFiltersStore.actions.navigate();
		},
		clearFilters: () => {
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);

			productFiltersStore.actions.removeActiveFiltersByType( 'rating' );

			productFiltersStore.actions.navigate();
		},
	},
} );
