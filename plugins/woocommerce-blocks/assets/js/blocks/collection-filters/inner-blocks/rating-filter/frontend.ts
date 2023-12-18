/**
 * External dependencies
 */
import { getContext, navigate, store } from '@woocommerce/interactivity';
import { CheckboxListContext } from '@woocommerce/interactivity-components/checkbox-list';
import { DropdownContext } from '@woocommerce/interactivity-components/dropdown';

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

			const url = new URL( window.location.href );

			if ( filters.length ) {
				// add filters to url
				url.searchParams.set( 'rating_filter', filters.join( ',' ) );
			} else {
				// remove filters from url
				url.searchParams.delete( 'rating_filter' );
			}

			navigate( url );
		},
		onDropdownChange: () => {
			const dropdownContext = getContext< DropdownContext >(
				'woocommerce/interactivity-dropdown'
			);

			const filter = dropdownContext.selectedItem?.value;
			const url = new URL( window.location.href );

			if ( filter ) {
				// add filter to url
				url.searchParams.set( 'rating_filter', filter );
			} else {
				// remove filter from url
				url.searchParams.delete( 'rating_filter' );
			}

			navigate( url );
		},
	},
} );
