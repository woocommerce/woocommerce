/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { getPersistedQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';
import { MenuItem } from './types';

export function setMenuItems( menuItems: MenuItem[] ) {
	return {
		type: TYPES.SET_MENU_ITEMS,
		menuItems,
	};
}

export function addMenuItems( menuItems: MenuItem[] ) {
	return {
		type: TYPES.ADD_MENU_ITEMS,
		menuItems,
	};
}

export function getFavoritesFailure( error: unknown ) {
	return {
		type: TYPES.GET_FAVORITES_FAILURE,
		error,
	};
}

export function getFavoritesRequest( favorites?: string[] ) {
	return {
		type: TYPES.GET_FAVORITES_REQUEST,
		favorites,
	};
}

export function getFavoritesSuccess( favorites: string[] ) {
	return {
		type: TYPES.GET_FAVORITES_SUCCESS,
		favorites,
	};
}

export function addFavoriteRequest( favorite: string ) {
	return {
		type: TYPES.ADD_FAVORITE_REQUEST,
		favorite,
	};
}

export function addFavoriteFailure( favorite: string, error: unknown ) {
	return {
		type: TYPES.ADD_FAVORITE_FAILURE,
		favorite,
		error,
	};
}

export function addFavoriteSuccess( favorite: string ) {
	return {
		type: TYPES.ADD_FAVORITE_SUCCESS,
		favorite,
	};
}

export function removeFavoriteRequest( favorite: string ) {
	return {
		type: TYPES.REMOVE_FAVORITE_REQUEST,
		favorite,
	};
}

export function removeFavoriteFailure( favorite: string, error: unknown ) {
	return {
		type: TYPES.REMOVE_FAVORITE_FAILURE,
		favorite,
		error,
	};
}

export function removeFavoriteSuccess( favorite: string ) {
	return {
		type: TYPES.REMOVE_FAVORITE_SUCCESS,
		favorite,
	};
}

export function* onHistoryChange() {
	const persistedQuery = getPersistedQuery();

	if ( ! Object.keys( persistedQuery ).length ) {
		return null;
	}

	yield {
		type: TYPES.ON_HISTORY_CHANGE,
		persistedQuery,
	};
}

export function* onLoad() {
	yield onHistoryChange();
}

export function* addFavorite( favorite: string ) {
	yield addFavoriteRequest( favorite );

	try {
		const results: string[] = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/navigation/favorites/me`,
			method: 'POST',
			data: {
				item_id: favorite,
			},
		} );

		if ( results ) {
			yield addFavoriteSuccess( favorite );
			return results;
		}

		throw new Error();
	} catch ( error ) {
		yield addFavoriteFailure( favorite, error );
		throw new Error();
	}
}

export function* removeFavorite( favorite: string ) {
	yield removeFavoriteRequest( favorite );

	try {
		const results: string[] = yield apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/navigation/favorites/me`,
			method: 'DELETE',
			data: {
				item_id: favorite,
			},
		} );

		if ( results ) {
			yield removeFavoriteSuccess( favorite );
			return results;
		}

		throw new Error();
	} catch ( error ) {
		yield removeFavoriteFailure( favorite, error );
		throw new Error();
	}
}

export type Action = ReturnType<
	| typeof setMenuItems
	| typeof addMenuItems
	| typeof getFavoritesFailure
	| typeof getFavoritesRequest
	| typeof getFavoritesSuccess
	| typeof addFavoriteRequest
	| typeof addFavoriteFailure
	| typeof addFavoriteSuccess
	| typeof removeFavoriteRequest
	| typeof removeFavoriteFailure
	| typeof removeFavoriteSuccess
	| ( () => {
			type: typeof TYPES.ON_HISTORY_CHANGE;
			persistedQuery: ReturnType< typeof getPersistedQuery >;
	  } )
>;
