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
		).map( ( categoryElement ) => {
			const id = getCategoryDataFromElement( categoryElement );
			categoryElement.remove();
			return id;
		} );
		return selectedCategories;
	}
	return [];
}
