/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = {
	error: null,
	menuItems: [],
	favorites: [],
	requesting: {},
	persistedQuery: {},
};

describe( 'navigation reducer', () => {
	it( 'should return a default state', () => {
		// @ts-expect-error -- we're testing the reducer's default state.
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
					url: 'https://example.com/menu-item-1',
					migrate: true,
					order: 1,
				},
				{
					id: 'menu-item-2',
					title: 'Menu Item 2',
					menuId: 'primary',
					url: 'https://example.com/menu-item-2',
					migrate: true,
					order: 2,
				},
				{
					id: 'menu-item-3',
					title: 'Menu Item 3',
					menuId: 'secondary',
					url: 'https://example.com/menu-item-3',
					migrate: true,
					order: 3,
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
						url: 'https://example.com/menu-item-1',
						migrate: true,
						order: 1,
					},
				],
				error: null,
				favorites: [],
				requesting: {},
				persistedQuery: {},
			},
			{
				type: TYPES.ADD_MENU_ITEMS,
				menuItems: [
					{
						id: 'menu-item-2',
						title: 'Menu Item 2',
						menuId: 'primary',
						url: 'https://example.com/menu-item-2',
						migrate: true,
						order: 2,
					},
				],
			}
		);

		expect( state.menuItems.length ).toBe( 2 );
		expect( state.menuItems[ 0 ].id ).toBe( 'menu-item-1' );
		expect( state.menuItems[ 1 ].id ).toBe( 'menu-item-2' );
	} );

	it( 'should set the favorites', () => {
		const favorites = [ 'favorite1', 'favorite2' ];
		const state = reducer( defaultState, {
			type: TYPES.GET_FAVORITES_SUCCESS,
			favorites,
		} );

		expect( state.favorites ).toEqual( favorites );
	} );

	it( 'should add a favorite', () => {
		const state = reducer(
			{
				...defaultState,
				favorites: [ 'favorite1', 'favorite2' ],
			},
			{
				type: TYPES.ADD_FAVORITE_SUCCESS,
				favorite: 'favorite3',
			}
		);

		expect( state.favorites ).toEqual( [
			'favorite1',
			'favorite2',
			'favorite3',
		] );
	} );

	it( 'should remove a favorite', () => {
		const state = reducer(
			{
				...defaultState,
				favorites: [ 'favorite1', 'favorite2' ],
			},
			{
				type: TYPES.REMOVE_FAVORITE_SUCCESS,
				favorite: 'favorite2',
			}
		);

		expect( state.favorites ).toEqual( [ 'favorite1' ] );
	} );
} );
