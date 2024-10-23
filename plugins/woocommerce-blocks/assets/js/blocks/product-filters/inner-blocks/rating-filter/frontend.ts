/**
 * External dependencies
 */
import { getContext, store, getElement } from '@woocommerce/interactivity';
import { DropdownContext } from '@woocommerce/interactivity-components/dropdown';

/**
 * Internal dependencies
 */
import { navigate } from '../../frontend';
import type { ProductFiltersContext } from '../../frontend';

const filterRatingKey = 'rating_filter';

function getUrl( filters: Array< string | null > ) {
	filters = filters.filter( Boolean );
	const url = new URL( window.location.href );

	if ( filters.length ) {
		// add filters to url
		url.searchParams.set( 'rating_filter', filters.join( ',' ) );
	} else {
		// remove filters from url
		url.searchParams.delete( 'rating_filter' );
	}

	return url.href;
}

/**
 * Get the rating list (an array of strings)
 * from the product filters context.
 *
 * @param {ProductFiltersContext} context The context
 * @return {Array} The rating filters
 */
function getRatingFilters( context: ProductFiltersContext ): Array< string > {
	if ( ! context.params[ filterRatingKey ] ) {
		return [];
	}

	return context.params[ filterRatingKey ].split( ',' );
}

store( 'woocommerce/product-filter-rating', {
	actions: {
		toggleFilter: () => {
			const context = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);

			// Pick out the active filters from the context
			const filtersList = getRatingFilters( context );

			const { ref } = getElement();
			const value =
				ref.getAttribute( 'data-target-value' ) ??
				ref.getAttribute( 'value' );

			const updatedFiltersList = filtersList.includes( value )
				? [ ...filtersList.filter( ( filter ) => filter !== value ) ]
				: [ ...filtersList, value ];

			if ( updatedFiltersList.length === 0 ) {
				delete context.params[ filterRatingKey ];
				return;
			}

			// Populate the context with the new rating filters
			context.params = {
				...context.params,
				[ filterRatingKey ]: updatedFiltersList.join( ',' ),
			};
		},
		onDropdownChange: () => {
			const dropdownContext = getContext< DropdownContext >(
				'woocommerce/interactivity-dropdown'
			);

			const selectedItems = dropdownContext.selectedItems;
			const items = selectedItems || [];
			const filters = items.map( ( i ) => i.value );

			navigate( getUrl( filters ) );
		},

		clearFilters: () => {
			const context = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			const updatedParams = context.params;

			delete updatedParams[ filterRatingKey ];

			context.params = {
				...updatedParams,
			};
		},
	},
} );
