/**
 * Internal dependencies
 */
import type {
	WooPaymentsDocumentsDataView,
	WooPaymentsDocumentsDataViewFilter,
	WooPaymentsDocumentsQuery,
} from './types';

export const DEFAULT_DOCUMENTS_QUERY: Required<
	Pick<
		WooPaymentsDocumentsQuery,
		'page' | 'pagesize' | 'sort' | 'direction'
	>
> = {
	page: 1,
	pagesize: 25,
	sort: 'date',
	direction: 'desc',
};

export const DOCUMENT_FILTER_PARAMS = [
	'match',
	'date_before',
	'date_after',
	'date_between',
	'type_is',
	'type_is_not',
] as const;

export const DOCUMENT_LIST_QUERY_PARAM_ORDER = [
	'page',
	'pagesize',
	'sort',
	'direction',
	...DOCUMENT_FILTER_PARAMS,
] as const;

export const DOCUMENT_SUMMARY_QUERY_PARAM_ORDER = [
	...DOCUMENT_FILTER_PARAMS,
] as const;

const DOCUMENT_DATAVIEWS_FILTER_PARAMS = DOCUMENT_FILTER_PARAMS.filter(
	( param ) => param !== 'match'
);

const FILTER_FIELD_ALIASES: Record< string, keyof WooPaymentsDocumentsQuery > =
	{
		date: 'date_between',
		date_before: 'date_before',
		date_after: 'date_after',
		date_between: 'date_between',
		type: 'type_is',
		type_is: 'type_is',
		type_is_not: 'type_is_not',
	};

const FILTER_PARAM_TO_FIELD: Partial<
	Record< keyof WooPaymentsDocumentsQuery, string >
> = {
	type_is: 'type',
	type_is_not: 'type',
};

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

const isSortDirection = ( value: unknown ): value is 'asc' | 'desc' =>
	value === 'asc' || value === 'desc';

const getSearchParams = ( locationOrSearch: string ) => {
	if ( locationOrSearch.startsWith( '?' ) ) {
		return new URLSearchParams( locationOrSearch.slice( 1 ) );
	}

	const queryIndex = locationOrSearch.indexOf( '?' );

	return new URLSearchParams(
		queryIndex === -1
			? locationOrSearch
			: locationOrSearch.slice( queryIndex + 1 )
	);
};

const getQueryValue = (
	params: URLSearchParams,
	key: string
): string | string[] | undefined => {
	const values = [
		...params.getAll( key ),
		...params.getAll( `${ key }[]` ),
	].filter( ( value ) => value !== '' );

	if ( values.length === 0 ) {
		return undefined;
	}

	return values.length === 1 ? values[ 0 ] : values;
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

const getFilterOperator = ( param: string, value: string | string[] ) => {
	if ( param === 'type_is_not' ) {
		return Array.isArray( value ) ? 'isNone' : 'isNot';
	}

	return Array.isArray( value ) ? 'isAny' : 'is';
};

const getFilterQueryValue = ( value: unknown ) => {
	if ( Array.isArray( value ) ) {
		const values = value
			.map( ( item ) => String( item ) )
			.filter( ( item ) => item !== '' );

		return values.length ? values : undefined;
	}

	if ( value === undefined || value === null || value === '' ) {
		return undefined;
	}

	return String( value );
};

const getFilterParamForDataViewFilter = (
	filter: WooPaymentsDocumentsDataViewFilter
): keyof WooPaymentsDocumentsQuery | undefined => {
	if ( filter.field === 'date' ) {
		if ( [ 'before', 'beforeInc' ].includes( filter.operator ) ) {
			return 'date_before';
		}

		if ( [ 'after', 'afterInc' ].includes( filter.operator ) ) {
			return 'date_after';
		}

		return 'date_between';
	}

	if (
		filter.field === 'type' &&
		[ 'isNot', 'isNone', 'isNotAll' ].includes( filter.operator )
	) {
		return 'type_is_not';
	}

	return FILTER_FIELD_ALIASES[ filter.field ];
};

export const parseDocumentsQuery = (
	locationOrSearch: string
): WooPaymentsDocumentsQuery => {
	const params = getSearchParams( locationOrSearch );
	const page = normalizePositiveInteger(
		params.get( 'page' ),
		DEFAULT_DOCUMENTS_QUERY.page
	);
	const pagesize = normalizePositiveInteger(
		params.get( 'pagesize' ) ||
			params.get( 'perPage' ) ||
			params.get( 'per_page' ),
		DEFAULT_DOCUMENTS_QUERY.pagesize
	);
	const direction = isSortDirection( params.get( 'direction' ) )
		? ( params.get( 'direction' ) as 'asc' | 'desc' )
		: DEFAULT_DOCUMENTS_QUERY.direction;
	const query: WooPaymentsDocumentsQuery = {
		page,
		pagesize,
		sort: params.get( 'sort' ) || DEFAULT_DOCUMENTS_QUERY.sort,
		direction,
	};

	DOCUMENT_FILTER_PARAMS.forEach( ( param ) => {
		const value = getQueryValue( params, param );

		if ( value ) {
			query[ param ] = value;
		}
	} );

	return query;
};

export const serializeDocumentsQuery = (
	query: WooPaymentsDocumentsQuery = {},
	paramOrder: readonly string[] = DOCUMENT_LIST_QUERY_PARAM_ORDER
): string => {
	const params = new URLSearchParams();

	paramOrder.forEach( ( key ) => {
		addParam( params, key, query[ key ] );
	} );

	return params.toString();
};

export const buildDocumentsRoutePath = (
	pathname: string,
	query: WooPaymentsDocumentsQuery
): string => {
	const queryString = serializeDocumentsQuery( query );

	return queryString ? `${ pathname }?${ queryString }` : pathname;
};

export const documentsQueryToDataViewsView = (
	query: WooPaymentsDocumentsQuery
): WooPaymentsDocumentsDataView => {
	const normalizedQuery = {
		...DEFAULT_DOCUMENTS_QUERY,
		...query,
	};
	const filters = DOCUMENT_DATAVIEWS_FILTER_PARAMS.reduce<
		WooPaymentsDocumentsDataViewFilter[]
	>( ( result, param ) => {
		const value = normalizedQuery[ param ];

		if (
			typeof value === 'string' ||
			( Array.isArray( value ) && value.length > 0 )
		) {
			result.push( {
				field: FILTER_PARAM_TO_FIELD[ param ] || param,
				operator: getFilterOperator( param, value ),
				value,
			} );
		}

		return result;
	}, [] );
	const view: WooPaymentsDocumentsDataView = {
		type: 'table',
		page: normalizePositiveInteger(
			normalizedQuery.page,
			DEFAULT_DOCUMENTS_QUERY.page
		),
		perPage: normalizePositiveInteger(
			normalizedQuery.pagesize,
			DEFAULT_DOCUMENTS_QUERY.pagesize
		),
		search:
			typeof normalizedQuery.match === 'string'
				? normalizedQuery.match
				: '',
		filters,
		fields: [ 'date', 'type', 'description', 'actions' ],
		titleField: 'type',
		showTitle: false,
		layout: {},
	};

	if (
		normalizedQuery.sort &&
		isSortDirection( normalizedQuery.direction )
	) {
		view.sort = {
			field: normalizedQuery.sort,
			direction: normalizedQuery.direction,
		};
	}

	return view;
};

export const dataViewsViewToDocumentsQuery = (
	view: WooPaymentsDocumentsDataView,
	currentQuery: WooPaymentsDocumentsQuery = {}
): WooPaymentsDocumentsQuery => {
	const query: WooPaymentsDocumentsQuery = {
		...currentQuery,
		page: normalizePositiveInteger(
			view.page,
			DEFAULT_DOCUMENTS_QUERY.page
		),
		pagesize: normalizePositiveInteger(
			view.perPage,
			DEFAULT_DOCUMENTS_QUERY.pagesize
		),
	};

	if ( view.sort ) {
		query.sort = view.sort.field;
		query.direction = view.sort.direction;
	} else {
		delete query.sort;
		delete query.direction;
	}

	DOCUMENT_DATAVIEWS_FILTER_PARAMS.forEach( ( param ) => {
		delete query[ param ];
	} );

	if ( view.search ) {
		query.match = view.search;
	} else {
		delete query.match;
	}

	view.filters?.forEach( ( filter ) => {
		const param = getFilterParamForDataViewFilter( filter );
		const value = getFilterQueryValue( filter.value );

		if ( ! param || value === undefined ) {
			return;
		}

		query[ param ] = value;
	} );

	return query;
};
