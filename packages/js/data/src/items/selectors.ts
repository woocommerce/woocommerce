/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { getTotalCountResourceName } from './utils';

import { ItemType, ItemsState, Query, ItemIDOf, ItemInfer } from './types';

export type getItemsType = < T extends ItemType >(
	itemType: T,
	query: Query,
	defaultValue?: Map< ItemIDOf< T >, ItemInfer< T > | undefined >
) => Map< ItemIDOf< T >, ItemInfer< T > | undefined >;

type getItemsSelectorType = < T extends ItemType >(
	state: ItemsState,
	itemType: T,
	query: Query,
	defaultValue?: Map< ItemIDOf< T >, ItemInfer< T > | undefined >
) => Map< ItemIDOf< T >, ItemInfer< T > | undefined >;

export const getItems = createSelector< getItemsSelectorType >(
	( state, itemType, query, defaultValue = new Map() ) => {
		const resourceName = getResourceName( itemType, query );

		let entries;
		if (
			state.items[ resourceName ] &&
			typeof state.items[ resourceName ] === 'object'
		) {
			entries = state.items[ resourceName ].data;
		}

		if ( ! entries ) {
			return defaultValue;
		}
		return entries.reduce( ( map, entry ) => {
			const isCachedItem = typeof entry === 'object';
			const item = isCachedItem
				? entry
				: state.data[ itemType ]?.[ entry ];
			map.set( isCachedItem ? entry.id : entry, item );
			return map;
		}, new Map() );
	},
	( state, itemType, query ) => {
		const resourceName = getResourceName( itemType, query );
		return [ state.items[ resourceName ] ];
	}
);

export const getItemsTotalCount = (
	state: ItemsState,
	itemType: ItemType,
	query: Query,
	defaultValue = 0
) => {
	const resourceName = getTotalCountResourceName( itemType, query );
	const totalCount = state.items.hasOwnProperty( resourceName )
		? state.items[ resourceName ]
		: defaultValue;
	return totalCount;
};

export const getItemsError = (
	state: ItemsState,
	itemType: ItemType,
	query: Query
) => {
	const resourceName = getResourceName( itemType, query );
	return state.errors[ resourceName ];
};
