/**
 * External dependencies
 */
import { getContext, navigate, store } from '@woocommerce/interactivity';
import { CheckboxListContext } from '@woocommerce/interactivity-components/checkbox-list';
import { DropdownContext } from '@woocommerce/interactivity-components/dropdown';

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

store( 'woocommerce/collection-rating-filter', {
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

			navigate( getUrl( filters ) );
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
	},
} );
