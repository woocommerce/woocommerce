/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';
import { DropdownContext } from '@woocommerce/interactivity-components/dropdown';
import { HTMLElementEvent } from '@woocommerce/types';
import { CheckboxListContext } from '@woocommerce/interactivity-components/checkbox-list';

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
		onCheckboxChange: () => {
			const checkboxContext = getContext< CheckboxListContext >(
				'woocommerce/interactivity-checkbox-list'
			);

			const filters = checkboxContext.items
				.filter( ( item ) => {
					return item.checked;
				} )
				.map( ( item ) => {
					return item.value;
				} );

			navigate( getUrl( filters.join( ',' ) ) );
		},
		onDropdownChange: () => {
			const dropdownContext = getContext< DropdownContext >(
				'woocommerce/interactivity-dropdown'
			);

			const selectedItems = dropdownContext.selectedItems;
			const items = selectedItems || [];
			const filters = items.map( ( i ) => i.value );

			navigate( getUrl( filters.join( ',' ) ) );
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
		removeFilter: () => {
			const { value } = getContext< { value: string } >();
			// get the active filters from the url:
			const url = new URL( window.location.href );
			const currentFilters =
				url.searchParams.get( 'filter_stock_status' ) || '';

			// split out the active filters into an array.
			const filtersArr =
				currentFilters === '' ? [] : currentFilters.split( ',' );

			const index = filtersArr.indexOf( value );

			if ( index > -1 ) {
				filtersArr.splice( index, 1 );
			}

			navigate( getUrl( filtersArr.join( ',' ) ) );
		},
	},
} );
