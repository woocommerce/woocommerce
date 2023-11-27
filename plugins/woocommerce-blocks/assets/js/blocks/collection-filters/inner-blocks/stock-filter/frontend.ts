/**
 * External dependencies
 */
import { store, navigate, getContext } from '@woocommerce/interactivity';
import { DropdownContext } from '@woocommerce/interactivity-components/dropdown';
import { HTMLElementEvent } from '@woocommerce/types';

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

store( 'woocommerce/collection-stock-filter', {
	actions: {
		// "on select" handler passed to the dropdown component.
		navigate: () => {
			const context = getContext< DropdownContext >(
				'woocommerce/interactivity-dropdown'
			);

			navigate( getUrl( context.selectedItem.value || '' ) );
		},
		updateProducts: ( event: HTMLElementEvent< HTMLInputElement > ) => {
			// get the active filters from the url:
			const url = new URL( window.location.href );
			const currentFilters =
				url.searchParams.get( 'filter_stock_status' ) || '';

			// split out the active filters into an array.
			const filtersArr =
				currentFilters === '' ? [] : currentFilters.split( ',' );

			// if checked and not already in activeFilters, add to activeFilters
			// if not checked and in activeFilters, remove from activeFilters.
			if ( event.target.checked ) {
				if ( ! currentFilters.includes( event.target.value ) ) {
					filtersArr.push( event.target.value );
				}
			} else {
				const index = filtersArr.indexOf( event.target.value );
				if ( index > -1 ) {
					filtersArr.splice( index, 1 );
				}
			}

			navigate( getUrl( filtersArr.join( ',' ) ) );
		},
	},
} );
