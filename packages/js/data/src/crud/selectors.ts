/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { applyNamespace, getUrlParameters, parseId } from './utils';
import { getResourceName, getTotalCountResourceName } from '../utils';
import { IdQuery, IdType, Item, ItemQuery } from './types';
import { ResourceState } from './reducer';
import CRUD_ACTIONS from './crud-actions';

type SelectorOptions = {
	resourceName: string;
	pluralResourceName: string;
	namespace: string;
};

export const getItemCreateError = (
	state: ResourceState,
	query: ItemQuery
) => {
	const itemQuery = getResourceName( CRUD_ACTIONS.CREATE_ITEM, query );
	return state.errors[ itemQuery ];
};

export const getItemDeleteError = (
	state: ResourceState,
	idQuery: IdQuery,
	namespace: string
) => {
	const urlParameters = getUrlParameters( namespace, idQuery );
	const { key } = parseId( idQuery, urlParameters );
	const itemQuery = getResourceName( CRUD_ACTIONS.DELETE_ITEM, { key } );
	return state.errors[ itemQuery ];
};

export const getItem = (
	state: ResourceState,
	idQuery: IdQuery,
	namespace: string
) => {
	const urlParameters = getUrlParameters( namespace, idQuery );
	const { key } = parseId( idQuery, urlParameters );
	return state.data[ key ];
};

export const getItemError = (
	state: ResourceState,
	idQuery: IdQuery,
	namespace: string
) => {
	const urlParameters = getUrlParameters( namespace, idQuery );
	const { key } = parseId( idQuery, urlParameters );
	const itemQuery = getResourceName( CRUD_ACTIONS.GET_ITEM, { key } );
	return state.errors[ itemQuery ];
};

export const getItems = createSelector(
	( state: ResourceState, query?: ItemQuery ) => {
		const itemQuery = getResourceName(
			CRUD_ACTIONS.GET_ITEMS,
			query || {}
		);

		const ids = state.items[ itemQuery ]
			? state.items[ itemQuery ].data
			: undefined;

		if ( ! ids ) {
			return null;
		}

		if ( query && query._fields ) {
			return ids.map( ( id: IdType ) => {
				return query._fields.reduce(
					( item: Partial< Item >, field: string ) => {
						return {
							...item,
							[ field ]: state.data[ id ][ field ],
						};
					},
					{} as Partial< Item >
				);
			} );
		}

		const data = ids
			.map( ( id: IdType ) => {
				return state.data[ id ];
			} )
			.filter( ( item ) => item !== undefined );

		return data;
	},
	( state, query ) => {
		const itemQuery = getResourceName(
			CRUD_ACTIONS.GET_ITEMS,
			query || {}
		);
		const ids = state.items[ itemQuery ]
			? state.items[ itemQuery ].data
			: undefined;

		return [
			state.items[ itemQuery ],
			...( ids || [] ).map( ( id: string ) => {
				return state.data[ id ];
			} ),
		];
	}
);

export const getItemsTotalCount = (
	state: ResourceState,
	query: ItemQuery,
	defaultValue = undefined
) => {
	const itemQuery = getTotalCountResourceName(
		CRUD_ACTIONS.GET_ITEMS,
		query || {}
	);
	const totalCount = state.itemsCount.hasOwnProperty( itemQuery )
		? state.itemsCount[ itemQuery ]
		: defaultValue;
	return totalCount;
};

export const getItemsError = ( state: ResourceState, query?: ItemQuery ) => {
	const itemQuery = getResourceName( CRUD_ACTIONS.GET_ITEMS, query || {} );
	return state.errors[ itemQuery ];
};

export const getItemUpdateError = (
	state: ResourceState,
	idQuery: IdQuery,
	urlParameters: IdType[]
) => {
	const params = parseId( idQuery, urlParameters );
	const { key } = params;
	const itemQuery = getResourceName( CRUD_ACTIONS.UPDATE_ITEM, {
		key,
		params,
	} );
	return state.errors[ itemQuery ];
};

const EMPTY_OBJECT = {};

export const createSelectors = ( {
	resourceName,
	pluralResourceName,
	namespace,
}: SelectorOptions ) => {
	return {
		[ `get${ resourceName }` ]: applyNamespace( getItem, namespace ),
		[ `get${ resourceName }Error` ]: applyNamespace(
			getItemError,
			namespace
		),
		[ `get${ pluralResourceName }` ]: applyNamespace( getItems, namespace, [
			EMPTY_OBJECT,
		] ),
		[ `get${ pluralResourceName }TotalCount` ]: applyNamespace(
			getItemsTotalCount,
			namespace,
			[ EMPTY_OBJECT, undefined ]
		),
		[ `get${ pluralResourceName }Error` ]: applyNamespace(
			getItemsError,
			namespace
		),
		[ `get${ resourceName }CreateError` ]: applyNamespace(
			getItemCreateError,
			namespace
		),
		[ `get${ resourceName }DeleteError` ]: applyNamespace(
			getItemDeleteError,
			namespace
		),
		[ `get${ resourceName }UpdateError` ]: applyNamespace(
			getItemUpdateError,
			namespace
		),
	};
};
