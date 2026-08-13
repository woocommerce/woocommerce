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

	return ! limitBy.every( ( item ) => query[ item ] && query[ item ].length );
}

/**
 * Whether an empty report is better explained by the search than by the date range.
 *
 * A report that resolves the search server side gets the same empty response whether the term
 * matched nothing or matched items with no data in the period, so the search is the closest
 * explanation it has. A report that resolves the search itself knows which of the two it is.
 *
 * @param {Object} query   Current query object.
 * @param {Array}  limitBy Properties used to limit the results.
 * @return {boolean} True when an empty report should be attributed to the search.
 */
export function isEmptyDueToSearch( query, limitBy ) {
	if ( ! query.search ) {
		return false;
	}

	return (
		usesServerSideSearch( limitBy ) ||
		hasEmptySearchResults( query, limitBy )
	);
}
