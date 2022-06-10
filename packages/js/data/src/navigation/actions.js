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

export function setMenuItems( menuItems ) {
	return {
		type: TYPES.SET_MENU_ITEMS,
		menuItems,
	};
}

export function addMenuItems( menuItems ) {
	return {
		type: TYPES.ADD_MENU_ITEMS,
		menuItems,
	};
}

export function getFavoritesFailure( error ) {
	return {
		type: TYPES.GET_FAVORITES_FAILURE,
		error,
	};
}

export function getFavoritesRequest( favorites ) {
	return {
		type: TYPES.GET_FAVORITES_REQUEST,
		favorites,
	};
}

export function getFavoritesSuccess( favorites ) {
	return {
		type: TYPES.GET_FAVORITES_SUCCESS,
		favorites,
	};
}

export function addFavoriteRequest( favorite ) {
	return {
		type: TYPES.ADD_FAVORITE_REQUEST,
		favorite,
	};
}

export function addFavoriteFailure( favorite, error ) {
	return {
		type: TYPES.ADD_FAVORITE_FAILURE,
		favorite,
		error,
	};
}

export function addFavoriteSuccess( favorite ) {
	return {
		type: TYPES.ADD_FAVORITE_SUCCESS,
		favorite,
	};
}

export function removeFavoriteRequest( favorite ) {
	return {
		type: TYPES.REMOVE_FAVORITE_REQUEST,
		favorite,
	};
}

export function removeFavoriteFailure( favorite, error ) {
	return {
		type: TYPES.REMOVE_FAVORITE_FAILURE,
		favorite,
		error,
	};
}

export function removeFavoriteSuccess( favorite, error ) {
	return {
		type: TYPES.REMOVE_FAVORITE_SUCCESS,
		favorite,
		error,
	};
}

export function* onLoad() {
	yield onHistoryChange();
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

export function* addFavorite( favorite ) {
	yield addFavoriteRequest( favorite );

	try {
		const results = yield apiFetch( {
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

export function* removeFavorite( favorite ) {
	yield removeFavoriteRequest( favorite );

	try {
		const results = yield apiFetch( {
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
