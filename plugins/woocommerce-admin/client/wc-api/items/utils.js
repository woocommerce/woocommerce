/** @format */

/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */
import { appendTimestamp, getCurrentDates } from '@woocommerce/date';

/**
 * Returns leaderboard data to render a leaderboard table.
 *
 * @param  {Objedt} options                 arguments
 * @param  {String} options.id              Leaderboard ID
 * @param  {Integer} options.per_page       Per page limit
 * @param  {Object} options.persisted_query Persisted query passed to endpoint
 * @param  {Object} options.query           Query parameters in the url
 * @param  {Object} options.select          Instance of @wordpress/select
 * @return {Object} Object containing leaderboard responses.
 */
export function getLeaderboard( options ) {
	const endpoint = 'leaderboards';
	const { per_page, persisted_query, query, select } = options;
	const { getItems, getItemsError, isGetItemsRequesting } = select( 'wc-api' );
	const response = {
		isRequesting: false,
		isError: false,
		rows: [],
	};

	const datesFromQuery = getCurrentDates( query );
	const leaderboardQuery = {
		after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
		before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
		per_page,
		persisted_query,
	};

	const leaderboards = getItems( endpoint, leaderboardQuery );
	const leaderboard = leaderboards.get( options.id );
	if ( isGetItemsRequesting( endpoint, leaderboardQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getItemsError( endpoint, leaderboardQuery ) ) {
		return { ...response, isError: true };
	}

	return { ...response, rows: leaderboard.rows };
}

/**
 * Returns items based on a search query.
 *
 * @param  {Object}   select    Instance of @wordpress/select
 * @param  {String}   endpoint  Report API Endpoint
 * @param  {String[]} search    Array of search strings.
 * @return {Object}   Object containing API request information and the matching items.
 */
export function searchItemsByString( select, endpoint, search ) {
	const { getItems, getItemsError, isGetItemsRequesting } = select( 'wc-api' );

	const items = {};
	let isRequesting = false;
	let isError = false;
	search.forEach( searchWord => {
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
