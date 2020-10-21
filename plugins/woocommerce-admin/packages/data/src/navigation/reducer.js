/**
 * Internal dependencies
 */
import TYPES from './action-types';

const reducer = (
	state = {
		activeItem: null,
		menuItems: [],
	},
	{ type, activeItem, menuItems }
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
	}
	return state;
};

export default reducer;
