/**
 * Internal dependencies
 */
import { getCategoriesMap, getMenuItemsByCategory } from '../';

describe( 'getCategoriesMap', () => {
	const menuItems = [
		{ id: 'zero', title: 'zero', isCategory: true },
		{ id: 'one', title: 'one', isCategory: true },
		{ id: 'two', title: 'two', isCategory: true },
		{ id: 'three', title: 'three', isCategory: false },
		{ id: 'four', title: 'four', isCategory: false },
	];

	it( 'should get a map of all categories', () => {
		const categoriesMap = getCategoriesMap( menuItems );

		expect( categoriesMap.zero ).toMatchObject( menuItems[ 0 ] );
		expect( categoriesMap.one ).toMatchObject( menuItems[ 1 ] );
		expect( categoriesMap.two ).toMatchObject( menuItems[ 2 ] );
		expect( categoriesMap.three ).toBeUndefined();
		expect( categoriesMap.four ).toBeUndefined();
	} );

	it( 'should include the topmost WooCommerce parent category', () => {
		const categoriesMap = getCategoriesMap( menuItems );

		expect( categoriesMap.woocommerce ).toBeDefined();
	} );

	it( 'should have the correct number of values', () => {
		const categoriesMap = getCategoriesMap( menuItems );

		expect( Object.keys( categoriesMap ).length ).toBe( 4 );
	} );
} );

describe( 'getMenuItemsByCategory', () => {
	it( 'should get a map of all categories and child elements', () => {
		const menuItems = [
			{
				id: 'child-one',
				title: 'child-one',
				isCategory: false,
				parent: 'parent',
				menuId: 'plugins',
			},
			{
				id: 'child-two',
				title: 'child-two',
				isCategory: false,
				parent: 'parent',
				menuId: 'plugins',
			},
			{
				id: 'parent',
				title: 'parent',
				isCategory: true,
				parent: 'woocommerce',
				menuId: 'plugins',
			},
		];
		const categoriesMap = getCategoriesMap( menuItems );
		const categorizedItems = getMenuItemsByCategory(
			categoriesMap,
			menuItems
		);

		expect( categorizedItems.woocommerce ).toBeDefined();
		expect( categorizedItems.woocommerce.plugins ).toBeDefined();
		expect( categorizedItems.woocommerce.plugins.length ).toBe( 1 );

		expect( categorizedItems.parent ).toBeDefined();
		expect( categorizedItems.parent.plugins ).toBeDefined();
		expect( categorizedItems.parent.plugins.length ).toBe( 2 );
	} );

	it( 'should handle multiple depths', () => {
		const menuItems = [
			{
				id: 'grand-child',
				title: 'grand-child',
				isCategory: false,
				parent: 'child',
				menuId: 'plugins',
			},
			{
				id: 'child',
				title: 'child',
				isCategory: true,
				parent: 'grand-parent',
				menuId: 'plugins',
			},
			{
				id: 'grand-parent',
				title: 'grand-parent',
				isCategory: true,
				parent: 'woocommerce',
				menuId: 'plugins',
			},
		];
		const categoriesMap = getCategoriesMap( menuItems );
		const categorizedItems = getMenuItemsByCategory(
			categoriesMap,
			menuItems
		);

		expect( categorizedItems[ 'grand-parent' ] ).toBeDefined();
		expect( categorizedItems[ 'grand-parent' ] ).toBeDefined();
		expect( categorizedItems[ 'grand-parent' ].plugins.length ).toBe( 1 );

		expect( categorizedItems.child ).toBeDefined();
		expect( categorizedItems.child ).toBeDefined();
		expect( categorizedItems.child.plugins.length ).toBe( 1 );

		expect( categorizedItems[ 'grand-child' ] ).not.toBeDefined();
	} );

	it( 'should group by menuId', () => {
		const menuItems = [
			{
				id: 'parent',
				title: 'parent',
				isCategory: true,
				parent: 'woocommerce',
				menuId: 'primary',
			},
			{
				id: 'primary-one',
				title: 'primary-one',
				isCategory: false,
				parent: 'parent',
				menuId: 'primary',
			},
			{
				id: 'primary-two',
				title: 'primary-two',
				isCategory: false,
				parent: 'parent',
				menuId: 'primary',
			},
		];
		const categoriesMap = getCategoriesMap( menuItems );
		const categorizedItems = getMenuItemsByCategory(
			categoriesMap,
			menuItems
		);

		expect( categorizedItems.parent ).toBeDefined();
		expect( categorizedItems.parent.primary ).toBeDefined();
		expect( categorizedItems.parent.primary.length ).toBe( 2 );
	} );

	it( 'should group children only if their menuId matches parent', () => {
		const menuItems = [
			{
				id: 'plugin-one',
				title: 'plugin-one',
				isCategory: false,
				parent: 'parent',
				menuId: 'plugins',
			},
			{
				id: 'plugin-two',
				title: 'plugin-two',
				isCategory: false,
				parent: 'parent',
				menuId: 'plugins',
			},
			{
				id: 'parent',
				title: 'parent',
				isCategory: true,
				parent: 'woocommerce',
				menuId: 'plugins',
			},
			{
				id: 'primary-one',
				title: 'primary-one',
				isCategory: false,
				parent: 'parent',
				menuId: 'primary',
			},
			{
				id: 'primary-two',
				title: 'primary-two',
				isCategory: false,
				parent: 'parent',
				menuId: 'primary',
			},
		];
		const categoriesMap = getCategoriesMap( menuItems );
		const categorizedItems = getMenuItemsByCategory(
			categoriesMap,
			menuItems
		);

		expect( categorizedItems.parent ).toBeDefined();
		expect( categorizedItems.parent.plugins ).toBeDefined();
		expect( categorizedItems.parent.plugins.length ).toBe( 2 );

		expect( categorizedItems.primary ).not.toBeDefined();
	} );
} );
