/**
 * Internal dependencies
 */
import TYPES from './action-types';

const reducer = (
	state = {
		activeItem: null,
		menuItems:
			window.wcNavigation && window.wcNavigation.menuItems
				? window.wcNavigation.menuItems
				: [],
		siteTitle:
			window.wcNavigation && window.wcNavigation.siteTitle
				? window.wcNavigation.siteTitle
				: null,
		siteUrl:
			window.wcNavigation && window.wcNavigation.siteUrl
				? window.wcNavigation.siteUrl
				: null,
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
