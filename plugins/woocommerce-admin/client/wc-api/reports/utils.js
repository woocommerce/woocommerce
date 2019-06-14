/** @format */

/**
 * External dependencies
 */
import { find, forEach, isNull, get, includes } from 'lodash';
import moment from 'moment';

/**
 * WooCommerce dependencies
 */
import { appendTimestamp, getCurrentDates, getIntervalForQuery } from '@woocommerce/date';
import { flattenFilters, getActiveFiltersFromQuery, getUrlKey } from '@woocommerce/navigation';
import { formatCurrency } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { MAX_PER_PAGE, QUERY_DEFAULTS } from 'wc-api/constants';
import * as reportsUtils from './utils';

/**
 * Add filters and advanced filters values to a query object.
 *
 * @param  {Objedt} options                   arguments
 * @param  {String} options.endpoint          Report API Endpoint
 * @param  {Object} options.query             Query parameters in the url
 * @param  {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {Array}  [options.filters]         config filters
 * @param  {Object} [options.advancedFilters] config advanced filters
 * @returns {Object} A query object with the values from filters and advanced fitlters applied.
 */
export function getFilterQuery( options ) {
	const { endpoint, query, limitBy, filters = [], advancedFilters = {} } = options;
	if ( query.search ) {
		const limitProperties = limitBy || [ endpoint ];
		return limitProperties.reduce( ( result, limitProperty ) => {
			result[ limitProperty ] = query[ limitProperty ];
			return result;
		}, {} );
	}

	return filters
		.map( filter => getQueryFromConfig( filter, advancedFilters, query ) )
		.reduce( ( result, configQuery ) => Object.assign( result, configQuery ), {} );
}

// Some stats endpoints don't have interval data, so they can ignore after/before params and omit that part of the response.
const noIntervalEndpoints = [ 'stock', 'customers' ];

/**
 * Add timestamp to advanced filter parameters involving date. The api
 * expects a timestamp for these values similar to `before` and `after`.
 *
 * @param {object} config - advancedFilters config object.
 * @param {object} activeFilter - an active filter.
 * @returns {object} - an active filter with timestamp added to date values.
 */
export function timeStampFilterDates( config, activeFilter ) {
	const advancedFilterConfig = config.filters[ activeFilter.key ];
	if ( 'Date' !== get( advancedFilterConfig, [ 'input', 'component' ] ) ) {
		return activeFilter;
	}

	const { rule, value } = activeFilter;
	const timeOfDayMap = {
		after: 'start',
		before: 'end',
	};
	// If the value is an array, it signifies "between" values which must have a timestamp
	// appended to each value.
	if ( Array.isArray( value ) ) {
		const [ after, before ] = value;
		return Object.assign( {}, activeFilter, {
			value: [
				appendTimestamp( moment( after ), timeOfDayMap.after ),
				appendTimestamp( moment( before ), timeOfDayMap.before ),
			],
		} );
	}

	return Object.assign( {}, activeFilter, {
		value: appendTimestamp( moment( value ), timeOfDayMap[ rule ] ),
	} );
}

export function getQueryFromConfig( config, advancedFilters, query ) {
	const queryValue = query[ config.param ];

	if ( ! queryValue ) {
		return {};
	}

	if ( 'advanced' === queryValue ) {
		const activeFilters = getActiveFiltersFromQuery( query, advancedFilters.filters );

		if ( activeFilters.length === 0 ) {
			return {};
		}

		return activeFilters.map( filter => timeStampFilterDates( advancedFilters, filter ) ).reduce(
			( result, activeFilter ) => {
				const { key, rule, value } = activeFilter;
				result[ getUrlKey( key, rule ) ] = value;
				return result;
			},
			{ match: query.match || 'all' }
		);
	}

	const filter = find( flattenFilters( config.filters ), { value: queryValue } );

	if ( ! filter ) {
		return {};
	}

	if ( filter.settings && filter.settings.param ) {
		const { param } = filter.settings;

		if ( query[ param ] ) {
			return {
				[ param ]: query[ param ],
			};
		}

		return {};
	}

	return {};
}

/**
 * Returns true if a report object is empty.
 *
 * @param  {Object}  report   Report to check
 * @param  {String}  endpoint Endpoint slug
 * @return {Boolean}        True if report is data is empty.
 */
export function isReportDataEmpty( report, endpoint ) {
	if ( ! report ) {
		return true;
	}
	if ( ! report.data ) {
		return true;
	}
	if ( ! report.data.totals || isNull( report.data.totals ) ) {
		return true;
	}

	const checkIntervals = ! includes( noIntervalEndpoints, endpoint );
	if ( checkIntervals && ( ! report.data.intervals || 0 === report.data.intervals.length ) ) {
		return true;
	}
	return false;
}

/**
 * Constructs and returns a query associated with a Report data request.
 * @param  {Objedt} options           arguments
 * @param  {String} options.endpoint  Report API Endpoint
 * @param  {String} options.dataType  'primary' or 'secondary'.
 * @param  {Object} options.query     Query parameters in the url.
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @returns {Object} data request query parameters.
 */
function getRequestQuery( options ) {
	const { endpoint, dataType, query } = options;
	const datesFromQuery = getCurrentDates( query );
	const interval = getIntervalForQuery( query );
	const filterQuery = getFilterQuery( options );
	const end = datesFromQuery[ dataType ].before;

	const noIntervals = includes( noIntervalEndpoints, endpoint );
	return noIntervals
		? { ...filterQuery }
		: {
				order: 'asc',
				interval,
				per_page: MAX_PER_PAGE,
				after: appendTimestamp( datesFromQuery[ dataType ].after, 'start' ),
				before: appendTimestamp( end, 'end' ),
				segmentby: query.segmentby,
				...filterQuery,
			};
}

/**
 * Returns summary number totals needed to render a report page.
 *
 * @param  {Objedt} options           arguments
 * @param  {String} options.endpoint  Report API Endpoint
 * @param  {Object} options.query     Query parameters in the url
 * @param  {Object} options.select    Instance of @wordpress/select
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @return {Object} Object containing summary number responses.
 */
export function getSummaryNumbers( options ) {
	const { endpoint, select } = options;
	const { getReportStats, getReportStatsError, isReportStatsRequesting } = select( 'wc-api' );
	const response = {
		isRequesting: false,
		isError: false,
		totals: {
			primary: null,
			secondary: null,
		},
	};

	const primaryQuery = getRequestQuery( { ...options, dataType: 'primary' } );
	const primary = getReportStats( endpoint, primaryQuery );
	if ( isReportStatsRequesting( endpoint, primaryQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getReportStatsError( endpoint, primaryQuery ) ) {
		return { ...response, isError: true };
	}

	const primaryTotals = ( primary && primary.data && primary.data.totals ) || null;

	const secondaryQuery = getRequestQuery( { ...options, dataType: 'secondary' } );
	const secondary = getReportStats( endpoint, secondaryQuery );
	if ( isReportStatsRequesting( endpoint, secondaryQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getReportStatsError( endpoint, secondaryQuery ) ) {
		return { ...response, isError: true };
	}

	const secondaryTotals = ( secondary && secondary.data && secondary.data.totals ) || null;

	return { ...response, totals: { primary: primaryTotals, secondary: secondaryTotals } };
}

/**
 * Returns all of the data needed to render a chart with summary numbers on a report page.
 * @param  {Objedt} options           arguments
 * @param  {String} options.endpoint  Report API Endpoint
 * @param  {String} options.dataType  'primary' or 'secondary'
 * @param  {Object} options.query     Query parameters in the url
 * @param  {Object} options.select    Instance of @wordpress/select
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @return {Object}  Object containing API request information (response, fetching, and error details)
 */
export function getReportChartData( options ) {
	const { endpoint, select } = options;
	const { getReportStats, getReportStatsError, isReportStatsRequesting } = select( 'wc-api' );

	const response = {
		isEmpty: false,
		isError: false,
		isRequesting: false,
		data: {
			totals: {},
			intervals: [],
		},
	};

	const requestQuery = getRequestQuery( options );
	const stats = getReportStats( endpoint, requestQuery );

	if ( isReportStatsRequesting( endpoint, requestQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getReportStatsError( endpoint, requestQuery ) ) {
		return { ...response, isError: true };
	} else if ( isReportDataEmpty( stats, endpoint ) ) {
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
			const nextQuery = { ...requestQuery, page: i };
			const _data = getReportStats( endpoint, nextQuery );
			if ( isReportStatsRequesting( endpoint, nextQuery ) ) {
				continue;
			}
			if ( getReportStatsError( endpoint, nextQuery ) ) {
				isError = true;
				isFetching = false;
				break;
			}

			pagedData.push( _data );
			if ( i === totalPages ) {
				isFetching = false;
				break;
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

/**
 * Returns a formatting function or string to be used by d3-format
 *
 * @param  {String} type Type of number, 'currency', 'number', 'percent', 'average'
 * @return {String|Function}  returns a number format based on the type or an overriding formatting function
 */
export function getTooltipValueFormat( type ) {
	switch ( type ) {
		case 'currency':
			return formatCurrency;
		case 'percent':
			return '.0%';
		case 'number':
			return ',';
		case 'average':
			return ',.2r';
		default:
			return ',';
	}
}

/**
 * Returns query needed for a request to populate a table.
 *
 * @param  {Objedt} options              arguments
 * @param  {Object} options.query        Query parameters in the url
 * @param  {Object} options.tableQuery   Query parameters specific for that endpoint
 * @return {Object} Object    Table data response
 */
export function getReportTableQuery( options ) {
	const { query, tableQuery = {} } = options;
	const filterQuery = getFilterQuery( options );
	const datesFromQuery = getCurrentDates( query );

	const noIntervals = includes( noIntervalEndpoints, options.endpoint );

	return {
		orderby: query.orderby || 'date',
		order: query.order || 'desc',
		after: noIntervals ? undefined : appendTimestamp( datesFromQuery.primary.after, 'start' ),
		before: noIntervals ? undefined : appendTimestamp( datesFromQuery.primary.before, 'end' ),
		page: query.page || 1,
		per_page: query.per_page || QUERY_DEFAULTS.pageSize,
		...filterQuery,
		...tableQuery,
	};
}

/**
 * Returns table data needed to render a report page.
 *
 * @param  {Object} options                arguments
 * @param  {String} options.endpoint       Report API Endpoint
 * @param  {Object} options.query          Query parameters in the url
 * @param  {Object} options.select         Instance of @wordpress/select
 * @param  {Object} options.tableQuery     Query parameters specific for that endpoint
 * @return {Object} Object    Table data response
 */
export function getReportTableData( options ) {
	const { endpoint, select } = options;
	const { getReportItems, getReportItemsError, isReportItemsRequesting } = select( 'wc-api' );

	const tableQuery = reportsUtils.getReportTableQuery( options );
	const response = {
		query: tableQuery,
		isRequesting: false,
		isError: false,
		items: {
			data: [],
			totalResults: 0,
		},
	};

	const items = getReportItems( endpoint, tableQuery );
	if ( isReportItemsRequesting( endpoint, tableQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getReportItemsError( endpoint, tableQuery ) ) {
		return { ...response, isError: true };
	}

	return { ...response, items };
}
