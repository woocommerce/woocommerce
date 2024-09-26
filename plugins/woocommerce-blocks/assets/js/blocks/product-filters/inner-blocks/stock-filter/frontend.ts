/**
 * External dependencies
 */
import { getElement, store } from '@woocommerce/interactivity';
import { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { navigate } from '../../frontend';

const getUrl = ( activeFilters: string ) => {
	const url = new URL( window.location.href );
	const { searchParams } = url;

	if ( activeFilters !== '' ) {
		searchParams.set( 'filter_stock_status', activeFilters );
	} else {
		searchParams.delete( 'filter_stock_status' );
	}

	return url.href;
};

store( 'woocommerce/product-filter-stock-status', {
	actions: {
		toggleFilter: () => {
			// get the active filters from the url:
			const url = new URL( window.location.href );
			const currentFilters =
				url.searchParams.get( 'filter_stock_status' ) || '';

			// split out the active filters into an array.
			const filtersArr =
				currentFilters === '' ? [] : currentFilters.split( ',' );

			const { ref } = getElement();
			const value = ref.getAttribute( 'value' );

			if ( filtersArr.includes( value ) ) {
				filtersArr.splice( filtersArr.indexOf( value ), 1 );
			} else {
				filtersArr.push( value );
			}

			navigate( getUrl( filtersArr.join( ',' ) ) );
		},
	},
} );
