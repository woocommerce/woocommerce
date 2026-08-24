/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { getTotalCountResourceName } from './utils';

import { ItemType, ItemsState, Query, ItemID, ItemInfer } from './types';

/**
 * Public type for the `getItems` selector.
 *
 * Map keys are `ItemID` (`number | string`), rather than the previously
 * declared `number`. TypeScript consumers assigning the result to a numeric-
 * keyed map should use `Map< ItemID, ... >` and narrow string keys where a
 * numeric ID is required.
 */
export type getItemsType = < T extends ItemType >(
	itemType: T,
	query: Query,
	defaultValue?: Map< ItemID, ItemInfer< T > | undefined >
) => Map< ItemID, ItemInfer< T > | undefined >;

type getItemsSelectorType = < T extends ItemType >(
	state: ItemsState,
	itemType: T,
	query: Query,
	defaultValue?: Map< ItemID, ItemInfer< T > | undefined >
) => Map< ItemID, ItemInfer< T > | undefined >;

export const getItems = createSelector< getItemsSelectorType >(
	( state, itemType, query, defaultValue = new Map() ) => {
		const resourceName = getResourceName( itemType, query );

		let ids;
		if (
			state.items[ resourceName ] &&
			typeof state.items[ resourceName ] === 'object'
		) {
			ids = ( state.items[ resourceName ] as Record< string, number[] > )
				.data;
		}

		if ( ! ids ) {
			return defaultValue;
		}
		return ids.reduce( ( map, id: ItemID ) => {
			const item = state.data[ itemType ]?.[ id ];
			map.set( item?.id ?? id, item );
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
