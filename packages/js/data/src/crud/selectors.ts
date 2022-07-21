/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { IdType, Item, ItemQuery } from './types';
import { ResourceState } from './reducer';
import CRUD_ACTIONS from './crud-actions';

type SelectorOptions = {
	resourceName: string;
	pluralResourceName: string;
};

export const getItemCreateError = (
	state: ResourceState,
	query: ItemQuery
) => {
	const itemQuery = getResourceName( CRUD_ACTIONS.CREATE_ITEM, query );
	return state.errors[ itemQuery ];
};

export const getItemDeleteError = ( state: ResourceState, id: IdType ) => {
	const itemQuery = getResourceName( CRUD_ACTIONS.DELETE_ITEM, { id } );
	return state.errors[ itemQuery ];
};

export const getItem = ( state: ResourceState, id: IdType ) => {
	return state.data[ id ];
};

export const getItemError = ( state: ResourceState, id: IdType ) => {
	const itemQuery = getResourceName( CRUD_ACTIONS.GET_ITEM, { id } );
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

export const getItemUpdateError = ( state: ResourceState, id: IdType ) => {
	const itemQuery = getResourceName( CRUD_ACTIONS.UPDATE_ITEM, { id } );
	return state.errors[ itemQuery ];
};

export const createSelectors = ( {
	resourceName,
	pluralResourceName,
}: SelectorOptions ) => {
	return {
		[ `get${ resourceName }` ]: getItem,
		[ `get${ resourceName }Error` ]: getItemError,
		[ `get${ pluralResourceName }` ]: getItems,
		[ `get${ pluralResourceName }Error` ]: getItemsError,
		[ `get${ resourceName }CreateError` ]: getItemCreateError,
		[ `get${ resourceName }DeleteError` ]: getItemDeleteError,
		[ `get${ resourceName }UpdateError` ]: getItemUpdateError,
	};
};
