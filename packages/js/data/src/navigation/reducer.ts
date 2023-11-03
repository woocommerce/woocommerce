/**
 * External dependencies
 */
import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Action } from './actions';
import { NavigationState } from './types';

const reducer: Reducer< NavigationState, Action > = (
	state = {
		error: null,
		menuItems: [],
		favorites: [],
		requesting: {},
		persistedQuery: {},
	},
	action
) => {
	switch ( action.type ) {
		case TYPES.SET_MENU_ITEMS:
			state = {
				...state,
				menuItems: action.menuItems,
			};
			break;
		case TYPES.ADD_MENU_ITEMS:
			state = {
				...state,
				menuItems: [ ...state.menuItems, ...action.menuItems ],
			};
			break;
		case TYPES.ON_HISTORY_CHANGE:
			state = {
				...state,
				persistedQuery: action.persistedQuery,
			};
			break;
		case TYPES.GET_FAVORITES_FAILURE:
			state = {
				...state,
				requesting: {
					...state.requesting,
					getFavorites: false,
				},
			};
			break;
		case TYPES.GET_FAVORITES_REQUEST:
			state = {
				...state,
				requesting: {
					...state.requesting,
					getFavorites: true,
				},
			};
			break;
		case TYPES.GET_FAVORITES_SUCCESS:
			state = {
				...state,
				favorites: action.favorites,
				requesting: {
					...state.requesting,
					getFavorites: false,
				},
			};
			break;
		case TYPES.ADD_FAVORITE_FAILURE:
			state = {
				...state,
				error: action.error,
				requesting: {
					...state.requesting,
					addFavorite: false,
				},
			};
			break;
		case TYPES.ADD_FAVORITE_REQUEST:
			state = {
				...state,
				requesting: {
					...state.requesting,
					addFavorite: true,
				},
			};
			break;
		case TYPES.ADD_FAVORITE_SUCCESS:
			const newFavorites = ! state.favorites.includes( action.favorite )
				? [ ...state.favorites, action.favorite ]
				: state.favorites;

			state = {
				...state,
				favorites: newFavorites,
				menuItems: state.menuItems.map( ( item ) => {
					if ( item.id === action.favorite ) {
						return {
							...item,
							menuId: 'favorites',
						};
					}
					return item;
				} ),
				requesting: {
					...state.requesting,
					addFavorite: false,
				},
			};
			break;
		case TYPES.REMOVE_FAVORITE_FAILURE:
			state = {
				...state,
				requesting: {
					...state.requesting,
					error: action.error,
					removeFavorite: false,
				},
			};
			break;
		case TYPES.REMOVE_FAVORITE_REQUEST:
			state = {
				...state,
				requesting: {
					...state.requesting,
					removeFavorite: true,
				},
			};
			break;
		case TYPES.REMOVE_FAVORITE_SUCCESS:
			const filteredFavorites = state.favorites.filter(
				( f ) => f !== action.favorite
			);

			state = {
				...state,
				favorites: filteredFavorites,
				menuItems: state.menuItems.map( ( item ) => {
					if ( item.id === action.favorite ) {
						return {
							...item,
							menuId: 'plugins',
						};
					}
					return item;
				} ),
				requesting: {
					...state.requesting,
					removeFavorite: false,
				},
			};
			break;
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
