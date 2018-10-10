/** @format */

/**
 * External dependencies
 */
import { forEach, isNull } from 'lodash';

/**
 * Internal dependencies
 */
import { MAX_PER_PAGE } from 'store/constants';

/**
 * Returns true if a report object is empty.
 *
 * @param  {Object} report  Report to check
 * @return {Boolean}        True if report is data is empty.
 */
export function isReportDataEmpty( report ) {
	if ( ! report ) {
		return true;
	}
	if ( ! report.data ) {
		return true;
	}
	if ( ! report.data.totals || isNull( report.data.totals ) ) {
		return true;
	}
	if ( ! report.data.intervals || 0 === report.data.intervals.length ) {
		return true;
	}
	return false;
}

/**
 * Returns all of the data needed to render a chart with summary numbers on a report page.
 *
 * @param  {String} endpoint Report  API Endpoint
 * @param  {Object} query  API arguments
 * @param {object} select Instance of @wordpress/select
 * @return {Object}  Object containing API request information (response, fetching, and error details)
 */
export function getReportChartData( endpoint, query, select ) {
	const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-admin' );

	const response = {
		isEmpty: false,
		isError: false,
		isRequesting: false,
		data: {
			totals: null,
			intervals: [],
		},
	};

	query.after += 'T00:00:00+00:00';
	query.before += 'T23:59:59+00:00';

	const stats = getReportStats( endpoint, query );

	if ( isReportStatsRequesting( endpoint, query ) ) {
		return { ...response, isRequesting: true };
	} else if ( isReportStatsError( endpoint, query ) ) {
		return { ...response, isError: true };
	} else if ( isReportDataEmpty( stats ) ) {
		return { ...response, isEmpty: true };
	}

	const totals = ( stats && stats.data && stats.data.totals ) || null;
	let intervals = ( stats && stats.data && stats.data.intervals ) || [];

	// If we have more than 100 results for this time period,
	// we need to make additional requests to complete the response.
	if ( stats.totalResults > MAX_PER_PAGE ) {
		let isFetching = true;
		let isError = false;
		const pagedData = [];
		const totalPages = Math.ceil( stats.totalResults / MAX_PER_PAGE );

		for ( let i = 2; i <= totalPages; i++ ) {
			const _query = { ...query, page: i };
			const _data = getReportStats( endpoint, _query );
			if ( isReportStatsRequesting( endpoint, _query ) ) {
				continue;
			}
			if ( isReportStatsError( endpoint, _query ) ) {
				isError = true;
				isFetching = false;
				break;
			}
			if ( ! isReportStatsRequesting( endpoint, _query ) ) {
				pagedData.push( _data );
				if ( i === totalPages ) {
					isFetching = false;
					break;
				}
			}
		}

		if ( isFetching ) {
			return { ...response, isRequesting: true };
		} else if ( isError ) {
			return { ...response, isError: true };
		}

		forEach( pagedData, function( _data ) {
			intervals = intervals.concat( _data.data.intervals );
		} );
	}

	return { ...response, data: { totals, intervals } };
}
