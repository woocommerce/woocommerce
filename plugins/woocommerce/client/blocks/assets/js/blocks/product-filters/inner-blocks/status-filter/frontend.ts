/**
 * External dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { ProductFiltersStore } from '../../frontend';

type ProductFilterStatusContext = {
	hasFilterOptions: boolean;
	activeLabelTemplate: string;
};

const { state, actions } = store( 'woocommerce/product-filter-status', {
	state: {
		get selectedFilters() {
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);
			return ( productFiltersStore.state.activeFilters || [] )
				.filter( ( item ) => item.type === 'status' )
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
				getContext< ProductFilterStatusContext >();
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
					'status',
					value
				);
			} else {
				productFiltersStore.actions.setActiveFilter( {
					type: 'status',
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

			productFiltersStore.actions.removeActiveFiltersByType( 'status' );

			productFiltersStore.actions.navigate();
		},
	},
} );
