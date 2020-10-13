/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = {
	activeItem: null,
	menuItems: [],
	siteTitle: null,
	siteUrl: null,
};

describe( 'navigation reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( "should set a menu's items", () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_MENU_ITEMS,
			menuItems: [
				{
					id: 'menu-item-1',
					title: 'Menu Item 1',
					menuId: 'primary',
				},
				{
					id: 'menu-item-2',
					title: 'Menu Item 2',
					menuId: 'primary',
				},
				{
					id: 'menu-item-3',
					title: 'Menu Item 3',
					menuId: 'secondary',
				},
			],
		} );

		expect( state.menuItems.length ).toBe( 3 );
		expect( state.menuItems[ 0 ].id ).toBe( 'menu-item-1' );
		expect( state.menuItems[ 1 ].id ).toBe( 'menu-item-2' );
		expect( state.menuItems[ 2 ].id ).toBe( 'menu-item-3' );
	} );

	it( 'should add menu items', () => {
		const state = reducer(
			{
				menuItems: [
					{
						id: 'menu-item-1',
						title: 'Menu Item 1',
						menuId: 'primary',
					},
				],
			},
			{
				type: TYPES.ADD_MENU_ITEMS,
				menuItems: [
					{
						id: 'menu-item-2',
						title: 'Menu Item 2',
						menuId: 'primary',
					},
				],
			}
		);

		expect( state.menuItems.length ).toBe( 2 );
		expect( state.menuItems[ 0 ].id ).toBe( 'menu-item-1' );
		expect( state.menuItems[ 1 ].id ).toBe( 'menu-item-2' );
	} );

	it( 'should set the active menu item', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_ACTIVE_ITEM,
			activeItem: 'test-active-item',
		} );

		expect( state.activeItem ).toBe( 'test-active-item' );
	} );
} );
