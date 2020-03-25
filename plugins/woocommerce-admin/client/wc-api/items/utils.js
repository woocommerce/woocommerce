/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */
import { appendTimestamp, getCurrentDates } from 'lib/date';

/**
 * Returns leaderboard data to render a leaderboard table.
 *
 * @param  {Object} options                 arguments
 * @param  {string} options.id              Leaderboard ID
 * @param  {number} options.per_page       Per page limit
 * @param  {Object} options.persisted_query Persisted query passed to endpoint
 * @param  {Object} options.query           Query parameters in the url
 * @param  {Object} options.select          Instance of @wordpress/select
 * @param  {string} options.defaultDateRange   User specified default date range.
 * @return {Object} Object containing leaderboard responses.
 */
export function getLeaderboard( options ) {
	const endpoint = 'leaderboards';
	const {
		per_page: perPage,
		persisted_query: persistedQuery,
		query,
		select,
	} = options;
	const { getItems, getItemsError, isGetItemsRequesting } = select(
		'wc-api'
	);
	const response = {
		isRequesting: false,
		isError: false,
		rows: [],
	};

	const datesFromQuery = getCurrentDates( query, options.defaultDateRange );
	const leaderboardQuery = {
		after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
		before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
		per_page: perPage,
		persisted_query: JSON.stringify( persistedQuery ),
	};

	// Disable eslint rule requiring `getItems` to be defined below because the next two statements
	// depend on `getItems` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const leaderboards = getItems( endpoint, leaderboardQuery );

	if ( isGetItemsRequesting( endpoint, leaderboardQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getItemsError( endpoint, leaderboardQuery ) ) {
		return { ...response, isError: true };
	}

	const leaderboard = leaderboards.get( options.id );
	return { ...response, rows: leaderboard.rows };
}

/**
 * Returns items based on a search query.
 *
 * @param  {Object}   select    Instance of @wordpress/select
 * @param  {string}   endpoint  Report API Endpoint
 * @param  {string[]} search    Array of search strings.
 * @return {Object}   Object containing API request information and the matching items.
 */
export function searchItemsByString( select, endpoint, search ) {
	const { getItems, getItemsError, isGetItemsRequesting } = select(
		'wc-api'
	);

	const items = {};
	let isRequesting = false;
	let isError = false;
	search.forEach( ( searchWord ) => {
		const query = {
			search: searchWord,
			per_page: 10,
		};
		const newItems = getItems( endpoint, query );
		newItems.forEach( ( item, id ) => {
			items[ id ] = item;
		} );
		if ( isGetItemsRequesting( endpoint, query ) ) {
			isRequesting = true;
		}
		if ( getItemsError( endpoint, query ) ) {
			isError = true;
		}
	} );

	return { items, isRequesting, isError };
}
