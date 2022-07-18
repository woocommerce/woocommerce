/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { applyUrlParameters, parseId } from './utils';
import { getResourceName } from '../utils';
import { IdQuery, IdType, Item, ItemQuery } from './types';
import { ResourceState } from './reducer';
import CRUD_ACTIONS from './crud-actions';

type SelectorOptions = {
	resourceName: string;
	pluralResourceName: string;
	urlParameters: IdType[];
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
	idQuery: IdQuery
) => {
	const { key } = parseId( idQuery );
	const itemQuery = getResourceName( CRUD_ACTIONS.DELETE_ITEM, { key } );
	return state.errors[ itemQuery ];
};

export const getItem = ( state: ResourceState, idQuery: IdQuery ) => {
	const { key } = parseId( idQuery );
	return state.data[ key ];
};

export const getItemError = ( state: ResourceState, idQuery: IdQuery ) => {
	const { key } = parseId( idQuery );
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

		return ids
			.map( ( id: IdType ) => {
				return state.data[ id ];
			} )
			.filter( ( item ) => item !== undefined );
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

export const createSelectors = ( {
	resourceName,
	pluralResourceName,
	urlParameters = [],
}: SelectorOptions ) => {
	return {
		[ `get${ resourceName }` ]: applyUrlParameters(
			getItem,
			urlParameters
		),
		[ `get${ resourceName }Error` ]: applyUrlParameters(
			getItemError,
			urlParameters
		),
		[ `get${ pluralResourceName }` ]: applyUrlParameters(
			getItems,
			urlParameters
		),
		[ `get${ pluralResourceName }Error` ]: applyUrlParameters(
			getItemsError,
			urlParameters
		),
		[ `get${ resourceName }CreateError` ]: applyUrlParameters(
			getItemCreateError,
			urlParameters
		),
		[ `get${ resourceName }DeleteError` ]: applyUrlParameters(
			getItemDeleteError,
			urlParameters
		),
		[ `get${ resourceName }UpdateError` ]: applyUrlParameters(
			getItemUpdateError,
			urlParameters
		),
	};
};
