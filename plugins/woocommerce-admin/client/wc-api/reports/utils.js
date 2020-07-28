/**
 * External dependencies
 */
import { find, forEach, isNull, get, includes } from 'lodash';
import moment from 'moment';

/**
 * WooCommerce dependencies
 */
import {
	appendTimestamp,
	getCurrentDates,
	getIntervalForQuery,
} from 'lib/date';
import {
	flattenFilters,
	getActiveFiltersFromQuery,
	getUrlKey,
} from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { MAX_PER_PAGE, QUERY_DEFAULTS } from 'wc-api/constants';
import * as reportsUtils from './utils';

/**
 * Add filters and advanced filters values to a query object.
 *
 * @param  {Object} options                   arguments
 * @param  {string} options.endpoint          Report API Endpoint
 * @param  {Object} options.query             Query parameters in the url
 * @param  {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {Array}  [options.filters]         config filters
 * @param  {Object} [options.advancedFilters] config advanced filters
 * @return {Object} A query object with the values from filters and advanced fitlters applied.
 */
export function getFilterQuery( options ) {
	const {
		endpoint,
		query,
		limitBy,
		filters = [],
		advancedFilters = {},
	} = options;
	if ( query.search ) {
		const limitProperties = limitBy || [ endpoint ];
		return limitProperties.reduce( ( result, limitProperty ) => {
			result[ limitProperty ] = query[ limitProperty ];
			return result;
		}, {} );
	}

	return filters
		.map( ( filter ) =>
			getQueryFromConfig( filter, advancedFilters, query )
		)
		.reduce(
			( result, configQuery ) => Object.assign( result, configQuery ),
			{}
		);
}

// Some stats endpoints don't have interval data, so they can ignore after/before params and omit that part of the response.
const noIntervalEndpoints = [ 'stock', 'customers' ];

/**
 * Add timestamp to advanced filter parameters involving date. The api
 * expects a timestamp for these values similar to `before` and `after`.
 *
 * @param {Object} config - advancedFilters config object.
 * @param {Object} activeFilter - an active filter.
 * @return {Object} - an active filter with timestamp added to date values.
 */
export function timeStampFilterDates( config, activeFilter ) {
	const advancedFilterConfig = config.filters[ activeFilter.key ];
	if ( get( advancedFilterConfig, [ 'input', 'component' ] ) !== 'Date' ) {
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

	if ( queryValue === 'advanced' ) {
		const activeFilters = getActiveFiltersFromQuery(
			query,
			advancedFilters.filters
		);

		if ( activeFilters.length === 0 ) {
			return {};
		}

		return activeFilters
			.map( ( filter ) =>
				timeStampFilterDates( advancedFilters, filter )
			)
			.reduce(
				( result, activeFilter ) => {
					const { key, rule, value } = activeFilter;
					result[ getUrlKey( key, rule ) ] = value;
					return result;
				},
				{ match: query.match || 'all' }
			);
	}

	const filter = find( flattenFilters( config.filters ), {
		value: queryValue,
	} );

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

	return {
		[ config.param ]: queryValue,
	};
}

/**
 * Returns true if a report object is empty.
 *
 * @param  {Object}  report   Report to check
 * @param  {string}  endpoint Endpoint slug
 * @return {boolean}        True if report is data is empty.
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
	if (
		checkIntervals &&
		( ! report.data.intervals || report.data.intervals.length === 0 )
	) {
		return true;
	}
	return false;
}

/**
 * Constructs and returns a query associated with a Report data request.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {string} options.dataType  'primary' or 'secondary'.
 * @param  {Object} options.query     Query parameters in the url.
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} data request query parameters.
 */
function getRequestQuery( options ) {
	const { endpoint, dataType, query, fields } = options;
	const datesFromQuery = getCurrentDates( query, options.defaultDateRange );
	const interval = getIntervalForQuery( query );
	const filterQuery = getFilterQuery( options );
	const end = datesFromQuery[ dataType ].before;

	const noIntervals = includes( noIntervalEndpoints, endpoint );
	return noIntervals
		? { ...filterQuery, fields }
		: {
				order: 'asc',
				interval,
				per_page: MAX_PER_PAGE,
				after: appendTimestamp(
					datesFromQuery[ dataType ].after,
					'start'
				),
				before: appendTimestamp( end, 'end' ),
				segmentby: query.segmentby,
				fields,
				...filterQuery,
		  };
}

/**
 * Returns summary number totals needed to render a report page.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {Object} options.query     Query parameters in the url
 * @param  {Object} options.select    Instance of @wordpress/select
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} Object containing summary number responses.
 */
export function getSummaryNumbers( options ) {
	const { endpoint, select } = options;
	const {
		getReportStats,
		getReportStatsError,
		isReportStatsRequesting,
	} = select( 'wc-api' );
	const response = {
		isRequesting: false,
		isError: false,
		totals: {
			primary: null,
			secondary: null,
		},
	};

	const primaryQuery = getRequestQuery( { ...options, dataType: 'primary' } );

	// Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
	// depend on `getReportStats` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const primary = getReportStats( endpoint, primaryQuery );

	if ( isReportStatsRequesting( endpoint, primaryQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getReportStatsError( endpoint, primaryQuery ) ) {
		return { ...response, isError: true };
	}

	const primaryTotals =
		( primary && primary.data && primary.data.totals ) || null;

	const secondaryQuery = getRequestQuery( {
		...options,
		dataType: 'secondary',
	} );

	// Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
	// depend on `getReportStats` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const secondary = getReportStats( endpoint, secondaryQuery );

	if ( isReportStatsRequesting( endpoint, secondaryQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getReportStatsError( endpoint, secondaryQuery ) ) {
		return { ...response, isError: true };
	}

	const secondaryTotals =
		( secondary && secondary.data && secondary.data.totals ) || null;

	return {
		...response,
		totals: { primary: primaryTotals, secondary: secondaryTotals },
	};
}

/**
 * Returns all of the data needed to render a chart with summary numbers on a report page.
 *
 * @param  {Object} options           arguments
 * @param  {string} options.endpoint  Report API Endpoint
 * @param  {string} options.dataType  'primary' or 'secondary'
 * @param  {Object} options.query     Query parameters in the url
 * @param  {Object} options.select    Instance of @wordpress/select
 * @param  {Array}  options.limitBy   Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object}  Object containing API request information (response, fetching, and error details)
 */
export function getReportChartData( options ) {
	const { endpoint, select } = options;
	const {
		getReportStats,
		getReportStatsError,
		isReportStatsRequesting,
	} = select( 'wc-api' );

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
	// Disable eslint rule requiring `stats` to be defined below because the next two if statements
	// depend on `getReportStats` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const stats = getReportStats( endpoint, requestQuery );

	if ( isReportStatsRequesting( endpoint, requestQuery ) ) {
		return { ...response, isRequesting: true };
	}

	if ( getReportStatsError( endpoint, requestQuery ) ) {
		return { ...response, isError: true };
	}

	if ( isReportDataEmpty( stats, endpoint ) ) {
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
		let pagesFetched = 1;

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
			pagesFetched++;

			if ( pagesFetched === totalPages ) {
				isFetching = false;
				break;
			}
		}

		if ( isFetching ) {
			return { ...response, isRequesting: true };
		} else if ( isError ) {
			return { ...response, isError: true };
		}

		forEach( pagedData, function ( _data ) {
			intervals = intervals.concat( _data.data.intervals );
		} );
	}

	return { ...response, data: { totals, intervals } };
}

/**
 * Returns a formatting function or string to be used by d3-format
 *
 * @param  {string} type Type of number, 'currency', 'number', 'percent', 'average'
 * @param  {Function} formatAmount format currency function
 * @return {string|Function}  returns a number format based on the type or an overriding formatting function
 */
export function getTooltipValueFormat( type, formatAmount ) {
	switch ( type ) {
		case 'currency':
			return formatAmount;
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
 * @param  {Object} options              arguments
 * @param  {Object} options.query        Query parameters in the url
 * @param  {Object} options.tableQuery   Query parameters specific for that endpoint
 * @param  {string} options.defaultDateRange   User specified default date range.
 * @return {Object} Object    Table data response
 */
export function getReportTableQuery( options ) {
	const { query, tableQuery = {} } = options;
	const filterQuery = getFilterQuery( options );
	const datesFromQuery = getCurrentDates( query, options.defaultDateRange );

	const noIntervals = includes( noIntervalEndpoints, options.endpoint );

	return {
		orderby: query.orderby || 'date',
		order: query.order || 'desc',
		after: noIntervals
			? undefined
			: appendTimestamp( datesFromQuery.primary.after, 'start' ),
		before: noIntervals
			? undefined
			: appendTimestamp( datesFromQuery.primary.before, 'end' ),
		page: query.paged || 1,
		per_page: query.per_page || QUERY_DEFAULTS.pageSize,
		...filterQuery,
		...tableQuery,
	};
}

/**
 * Returns table data needed to render a report page.
 *
 * @param  {Object} options                arguments
 * @param  {string} options.endpoint       Report API Endpoint
 * @param  {Object} options.query          Query parameters in the url
 * @param  {Object} options.select         Instance of @wordpress/select
 * @param  {Object} options.tableQuery     Query parameters specific for that endpoint
 * @param  {string}  options.defaultDateRange   User specified default date range.
 * @return {Object} Object    Table data response
 */
export function getReportTableData( options ) {
	const { endpoint, select } = options;
	const {
		getReportItems,
		getReportItemsError,
		isReportItemsRequesting,
	} = select( 'wc-api' );

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

	// Disable eslint rule requiring `items` to be defined below because the next two if statements
	// depend on `getReportItems` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const items = getReportItems( endpoint, tableQuery );

	if ( isReportItemsRequesting( endpoint, tableQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( getReportItemsError( endpoint, tableQuery ) ) {
		return { ...response, isError: true };
	}

	return { ...response, items };
}
