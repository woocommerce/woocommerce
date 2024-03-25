/**
 * External dependencies
 */
import { find, forEach, isNull, get, includes, memoize } from 'lodash';
import moment from 'moment';
import {
	appendTimestamp,
	getCurrentDates,
	getIntervalForQuery,
} from '@woocommerce/date';
import {
	flattenFilters,
	getActiveFiltersFromQuery,
	getQueryFromActiveFilters,
} from '@woocommerce/navigation';
import deprecated from '@wordpress/deprecated';
import { select as WPSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as reportsUtils from './utils';
import { MAX_PER_PAGE, QUERY_DEFAULTS } from '../constants';
import { STORE_NAME } from './constants';
import { getResourceName } from '../utils';
import {
	ReportItemsEndpoint,
	ReportStatEndpoint,
	ReportStatObject,
} from './types';
import type { ReportsSelect } from './';

type Filter = {
	param: string;
	filters: Array< Record< string, unknown > >;
};

type AdvancedFilters =
	| {
			filters: {
				[ key: string ]: {
					input: {
						component: string;
					};
				};
			};
	  }
	| Record< string, never >;

type QueryOptions = {
	endpoint: ReportStatEndpoint;
	dataType: 'primary' | 'secondary';
	query: Record< string, string >;
	limitBy: string[];
	filters: Array< Filter >;
	advancedFilters: AdvancedFilters;
	defaultDateRange: string;
	tableQuery: Record< string, string >;
	fields: string[];
	selector: ReportsSelect;
	select: typeof WPSelect;
};

/**
 * Add timestamp to advanced filter parameters involving date. The api
 * expects a timestamp for these values similar to `before` and `after`.
 *
 * @param {Object} config       - advancedFilters config object.
 * @param {Object} activeFilter - an active filter.
 * @return {Object} - an active filter with timestamp added to date values.
 */
export function timeStampFilterDates(
	config: AdvancedFilters,
	activeFilter: ActiveFilter
) {
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

export function getQueryFromConfig(
	config: Filter,
	advancedFilters: QueryOptions[ 'advancedFilters' ],
	query: QueryOptions[ 'query' ]
) {
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

		const filterQuery = getQueryFromActiveFilters(
			activeFilters.map( ( filter ) =>
				timeStampFilterDates( advancedFilters, filter )
			),
			{},
			advancedFilters.filters
		);

		return {
			match: query.match || 'all',
			...filterQuery,
		};
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
 * Add filters and advanced filters values to a query object.
 *
 * @param {Object} options                   arguments
 * @param {string} options.endpoint          Report API Endpoint
 * @param {Object} options.query             Query parameters in the url
 * @param {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param {Array}  [options.filters]         config filters
 * @param {Object} [options.advancedFilters] config advanced filters
 * @return {Object} A query object with the values from filters and advanced fitlters applied.
 */
export function getFilterQuery(
	options: Omit< QueryOptions, 'endpoint' > & {
		endpoint: ReportItemsEndpoint | ReportStatEndpoint;
	}
) {
	const {
		endpoint,
		query,
		limitBy,
		filters = [],
		advancedFilters = {},
	} = options;
	if ( query.search ) {
		const limitProperties = limitBy || [ endpoint ];
		return limitProperties.reduce< Record< string, string > >(
			( result, limitProperty ) => {
				result[ limitProperty ] = query[ limitProperty ];
				return result;
			},
			{}
		);
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
const noIntervalEndpoints = [ 'stock', 'customers' ] as const;

type ActiveFilter = {
	key: string;
	rule: 'after' | 'before';
	value: string;
};

/**
 * Returns true if a report object is empty.
 *
 * @param {Object} report   Report to check
 * @param {string} endpoint Endpoint slug
 * @return {boolean}        True if report is data is empty.
 */
export function isReportDataEmpty(
	report: ReportStatObject,
	endpoint: ReportStatEndpoint
) {
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
 * @param {Object} options                   arguments
 * @param {string} options.endpoint          Report API Endpoint
 * @param {string} options.dataType          'primary' or 'secondary'.
 * @param {Object} options.query             Query parameters in the url.
 * @param {Array}  [options.filters]         config filters
 * @param {Object} [options.advancedFilters] config advanced filters
 * @param {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param {string} options.defaultDateRange  User specified default date range.
 * @return {Object} data request query parameters.
 */
export function getRequestQuery( options: QueryOptions ) {
	const { endpoint, dataType, query, fields, defaultDateRange } = options;
	const datesFromQuery = getCurrentDates( query, defaultDateRange );
	const interval = getIntervalForQuery( query, defaultDateRange );
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
 * @param {Object} options                   arguments
 * @param {string} options.endpoint          Report API Endpoint
 * @param {Object} options.query             Query parameters in the url
 * @param {Object} options.select            Instance of @wordpress/select
 * @param {Array}  [options.filters]         config filters
 * @param {Object} [options.advancedFilters] config advanced filters
 * @param {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param {string} options.defaultDateRange  User specified default date range.
 * @return {Object} Object containing summary number responses.
 */
export function getSummaryNumbers< T extends ReportStatEndpoint >(
	options: QueryOptions
) {
	const { endpoint, select } = options;
	const { getReportStats, getReportStatsError, isResolving } =
		select( STORE_NAME );
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
	const primary = getReportStats< T >( endpoint, primaryQuery );

	if ( isResolving( 'getReportStats', [ endpoint, primaryQuery ] ) ) {
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
	const secondary = getReportStats< T >( endpoint, secondaryQuery );

	if ( isResolving( 'getReportStats', [ endpoint, secondaryQuery ] ) ) {
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
 * Static responses object to avoid returning new references each call.
 */
const reportChartDataResponses = {
	requesting: {
		isEmpty: false,
		isError: false,
		isRequesting: true,
		data: {
			totals: {},
			intervals: [],
		},
	},
	error: {
		isEmpty: false,
		isError: true,
		isRequesting: false,
		data: {
			totals: {},
			intervals: [],
		},
	},
	empty: {
		isEmpty: true,
		isError: false,
		isRequesting: false,
		data: {
			totals: {},
			intervals: [],
		},
	},
};

const EMPTY_ARRAY = [] as const;

/**
 * Cache helper for returning the full chart dataset after multiple
 * requests. Memoized on the request query (string), only called after
 * all the requests have resolved successfully.
 */
const getReportChartDataResponse = memoize(
	( _requestString, totals, intervals ) => ( {
		isEmpty: false,
		isError: false,
		isRequesting: false,
		data: { totals, intervals },
	} ),
	( requestString, totals, intervals ) =>
		[ requestString, totals.length, intervals.length ].join( ':' )
);

/**
 * Returns all of the data needed to render a chart with summary numbers on a report page.
 *
 * @param {Object} options                  arguments
 * @param {string} options.endpoint         Report API Endpoint
 * @param {string} options.dataType         'primary' or 'secondary'
 * @param {Object} options.query            Query parameters in the url
 * @param {Object} options.selector         Instance of @wordpress/select response
 * @param {Object} options.select           (Depreciated) Instance of @wordpress/select
 * @param {Array}  options.limitBy          Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param {string} options.defaultDateRange User specified default date range.
 * @return {Object}  Object containing API request information (response, fetching, and error details)
 */
export function getReportChartData< T extends ReportStatEndpoint >(
	options: QueryOptions
) {
	const { endpoint } = options;
	let reportSelectors = options.selector;
	if ( options.select && ! options.selector ) {
		deprecated( 'option.select', {
			version: '1.7.0',
			hint: 'You can pass the report selectors through option.selector now.',
		} );
		reportSelectors = options.select( STORE_NAME );
	}
	const { getReportStats, getReportStatsError, isResolving } =
		reportSelectors;

	const requestQuery = getRequestQuery( options );
	// Disable eslint rule requiring `stats` to be defined below because the next two if statements
	// depend on `getReportStats` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const stats = getReportStats< T >( endpoint, requestQuery );

	if ( isResolving( 'getReportStats', [ endpoint, requestQuery ] ) ) {
		return reportChartDataResponses.requesting;
	}

	if ( getReportStatsError( endpoint, requestQuery ) ) {
		return reportChartDataResponses.error;
	}

	if ( isReportDataEmpty( stats, endpoint ) ) {
		return reportChartDataResponses.empty;
	}

	const totals = ( stats && stats.data && stats.data.totals ) || null;
	let intervals =
		( stats && stats.data && stats.data.intervals ) || EMPTY_ARRAY;

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
			const _data = getReportStats< T >( endpoint, nextQuery );
			if ( isResolving( 'getReportStats', [ endpoint, nextQuery ] ) ) {
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
			return reportChartDataResponses.requesting;
		} else if ( isError ) {
			return reportChartDataResponses.error;
		}

		forEach( pagedData, function ( _data ) {
			if (
				_data.data &&
				_data.data.intervals &&
				Array.isArray( _data.data.intervals )
			) {
				intervals = intervals.concat( _data.data.intervals );
			}
		} );
	}

	return getReportChartDataResponse(
		getResourceName( endpoint, requestQuery ),
		totals,
		intervals
	);
}

/**
 * Returns a formatting function or string to be used by d3-format
 *
 * @param {string}   type         Type of number, 'currency', 'number', 'percent', 'average'
 * @param {Function} formatAmount format currency function
 * @return {string|Function}  returns a number format based on the type or an overriding formatting function
 */
export function getTooltipValueFormat(
	type: string,
	formatAmount: ( amount: number ) => string
) {
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
 * @param {Object} options                  arguments
 * @param {string} options.endpoint         Report API Endpoint
 * @param {Object} options.query            Query parameters in the url
 * @param {Object} options.tableQuery       Query parameters specific for that endpoint
 * @param {string} options.defaultDateRange User specified default date range.
 * @return {Object} Object    Table data response
 */
export function getReportTableQuery(
	options: Omit< QueryOptions, 'endpoint' > & {
		endpoint: ReportItemsEndpoint;
	}
) {
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
 * @param {Object} options                  arguments
 * @param {string} options.endpoint         Report API Endpoint
 * @param {Object} options.query            Query parameters in the url
 * @param {Object} options.selector         Instance of @wordpress/select response
 * @param {Object} options.select           (depreciated) Instance of @wordpress/select
 * @param {Object} options.tableQuery       Query parameters specific for that endpoint
 * @param {string} options.defaultDateRange User specified default date range.
 * @return {Object} Object    Table data response
 */
export function getReportTableData< T extends ReportItemsEndpoint >(
	options: Omit< QueryOptions, 'endpoint' > & {
		endpoint: ReportItemsEndpoint;
	}
) {
	const { endpoint } = options;
	let reportSelectors = options.selector;
	if ( options.select && ! options.selector ) {
		deprecated( 'option.select', {
			version: '1.7.0',
			hint: 'You can pass the report selectors through option.selector now.',
		} );
		reportSelectors = options.select( STORE_NAME );
	}
	const { getReportItems, getReportItemsError, hasFinishedResolution } =
		reportSelectors;

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
	const items = getReportItems< T >( endpoint, tableQuery );

	const queryResolved = hasFinishedResolution( 'getReportItems', [
		endpoint,
		tableQuery,
	] );

	if ( ! queryResolved ) {
		return { ...response, isRequesting: true };
	}

	if ( getReportItemsError( endpoint, tableQuery ) ) {
		return { ...response, isError: true };
	}

	return { ...response, items };
}

export function isLeapYear( year: number ) {
	return ( year % 4 === 0 && year % 100 !== 0 ) || year % 400 === 0;
}

export function containsLeapYear( startDate: string, endDate: string ) {
	// Parse the input dates to get the years
	const startYear = new Date( startDate ).getFullYear();
	const endYear = new Date( endDate ).getFullYear();

	// Check each year in the range
	for ( let year = startYear; year <= endYear; year++ ) {
		if ( isLeapYear( year ) ) {
			console.log( `${ year } is a leap year.` );
			return true; // Found a leap year, no need to continue
		}
	}

	console.log( 'No leap years found.' );
	return false; // No leap years in the range
}
