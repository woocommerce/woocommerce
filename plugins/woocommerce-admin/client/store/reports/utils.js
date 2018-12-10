/** @format */

/**
 * External dependencies
 */
import { find, forEach, isNull } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { appendTimestamp, getCurrentDates, getIntervalForQuery } from '@woocommerce/date';
import { flattenFilters, getActiveFiltersFromQuery, getUrlKey } from '@woocommerce/navigation';
import { formatCurrency } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { MAX_PER_PAGE, QUERY_DEFAULTS } from 'store/constants';
import * as categoriesConfig from 'analytics/report/categories/config';
import * as couponsConfig from 'analytics/report/coupons/config';
import * as ordersConfig from 'analytics/report/orders/config';
import * as productsConfig from 'analytics/report/products/config';
import * as taxesConfig from 'analytics/report/taxes/config';
import * as customersConfig from 'analytics/report/customers/config';
import * as reportsUtils from './utils';

const reportConfigs = {
	categories: categoriesConfig,
	coupons: couponsConfig,
	orders: ordersConfig,
	products: productsConfig,
	taxes: taxesConfig,
	customers: customersConfig,
};

export function getFilterQuery( endpoint, query ) {
	if ( reportConfigs[ endpoint ] ) {
		const { filters = [], advancedFilters = {} } = reportConfigs[ endpoint ];
		return filters
			.map( filter => getQueryFromConfig( filter, advancedFilters, query ) )
			.reduce( ( result, configQuery ) => Object.assign( result, configQuery ), {} );
	}
	return {};
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

		return activeFilters.reduce(
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
 * Constructs and returns a query associated with a Report data request.
 *
 * @param  {String} endpoint Report API Endpoint
 * @param  {String} dataType 'primary' or 'secondary'.
 * @param  {Object} query  query parameters in the url.
 * @returns {Object} data request query parameters.
 */
function getRequestQuery( endpoint, dataType, query ) {
	const datesFromQuery = getCurrentDates( query );
	const interval = getIntervalForQuery( query );
	const filterQuery = getFilterQuery( endpoint, query );
	return {
		order: 'asc',
		interval,
		per_page: MAX_PER_PAGE,
		after: appendTimestamp( datesFromQuery[ dataType ].after, 'start' ),
		before: appendTimestamp( datesFromQuery[ dataType ].before, 'end' ),
		...filterQuery,
	};
}

/**
 * Returns summary number totals needed to render a report page.
 *
 * @param  {String} endpoint Report  API Endpoint
 * @param  {Object} query  query parameters in the url
 * @param {object} select Instance of @wordpress/select
 * @return {Object}  Object containing summary number responses.
 */
export function getSummaryNumbers( endpoint, query, select ) {
	const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-api' );
	const response = {
		isRequesting: false,
		isError: false,
		totals: {
			primary: null,
			secondary: null,
		},
	};

	const primaryQuery = getRequestQuery( endpoint, 'primary', query );
	const primary = getReportStats( endpoint, primaryQuery );
	if ( isReportStatsRequesting( endpoint, primaryQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( isReportStatsError( endpoint, primaryQuery ) ) {
		return { ...response, isError: true };
	}

	const primaryTotals = ( primary && primary.data && primary.data.totals ) || null;

	const secondaryQuery = getRequestQuery( endpoint, 'secondary', query );
	const secondary = getReportStats( endpoint, secondaryQuery );
	if ( isReportStatsRequesting( endpoint, secondaryQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( isReportStatsError( endpoint, secondaryQuery ) ) {
		return { ...response, isError: true };
	}

	const secondaryTotals = ( secondary && secondary.data && secondary.data.totals ) || null;

	return { ...response, totals: { primary: primaryTotals, secondary: secondaryTotals } };
}

/**
 * Returns all of the data needed to render a chart with summary numbers on a report page.
 *
 * @param  {String} endpoint Report  API Endpoint
 * @param  {String} dataType 'primary' or 'secondary'
 * @param  {Object} query  query parameters in the url
 * @param {object} select Instance of @wordpress/select
 * @return {Object}  Object containing API request information (response, fetching, and error details)
 */
export function getReportChartData( endpoint, dataType, query, select ) {
	const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-api' );

	const response = {
		isEmpty: false,
		isError: false,
		isRequesting: false,
		data: {
			totals: null,
			intervals: [],
		},
	};

	const requestQuery = getRequestQuery( endpoint, dataType, query );
	const stats = getReportStats( endpoint, requestQuery );

	if ( isReportStatsRequesting( endpoint, requestQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( isReportStatsError( endpoint, requestQuery ) ) {
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
			const nextQuery = { ...requestQuery, page: i };
			const _data = getReportStats( endpoint, nextQuery );
			if ( isReportStatsRequesting( endpoint, nextQuery ) ) {
				continue;
			}
			if ( isReportStatsError( endpoint, nextQuery ) ) {
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

export function getReportTableQuery( endpoint, urlQuery, query ) {
	const filterQuery = getFilterQuery( endpoint, urlQuery );
	const datesFromQuery = getCurrentDates( urlQuery );

	return {
		orderby: urlQuery.orderby || 'date',
		order: urlQuery.order || 'asc',
		after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
		before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
		page: urlQuery.page || 1,
		per_page: urlQuery.per_page || QUERY_DEFAULTS.pageSize,
		...filterQuery,
		...query,
	};
}

/**
 * Returns table data needed to render a report page.
 *
 * @param  {String} endpoint  Report API Endpoint
 * @param  {Object} urlQuery  Query parameters in the url
 * @param  {object} select    Instance of @wordpress/select
 * @param  {Object} query     Query parameters specific for that endpoint
 * @return {Object} Object    Table data response
 */
export function getReportTableData( endpoint, urlQuery, select, query = {} ) {
	const { getReportItems, isReportItemsRequesting, isReportItemsError } = select( 'wc-api' );

	const tableQuery = reportsUtils.getReportTableQuery( endpoint, urlQuery, query );
	const response = {
		query: tableQuery,
		isRequesting: false,
		isError: false,
		items: {
			data: [],
		},
	};

	const items = getReportItems( endpoint, tableQuery );
	if ( isReportItemsRequesting( endpoint, tableQuery ) ) {
		return { ...response, isRequesting: true };
	} else if ( isReportItemsError( endpoint, tableQuery ) ) {
		return { ...response, isError: true };
	}

	return { ...response, items };
}
