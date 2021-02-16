/**
 * Internal dependencies
 */
import TYPES from './action-types';

const reducer = (
	state = {
		activeItem: null,
		error: null,
		menuItems: [],
		favorites: [],
		requesting: {},
	},
	{ type, activeItem, error, favorite, favorites, menuItems }
) => {
	switch ( type ) {
		case TYPES.SET_ACTIVE_ITEM:
			state = {
				...state,
				activeItem,
			};
			break;
		case TYPES.SET_MENU_ITEMS:
			state = {
				...state,
				menuItems,
			};
			break;
		case TYPES.ADD_MENU_ITEMS:
			state = {
				...state,
				menuItems: [ ...state.menuItems, ...menuItems ],
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
				favorites,
				requesting: {
					...state.requesting,
					getFavorites: false,
				},
			};
			break;
		case TYPES.ADD_FAVORITE_FAILURE:
			state = {
				...state,
				error,
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
			const newFavorites = ! state.favorites.includes( favorite )
				? [ ...state.favorites, favorite ]
				: state.favorites;

			state = {
				...state,
				favorites: newFavorites,
				menuItems: state.menuItems.map( ( item ) => {
					if ( item.id === favorite ) {
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
					error,
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
				( f ) => f !== favorite
			);

			state = {
				...state,
				favorites: filteredFavorites,
				menuItems: state.menuItems.map( ( item ) => {
					if ( item.id === favorite ) {
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

export default reducer;
