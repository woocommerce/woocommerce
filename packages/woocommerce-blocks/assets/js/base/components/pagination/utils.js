/**
 * Given the number of pages to display, the current page and the total pages,
 * returns the min and max index of the pages to display in the pagination component.
 *
 * @param {integer} pagesToDisplay Maximum number of pages to display in the pagination component.
 * @param {integer} currentPage Page currently visible.
 * @param {integer} totalPages Total pages available.
 * @return {Object} Object containing the min and max index to display in the pagination component.
 */
export const getIndexes = ( pagesToDisplay, currentPage, totalPages ) => {
	if ( totalPages <= 2 ) {
		return { minIndex: null, maxIndex: null };
	}
	const extraPagesToDisplay = pagesToDisplay - 1;
	const tentativeMinIndex = Math.max(
		Math.floor( currentPage - extraPagesToDisplay / 2 ),
		2
	);
	const maxIndex = Math.min(
		Math.ceil(
			currentPage +
				( extraPagesToDisplay - ( currentPage - tentativeMinIndex ) )
		),
		totalPages - 1
	);
	const minIndex = Math.max(
		Math.floor(
			currentPage - ( extraPagesToDisplay - ( maxIndex - currentPage ) )
		),
		2
	);

	return { minIndex, maxIndex };
};
