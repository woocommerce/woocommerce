/**
 * External dependencies
 */
import { getElement, store } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import { navigate } from '../../frontend';

const prefix = 'filter_stock_status';

const getUrl = ( activeFilters: string ) => {
	const url = new URL( window.location.href );
	const { searchParams } = url;

	if ( activeFilters !== '' ) {
		searchParams.set( prefix, activeFilters );
	} else {
		searchParams.delete( prefix );
	}

	return url.href;
};

store( 'woocommerce/product-filter-stock-status', {
	actions: {
		toggleFilter: () => {
			// get the active filters from the url:
			const url = new URL( window.location.href );
			const currentFilters = url.searchParams.get( prefix ) || '';

			// split out the active filters into an array.
			const filtersArr =
				currentFilters === '' ? [] : currentFilters.split( ',' );

			const { ref } = getElement();
			const value = ref.getAttribute( 'value' );

			const newFilterArr = filtersArr.includes( value )
				? [ ...filtersArr.filter( ( filter ) => filter !== value ) ]
				: [ ...filtersArr, value ];

			navigate( getUrl( newFilterArr.join( ',' ) ) );
		},
	},
} );
