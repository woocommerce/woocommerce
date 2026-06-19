/**
 * Internal dependencies
 */
import type {
	WooPaymentsMoneyMovementDataView,
	WooPaymentsMoneyMovementDataViewFilter,
	WooPaymentsMoneyMovementDataViewFilterOperator,
	WooPaymentsMoneyMovementQuery,
	WooPaymentsMoneyMovementQueryFilterParam,
	WooPaymentsMoneyMovementRouteLocation,
	WooPaymentsMoneyMovementSortDirection,
} from './types';

export const DEFAULT_MONEY_MOVEMENT_QUERY: Required<
	Pick< WooPaymentsMoneyMovementQuery, 'page' | 'pagesize' >
> = {
	page: 1,
	pagesize: 25,
};

export const MONEY_MOVEMENT_FILTER_PARAMS = [
	'loan_id_is',
	'deposit_id',
	'store_currency_is',
	'type_is',
	'status_is',
	'status_is_not',
	'date_after',
	'date_before',
	'date_between',
] as const satisfies readonly WooPaymentsMoneyMovementQueryFilterParam[];

const QUERY_PARAM_ORDER = [
	'page',
	'pagesize',
	'sort',
	'direction',
	'search',
	...MONEY_MOVEMENT_FILTER_PARAMS,
] as const;

const FILTER_FIELD_ALIASES: Record<
	string,
	WooPaymentsMoneyMovementQueryFilterParam
> = {
	loan_id: 'loan_id_is',
	loan_id_is: 'loan_id_is',
	deposit: 'deposit_id',
	deposit_id: 'deposit_id',
	currency: 'store_currency_is',
	store_currency: 'store_currency_is',
	store_currency_is: 'store_currency_is',
	type: 'type_is',
	type_is: 'type_is',
	status: 'status_is',
	status_is: 'status_is',
	status_is_not: 'status_is_not',
	date_after: 'date_after',
	date_before: 'date_before',
	date_between: 'date_between',
};

const FILTER_PARAM_TO_FIELD: Partial<
	Record< WooPaymentsMoneyMovementQueryFilterParam, string >
> = {
	store_currency_is: 'currency',
	type_is: 'type',
	status_is: 'status',
	status_is_not: 'status',
	loan_id_is: 'loan_id',
};

const isSortDirection = (
	value: unknown
): value is WooPaymentsMoneyMovementSortDirection =>
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

const getSearchString = (
	locationOrSearch: WooPaymentsMoneyMovementRouteLocation | string
) => {
	if ( typeof locationOrSearch !== 'string' ) {
		return locationOrSearch.search || '';
	}

	if ( locationOrSearch.startsWith( '?' ) ) {
		return locationOrSearch;
	}

	const queryIndex = locationOrSearch.indexOf( '?' );

	return queryIndex === -1
		? locationOrSearch
		: locationOrSearch.slice( queryIndex + 1 );
};

const getSearchParams = (
	locationOrSearch: WooPaymentsMoneyMovementRouteLocation | string
) =>
	new URLSearchParams(
		getSearchString( locationOrSearch ).replace( /^\?/, '' )
	);

const getQueryValue = (
	params: URLSearchParams,
	key: string
): string | string[] | undefined => {
	const values = params.getAll( key ).filter( ( value ) => value !== '' );

	if ( values.length === 0 ) {
		return undefined;
	}

	return values.length === 1 ? values[ 0 ] : values;
};

const getFirstString = ( value: unknown ): string | undefined => {
	if ( Array.isArray( value ) ) {
		return value.find( ( item ) => typeof item === 'string' && item );
	}

	return typeof value === 'string' && value ? value : undefined;
};

const addParam = ( params: URLSearchParams, key: string, value: unknown ) => {
	if ( value === undefined || value === null || value === '' ) {
		return;
	}

	if ( Array.isArray( value ) ) {
		value.forEach( ( item ) => addParam( params, key, item ) );
		return;
	}

	params.append( key, String( value ) );
};

const getFilterOperator = (
	param: WooPaymentsMoneyMovementQueryFilterParam,
	value: string | string[]
): WooPaymentsMoneyMovementDataViewFilterOperator => {
	if ( param === 'status_is_not' ) {
		return Array.isArray( value ) ? 'isNone' : 'isNot';
	}

	return Array.isArray( value ) ? 'isAny' : 'is';
};

const getFilterParamForDataViewFilter = (
	filter: WooPaymentsMoneyMovementDataViewFilter
): WooPaymentsMoneyMovementQueryFilterParam | undefined => {
	if (
		filter.field === 'status' &&
		[ 'isNot', 'isNone', 'isNotAll' ].includes( filter.operator )
	) {
		return 'status_is_not';
	}

	if (
		filter.field === 'status_is' &&
		[ 'isNot', 'isNone', 'isNotAll' ].includes( filter.operator )
	) {
		return 'status_is_not';
	}

	return FILTER_FIELD_ALIASES[ filter.field ];
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

export const parseMoneyMovementQuery = (
	locationOrSearch: WooPaymentsMoneyMovementRouteLocation | string,
	defaults: WooPaymentsMoneyMovementQuery = {}
): WooPaymentsMoneyMovementQuery => {
	const params = getSearchParams( locationOrSearch );
	const page = normalizePositiveInteger(
		params.get( 'page' ),
		normalizePositiveInteger(
			defaults.page,
			DEFAULT_MONEY_MOVEMENT_QUERY.page
		)
	);
	const pagesize = normalizePositiveInteger(
		params.get( 'pagesize' ) || params.get( 'perPage' ),
		normalizePositiveInteger(
			defaults.pagesize,
			DEFAULT_MONEY_MOVEMENT_QUERY.pagesize
		)
	);
	const direction =
		( isSortDirection( params.get( 'direction' ) )
			? ( params.get(
					'direction'
			  ) as WooPaymentsMoneyMovementSortDirection )
			: undefined ) ||
		( isSortDirection( defaults.direction )
			? defaults.direction
			: undefined );
	const query: WooPaymentsMoneyMovementQuery = {
		page,
		pagesize,
	};
	const sort = params.get( 'sort' ) || defaults.sort;
	const search = getQueryValue( params, 'search' ) || defaults.search;

	if ( sort ) {
		query.sort = sort;
	}

	if ( direction ) {
		query.direction = direction;
	}

	if ( search ) {
		query.search = search;
	}

	MONEY_MOVEMENT_FILTER_PARAMS.forEach( ( param ) => {
		const value = getQueryValue( params, param );

		if ( value ) {
			query[ param ] = value;
		}
	} );

	return query;
};

export const serializeMoneyMovementQuery = (
	query: WooPaymentsMoneyMovementQuery
): string => {
	const params = new URLSearchParams();

	QUERY_PARAM_ORDER.forEach( ( key ) => {
		addParam( params, key, query[ key ] );
	} );

	return params.toString();
};

export const buildMoneyMovementRoutePath = (
	pathname: string,
	query: WooPaymentsMoneyMovementQuery
): string => {
	const queryString = serializeMoneyMovementQuery( query );

	return queryString ? `${ pathname }?${ queryString }` : pathname;
};

export const moneyMovementQueryToDataViewsView = (
	query: WooPaymentsMoneyMovementQuery,
	options: {
		fields?: string[];
		titleField?: string;
		showTitle?: boolean;
		layout?: Record< string, unknown >;
	} = {}
): WooPaymentsMoneyMovementDataView => {
	const normalizedQuery = {
		...DEFAULT_MONEY_MOVEMENT_QUERY,
		...query,
	};
	const filters = MONEY_MOVEMENT_FILTER_PARAMS.reduce<
		WooPaymentsMoneyMovementDataViewFilter[]
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
	const view: WooPaymentsMoneyMovementDataView = {
		type: 'table',
		page: normalizePositiveInteger(
			normalizedQuery.page,
			DEFAULT_MONEY_MOVEMENT_QUERY.page
		),
		perPage: normalizePositiveInteger(
			normalizedQuery.pagesize,
			DEFAULT_MONEY_MOVEMENT_QUERY.pagesize
		),
		search: getFirstString( normalizedQuery.search ) || '',
		filters,
		fields: options.fields || [],
		layout: options.layout || {},
	};
	const direction = isSortDirection( normalizedQuery.direction )
		? normalizedQuery.direction
		: undefined;

	if ( normalizedQuery.sort && direction ) {
		view.sort = {
			field: normalizedQuery.sort,
			direction,
		};
	}

	if ( options.titleField ) {
		view.titleField = options.titleField;
	}

	if ( options.showTitle !== undefined ) {
		view.showTitle = options.showTitle;
	}

	return view;
};

export const dataViewsViewToMoneyMovementQuery = (
	view: WooPaymentsMoneyMovementDataView,
	currentQuery: WooPaymentsMoneyMovementQuery = {}
): WooPaymentsMoneyMovementQuery => {
	const query: WooPaymentsMoneyMovementQuery = {
		...currentQuery,
		page: normalizePositiveInteger(
			view.page,
			DEFAULT_MONEY_MOVEMENT_QUERY.page
		),
		pagesize: normalizePositiveInteger(
			view.perPage,
			DEFAULT_MONEY_MOVEMENT_QUERY.pagesize
		),
	};

	if ( view.search ) {
		query.search = view.search;
	} else {
		delete query.search;
	}

	if ( view.sort ) {
		query.sort = view.sort.field;
		query.direction = view.sort.direction;
	} else {
		delete query.sort;
		delete query.direction;
	}

	MONEY_MOVEMENT_FILTER_PARAMS.forEach( ( param ) => {
		delete query[ param ];
	} );

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
