/**
 * Internal dependencies
 */
import type {
	ReportsBalanceQuery,
	ReportsFeesQuery,
	ReportsFeesView,
	ReportsSortDirection,
} from './types';

const FEES_LIST_QUERY_PARAM_ORDER = [
	'page',
	'per_page',
	'sort',
	'direction',
	'date_before',
	'date_after',
	'date_between',
	'payment_method_type',
	'type',
	'search',
	'user_timezone',
] as const;

const FEES_SUMMARY_QUERY_PARAM_ORDER = [
	'date_before',
	'date_after',
	'date_between',
	'payment_method_type',
	'type',
	'search',
	'user_timezone',
] as const;

const FEES_EXPORT_QUERY_PARAM_ORDER = [
	...FEES_SUMMARY_QUERY_PARAM_ORDER,
	'user_email',
	'locale',
] as const;

const SORT_FIELD_BY_COLUMN_ID: Record< string, string > = {
	payment_method: 'source',
	transaction_currency: 'customer_currency',
	deposit_date: 'available_on',
};

const isSortDirection = ( value: unknown ): value is ReportsSortDirection =>
	value === 'asc' || value === 'desc';

const normalizePositiveInteger = (
	value: unknown,
	fallback: number
): number => {
	let parsed = Number.NaN;

	if ( typeof value === 'number' ) {
		parsed = value;
	} else if ( typeof value === 'string' ) {
		parsed = Number( value );
	}

	return Number.isInteger( parsed ) && parsed > 0 ? parsed : fallback;
};

const getUserTimeZone = () => {
	const offset = -new Date().getTimezoneOffset();
	const sign = offset >= 0 ? '+' : '-';
	const absoluteOffset = Math.abs( offset );
	const hours = String( Math.floor( absoluteOffset / 60 ) ).padStart(
		2,
		'0'
	);
	const minutes = String( absoluteOffset % 60 ).padStart( 2, '0' );

	return `${ sign }${ hours }:${ minutes }`;
};

const normalizeStringArray = ( value: unknown ): string[] | undefined => {
	if ( Array.isArray( value ) ) {
		const values = value
			.map( ( item ) => String( item ) )
			.filter( ( item ) => item !== '' );

		return values.length ? values : undefined;
	}

	if ( value === undefined || value === null || value === '' ) {
		return undefined;
	}

	return [ String( value ) ];
};

const getSingleString = ( value: unknown ): string | undefined => {
	if ( Array.isArray( value ) ) {
		return value.find( ( item ) => typeof item === 'string' && item );
	}

	return typeof value === 'string' && value ? value : undefined;
};

const getDateFilterQuery = (
	value: unknown,
	operator?: string
): Pick< ReportsFeesQuery, 'date_before' | 'date_after' | 'date_between' > => {
	const dateRange = normalizeStringArray( value );

	if ( Array.isArray( dateRange ) && dateRange.length >= 2 ) {
		return {
			date_between: dateRange.slice( 0, 2 ),
		};
	}

	const date = getSingleString( value );

	if ( ! date ) {
		return {};
	}

	if ( operator && operator.startsWith( 'before' ) ) {
		return { date_before: date };
	}

	if ( operator && operator.startsWith( 'after' ) ) {
		return { date_after: date };
	}

	return {
		date_between: [ date, date ],
	};
};

const addParam = ( params: URLSearchParams, key: string, value: unknown ) => {
	if ( value === undefined || value === null || value === '' ) {
		return;
	}

	if ( Array.isArray( value ) ) {
		value.forEach( ( item ) => addParam( params, `${ key }[]`, item ) );
		return;
	}

	params.append( key, String( value ) );
};

const serializeReportsQuery = (
	query: ReportsFeesQuery,
	paramOrder: readonly string[]
) => {
	const params = new URLSearchParams();

	paramOrder.forEach( ( key ) => {
		addParam( params, key, query[ key as keyof ReportsFeesQuery ] );
	} );

	return params.toString();
};

export const serializeReportsBalanceQuery = (
	query: ReportsBalanceQuery
): string => {
	const params = new URLSearchParams();

	addParam( params, 'date_start', query.date_start );
	addParam( params, 'date_end', query.date_end );
	addParam( params, 'currency', query.currency?.toLowerCase() );

	return params.toString();
};

export const buildReportsFeesQueryFromView = (
	view: Partial< ReportsFeesView >
): ReportsFeesQuery => {
	const query: ReportsFeesQuery = {
		page: normalizePositiveInteger( view.page, 1 ),
		per_page: normalizePositiveInteger( view.perPage, 25 ),
		sort:
			SORT_FIELD_BY_COLUMN_ID[ String( view.sort?.field || '' ) ] ||
			view.sort?.field ||
			'date',
		direction: isSortDirection( view.sort?.direction )
			? view.sort.direction
			: 'desc',
		user_timezone: getUserTimeZone(),
	};

	view.filters?.forEach( ( filter ) => {
		if ( filter.field === 'date' ) {
			Object.assign(
				query,
				getDateFilterQuery( filter.value, filter.operator )
			);
		}

		if ( filter.field === 'payment_method' ) {
			query.payment_method_type = getSingleString( filter.value );
		}

		if ( filter.field === 'type' ) {
			query.type = normalizeStringArray( filter.value );
		}
	} );

	const search = getSingleString( view.search );

	if ( search ) {
		query.search = [ search ];
	}

	return query;
};

export const serializeReportsFeesListQuery = (
	query: ReportsFeesQuery = {}
): string => serializeReportsQuery( query, FEES_LIST_QUERY_PARAM_ORDER );

export const serializeReportsFeesSummaryQuery = (
	query: ReportsFeesQuery = {}
): string => serializeReportsQuery( query, FEES_SUMMARY_QUERY_PARAM_ORDER );

export const serializeReportsFeesExportQuery = (
	query: ReportsFeesQuery = {}
): string => serializeReportsQuery( query, FEES_EXPORT_QUERY_PARAM_ORDER );
