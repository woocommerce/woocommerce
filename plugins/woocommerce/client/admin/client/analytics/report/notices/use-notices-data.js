/**
 * External dependencies
 */
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const NAMESPACE = '/wc-analytics/back-in-stock';

const DAY_MS = 24 * 60 * 60 * 1000;

/**
 * Reshape `/timeseries` API rows into the per-date object shape `<Chart>`
 * from `@woocommerce/components` expects, with zero-fill for missing days.
 *
 * @param {Array<{date:string,signups:number,notifications_sent:number}>} rows API rows.
 * @param {number}                                                        days Total span of days to render.
 * @return {{notifications:Array,signups:Array}} Two Chart-ready data series.
 */
function shapeChartData( rows, days ) {
	const today = new Date();
	today.setUTCHours( 0, 0, 0, 0 );
	const start = today.getTime() - ( days - 1 ) * DAY_MS;
	const byDate = Object.fromEntries(
		( rows || [] ).map( ( r ) => [ r.date, r ] )
	);

	const notifications = [];
	const signups = [];
	for ( let i = 0; i < days; i++ ) {
		const d = new Date( start + i * DAY_MS );
		const iso = d.toISOString().slice( 0, 10 );
		const isoMid = iso + 'T00:00:00';
		const row = byDate[ iso ] || {};
		notifications.push( {
			date: isoMid,
			notifications: {
				label: 'Notifications sent',
				value: Number( row.notifications_sent || 0 ),
			},
		} );
		signups.push( {
			date: isoMid,
			signups: {
				label: 'Sign-ups',
				value: Number( row.signups || 0 ),
			},
		} );
	}

	return { notifications, signups };
}

/**
 * Fetch every endpoint the Stock Notifications analytics dashboard needs and
 * return shaped data plus loading / error state.
 *
 * Refetches the "Most signed-up" leaderboard whenever `signupsWindow` changes;
 * everything else loads once on mount.
 *
 * @param {Object}                   args
 * @param {'week'|'month'|'quarter'} args.signupsWindow
 * @param {number}                   args.timeseriesDays Number of trailing days to plot.
 * @return {Object} { isLoading, isError, summary, charts, mostWanted, mostOverdue, mostSignedUp }
 */
export function useNoticesData( { signupsWindow, timeseriesDays = 15 } ) {
	const [ summary, setSummary ] = useState( null );
	const [ rawTimeseries, setRawTimeseries ] = useState( null );
	const [ mostWanted, setMostWanted ] = useState( null );
	const [ mostOverdue, setMostOverdue ] = useState( null );
	const [ mostSignedUp, setMostSignedUp ] = useState( null );
	const [ isError, setIsError ] = useState( false );

	const fetchTopDemand = useCallback( ( query, setter ) => {
		return apiFetch( {
			path: `${ NAMESPACE }/top-demand?${ new URLSearchParams(
				query
			).toString() }`,
		} )
			.then( ( res ) => setter( res.rows || [] ) )
			.catch( () => setIsError( true ) );
	}, [] );

	useEffect( () => {
		apiFetch( { path: `${ NAMESPACE }/summary` } )
			.then( setSummary )
			.catch( () => setIsError( true ) );

		apiFetch( {
			path: `${ NAMESPACE }/timeseries?days=${ timeseriesDays }`,
		} )
			.then( ( res ) => setRawTimeseries( res.rows || [] ) )
			.catch( () => setIsError( true ) );

		fetchTopDemand(
			{ sort_by: 'active_signups', limit: 5 },
			setMostWanted
		);
		fetchTopDemand( { sort_by: 'most_overdue', limit: 5 }, setMostOverdue );
	}, [ timeseriesDays, fetchTopDemand ] );

	useEffect( () => {
		fetchTopDemand(
			{ sort_by: 'period_signups', window: signupsWindow, limit: 5 },
			setMostSignedUp
		);
	}, [ signupsWindow, fetchTopDemand ] );

	const isLoading =
		summary === null ||
		rawTimeseries === null ||
		mostWanted === null ||
		mostOverdue === null ||
		mostSignedUp === null;

	const charts = rawTimeseries
		? shapeChartData( rawTimeseries, timeseriesDays )
		: { notifications: [], signups: [] };

	return {
		isLoading,
		isError,
		summary,
		charts,
		mostWanted: mostWanted || [],
		mostOverdue: mostOverdue || [],
		mostSignedUp: mostSignedUp || [],
	};
}
