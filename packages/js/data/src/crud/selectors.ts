/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { Item, ItemQuery } from './types';
import { ResourceState } from './reducer';
import CRUD_ACTIONS from './crud-actions';

type SelectorOptions = {
	resourceName: string;
	pluralResourceName: string;
};

export const createSelectors = ( {
	resourceName,
	pluralResourceName,
}: SelectorOptions ) => {
	const getCreateItemError = ( state: ResourceState, query: ItemQuery ) => {
		const itemQuery = getResourceName( CRUD_ACTIONS.CREATE_ITEM, query );
		return state.errors[ itemQuery ];
	};

	const getItem = ( state: ResourceState, id: number ) => {
		return state.data[ id ];
	};

	const getItemError = ( state: ResourceState, id: number ) => {
		const itemQuery = getResourceName( CRUD_ACTIONS.GET_ITEM, { id } );
		return state.errors[ itemQuery ];
	};

	const getItems = createSelector(
		( state: ResourceState, query: ItemQuery ) => {
			const itemQuery = getResourceName( CRUD_ACTIONS.GET_ITEMS, query );

			const ids = state.items[ itemQuery ]
				? state.items[ itemQuery ].data
				: undefined;

			if ( ! ids ) {
				return null;
			}

			if ( query._fields ) {
				return ids.map( ( id: number ) => {
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

			return ids.map( ( id: number ) => {
				return state.data[ id ];
			} );
		},
		( state, query ) => {
			const itemQuery = getResourceName( resourceName, query );
			const ids = state.items[ itemQuery ]
				? state.items[ itemQuery ].data
				: undefined;
			return [
				state.items[ itemQuery ],
				...( ids || [] ).map( ( id: number ) => {
					return state.data[ id ];
				} ),
			];
		}
	);

	const getItemsError = ( state: ResourceState, query: ItemQuery ) => {
		const itemQuery = getResourceName( CRUD_ACTIONS.GET_ITEMS, query );
		return state.errors[ itemQuery ];
	};

	const getUpdateItemError = ( state: ResourceState, id: number ) => {
		const itemQuery = getResourceName( CRUD_ACTIONS.UPDATE_ITEM, { id } );
		return state.errors[ itemQuery ];
	};

	return {
		[ `get${ resourceName }` ]: getItem,
		[ `get${ resourceName }Error` ]: getItemError,
		[ `get${ pluralResourceName }` ]: getItems,
		[ `get${ pluralResourceName }Error` ]: getItemsError,
		[ `getCreate${ resourceName }Error` ]: getCreateItemError,
		[ `getUpdate${ resourceName }Error` ]: getUpdateItemError,
	};
};
