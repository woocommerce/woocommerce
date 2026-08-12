/**
 * External dependencies
 */
import { usesServerSideSearch } from '@woocommerce/data';

/**
 * Whether a report should render its empty search state.
 *
 * Only reports that resolve the search client side can know this: their query carries the
 * matching item IDs, so an active search without any means nothing matched.
 *
 * @param {Object} query   Current query object.
 * @param {Array}  limitBy Properties used to limit the results.
 * @return {boolean} True when the search is known to have matched nothing.
 */
export function hasEmptySearchResults( query, limitBy ) {
	if ( ! query.search || usesServerSideSearch( limitBy ) ) {
		return false;
	}

	return ! limitBy.some( ( item ) => query[ item ] && query[ item ].length );
}
