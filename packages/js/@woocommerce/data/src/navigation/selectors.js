/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';

const MENU_ITEMS_HOOK = 'woocommerce_navigation_menu_items';

export const getMenuItems = ( state ) => {
	/**
	 * Navigation Menu Items.
	 *
	 * @filter woocommerce_navigation_menu_items
	 * @param {Array.<Object>} menuItems Array of Navigation menu items.
	 */
	return applyFilters( MENU_ITEMS_HOOK, state.menuItems );
};

export const getFavorites = ( state ) => {
	return state.favorites || [];
};

export const isNavigationRequesting = ( state, selector ) => {
	return state.requesting[ selector ] || false;
};

export const getPersistedQuery = ( state ) => {
	return state.persistedQuery || {};
};
