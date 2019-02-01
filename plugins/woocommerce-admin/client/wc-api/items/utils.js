/** @format */

/**
 * External dependencies
 */

/**
 * Returns items based on a search query.
 *
 * @param  {Object} select    Instance of @wordpress/select
 * @param  {String} endpoint  Report API Endpoint
 * @param  {String} search    Search strings separated by commas.
 * @return {Object} Object    Object containing the matching items.
 */
export function searchItemsByString( select, endpoint, search ) {
	const { getItems } = select( 'wc-api' );
	const searchWords = search.split( ',' );

	const items = searchWords.reduce( ( acc, searchWord ) => {
		return {
			...acc,
			...getItems( endpoint, {
				search: searchWord,
				per_page: 10,
			} ),
		};
	}, [] );

	return items;
}
