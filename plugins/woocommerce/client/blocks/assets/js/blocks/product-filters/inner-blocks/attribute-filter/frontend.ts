/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { ProductFiltersStore } from '../../frontend';

type ProductFilterAttributeContext = {
	attributeSlug: string;
	queryType: 'or' | 'and';
	selectType: 'single' | 'multiple';
	hasFilterOptions: boolean;
	activeLabelTemplate: string;
};

const { state, actions } = store( 'woocommerce/product-filter-attribute', {
	state: {
		get selectedFilters() {
			const context = getContext< ProductFilterAttributeContext >();
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);
			return ( productFiltersStore.state.activeFilters || [] )
				.filter(
					( item ) =>
						item.type === 'attribute' &&
						item.attribute?.slug === context.attributeSlug
				)
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
				getContext< ProductFilterAttributeContext >();
			return activeLabelTemplate.replace( '{{label}}', label );
		},
		toggleFilter: () => {
			const { attributes } = getElement();
			let filterItem = attributes[ 'data-filter-item' ];

			if ( typeof filterItem === 'string' )
				filterItem = JSON.parse( filterItem );

			const { ariaLabel, value } = filterItem;

			if ( ! value || ! ariaLabel ) return;

			const context = getContext< ProductFilterAttributeContext >();
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);

			if ( state.selectedFilters.includes( value ) ) {
				productFiltersStore.actions.removeActiveFiltersBy(
					( item ) =>
						item.value === value &&
						item.type === 'attribute' &&
						item.attribute?.slug === context.attributeSlug &&
						item.attribute?.queryType === context.queryType
				);
			} else {
				productFiltersStore.actions.setActiveFilter( {
					type: 'attribute',
					value,
					label: actions.getActiveLabel( ariaLabel ),
					attribute: {
						slug: context.attributeSlug,
						queryType: context.queryType,
					},
				} );
			}

			productFiltersStore.actions.navigate();
		},
	},
} );
