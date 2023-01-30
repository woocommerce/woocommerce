/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import {
	applyNamespace,
	getGenericActionName,
	getRequestIdentifier,
	getUrlParameters,
	maybeReplaceIdQuery,
	parseId,
} from './utils';
import { getTotalCountResourceName } from '../utils';
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
	const itemQuery = getRequestIdentifier( CRUD_ACTIONS.CREATE_ITEM, query );
	return state.errors[ itemQuery ];
};

export const getItemDeleteError = (
	state: ResourceState,
	idQuery: IdQuery,
	namespace: string
) => {
	const urlParameters = getUrlParameters( namespace, idQuery );
	const { key } = parseId( idQuery, urlParameters );
	const itemQuery = getRequestIdentifier( CRUD_ACTIONS.DELETE_ITEM, key );
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
	const itemQuery = getRequestIdentifier( CRUD_ACTIONS.GET_ITEM, key );
	return state.errors[ itemQuery ];
};

export const getItems = createSelector(
	( state: ResourceState, query?: ItemQuery ) => {
		const itemQuery = getRequestIdentifier(
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
		const itemQuery = getRequestIdentifier(
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
	const itemQuery = getRequestIdentifier(
		CRUD_ACTIONS.GET_ITEMS,
		query || {}
	);
	return state.errors[ itemQuery ];
};

export const getItemUpdateError = (
	state: ResourceState,
	idQuery: IdQuery,
	urlParameters: IdType[]
) => {
	const { key } = parseId( idQuery, urlParameters );
	const itemQuery = getRequestIdentifier( CRUD_ACTIONS.UPDATE_ITEM, key );
	return state.errors[ itemQuery ];
};

const EMPTY_OBJECT = {};

export const createSelectors = ( {
	resourceName,
	pluralResourceName,
	namespace,
}: SelectorOptions ) => {
	const hasFinishedRequest = (
		state: ResourceState,
		action: string,
		args = []
	) => {
		const sanitizedArgs = maybeReplaceIdQuery( args, namespace );
		const actionName = getGenericActionName( action, resourceName );
		const requestId = getRequestIdentifier( actionName, ...sanitizedArgs );
		if ( action )
			return (
				state.requesting.hasOwnProperty( requestId ) &&
				! state.requesting[ requestId ]
			);
	};

	const isRequesting = (
		state: ResourceState,
		action: string,
		args = []
	) => {
		const sanitizedArgs = maybeReplaceIdQuery( args, namespace );
		const actionName = getGenericActionName( action, resourceName );
		const requestId = getRequestIdentifier( actionName, ...sanitizedArgs );
		return state.requesting[ requestId ];
	};

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
		hasFinishedRequest,
		isRequesting,
	};
};
