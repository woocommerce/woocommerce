export const CATEGORY_TERM_NAME = 'product_cat';

export function getCategoryDataFromElement( element ) {
	if ( element && element.dataset && element.dataset.name ) {
		return {
			term_id: parseInt( element.value, 10 ),
			name: element.dataset.name,
		};
	}
	return null;
}

export function getSelectedCategoryData( container ) {
	if ( container ) {
		const selectedCategories = Array.from(
			container.querySelectorAll( ':scope > input[type=hidden]' )
		).map( ( ele ) => {
			const id = getCategoryDataFromElement( ele );
			ele.remove();
			return id;
		} );
		return selectedCategories;
	}
	return [];
}

export function syncWithMostUsed( selectedIds ) {
	document
		.querySelectorAll( '#' + CATEGORY_TERM_NAME + 'checklist-pop li' )
		.forEach( ( item ) => {
			const id = parseInt(
				item.id.replace( 'popular-' + CATEGORY_TERM_NAME + '-', '' ),
				10
			);
			if ( id !== NaN ) {
				const input = item.querySelector( 'input[type=checkbox]' );
				if ( input ) {
					input.checked = selectedIds.includes( id );
				}
			}
		} );
}

export function onMostUsedChanged( callback ) {
	document
		.querySelectorAll( '#' + CATEGORY_TERM_NAME + 'checklist-pop li' )
		.forEach( ( item ) => {
			const input = item.querySelector( 'input[type=checkbox]' );
			if ( input ) {
				input.addEventListener( 'change', callback );
			}
		} );
}
export function removeOnMostUsedChanged( callback ) {
	document
		.querySelectorAll( '#' + CATEGORY_TERM_NAME + 'checklist-pop li' )
		.forEach( ( item ) => {
			const input = item.querySelector( 'input[type=checkbox]' );
			if ( input ) {
				input.removeEventListener( 'change', callback );
			}
		} );
}
