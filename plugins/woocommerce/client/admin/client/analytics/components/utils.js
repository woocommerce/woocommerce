/**
 * External dependencies
 */
import { usesServerSideSearch } from '@woocommerce/data';

/**
 * Whether a report should render its empty search state.
 *
 * A search the client resolves itself only reaches the API once it has been turned into a
 * list of matching item IDs, so an active search without such a list means the search
 * matched nothing. Reports whose endpoint resolves the search itself never hit that state,
 * because the term is passed through and the API decides what it matches.
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
