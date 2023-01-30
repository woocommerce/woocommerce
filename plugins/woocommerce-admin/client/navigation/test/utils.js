/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import {
	getDefaultMatchExpression,
	getFullUrl,
	getMappedItemsCategories,
	getMatchingItem,
	getMatchScore,
	sortMenuItems,
} from '../utils';

const originalLocation = window.location;
global.window = Object.create( window );
global.window.wcNavigation = {};

const sampleMenuItems = [
	{
		id: 'main',
		title: 'Main page',
		url: 'admin.php?page=wc-admin',
	},
	{
		id: 'path',
		title: 'Page with Path',
		url: 'admin.php?page=wc-admin&path=/test-path',
	},
	{
		id: 'hash',
		title: 'Page with Hash',
		url: 'admin.php?page=wc-admin&path=/test-path#anchor',
	},
	{
		id: 'multiple-args',
		title: 'Page with multiple arguments',
		url: 'admin.php?page=wc-admin&path=/test-path&section=section-name',
	},
	{
		id: 'multiple-args-plus-one',
		title: 'Page with same multiple arguments plus an additional one',
		url: 'admin.php?page=wc-admin&path=/test-path&section=section-name&version=22',
	},
	{
		id: 'hash-and-multiple-args',
		title: 'Page with multiple arguments and a hash',
		url: 'admin.php?page=wc-admin&path=/test-path&section=section-name#anchor',
	},
];

const runGetMatchingItemTests = ( items ) => {
	it( 'should get the closest matched item', () => {
		window.location = new URL( getAdminLink( 'admin.php?page=wc-admin' ) );
		const matchingItem = getMatchingItem( items );
		expect( matchingItem.id ).toBe( 'main' );
	} );

	it( 'should match the item without hash if a better match does not exist', () => {
		window.location = new URL(
			getAdminLink( 'admin.php?page=wc-admin#hash' )
		);
		const matchingItem = getMatchingItem( items );
		expect( matchingItem.id ).toBe( 'main' );
	} );

	it( 'should exactly match the item with a hash if it exists', () => {
		window.location = new URL(
			getAdminLink( 'admin.php?page=wc-admin&path=/test-path#anchor' )
		);
		const matchingItem = getMatchingItem( items );
		expect( matchingItem.id ).toBe( 'hash' );
	} );

	it( 'should roughly match the item if all menu item arguments exist', () => {
		window.location = new URL(
			getAdminLink(
				'admin.php?page=wc-admin&path=/test-path&section=section-name'
			)
		);
		const matchingItem = getMatchingItem( items );
		expect( matchingItem.id ).toBe( 'multiple-args' );
	} );

	it( 'should match an item with irrelevant query parameters', () => {
		window.location = new URL(
			getAdminLink(
				'admin.php?page=wc-admin&path=/test-path&section=section-name&foo=bar'
			)
		);
		const matchingItem = getMatchingItem( items );
		expect( matchingItem.id ).toBe( 'multiple-args' );
	} );

	it( 'should match an item with similar query args plus one additional arg', () => {
		window.location = new URL(
			getAdminLink(
				'admin.php?page=wc-admin&path=/test-path&section=section-name&version=22'
			)
		);
		const matchingItem = getMatchingItem( items );
		expect( matchingItem.id ).toBe( 'multiple-args-plus-one' );
	} );

	it( 'should match an item with query parameters in mixed order', () => {
		window.location = new URL(
			getAdminLink(
				'admin.php?foo=bar&page=wc-admin&path=/test-path&section=section-name'
			)
		);
		const matchingItem = getMatchingItem( items );
		expect( matchingItem.id ).toBe( 'multiple-args' );
	} );

	it( 'should match an item with query parameters and a hash', () => {
		window.location = new URL(
			getAdminLink(
				'admin.php?foo=bar&page=wc-admin&path=/test-path&section=section-name#anchor'
			)
		);
		const matchingItem = getMatchingItem( items );
		expect( matchingItem.id ).toBe( 'hash-and-multiple-args' );
	} );
};

describe( 'getMatchingItem', () => {
	beforeAll( () => {
		delete window.location;
	} );

	afterAll( () => {
		window.location = originalLocation;
	} );

	runGetMatchingItemTests( sampleMenuItems );
	// re-run the tests with sampleMenuItems in reverse order.
	runGetMatchingItemTests( sampleMenuItems.reverse() );
} );

describe( 'getDefaultMatchExpression', () => {
	it( 'should return the regex for the path without query args', () => {
		expect( getDefaultMatchExpression( 'http://wordpress.org' ) ).toBe(
			'^http:\\/\\/wordpress\\.org'
		);
	} );

	it( 'should return the regex for the path and query args', () => {
		expect(
			getDefaultMatchExpression(
				'http://wordpress.org?param1=a&param2=b'
			)
		).toBe(
			'^http:\\/\\/wordpress\\.org(?=.*[?|&]param1=a(&|$|#))(?=.*[?|&]param2=b(&|$|#))'
		);
	} );

	it( 'should return the regex with hash if present', () => {
		expect(
			getDefaultMatchExpression(
				'http://wordpress.org?param1=a&param2=b#hash'
			)
		).toBe(
			'^http:\\/\\/wordpress\\.org(?=.*[?|&]param1=a(&|$|#))(?=.*[?|&]param2=b(&|$|#))(.*#hash$)'
		);
	} );
} );

describe( 'getMatchScore', () => {
	beforeAll( () => {
		delete window.location;
		window.location = new URL( getAdminLink( '/' ) );
	} );

	afterAll( () => {
		window.location = originalLocation;
	} );

	it( 'should return max safe integer if the url is an exact match', () => {
		expect(
			getMatchScore(
				new URL( getAdminLink( 'admin.php?page=testpage' ) ),
				getAdminLink( 'admin.php?page=testpage' )
			)
		).toBe( Number.MAX_SAFE_INTEGER );
	} );

	it( 'should return matching path and parameter count', () => {
		expect(
			getMatchScore(
				new URL(
					getFullUrl(
						'/wp-admin/admin.php?page=testpage&extra_param=a'
					)
				),
				'/wp-admin/admin.php?page=testpage'
			)
		).toBe( 2 );
	} );

	it( 'should return 0 if the URL does not meet match criteria', () => {
		expect(
			getMatchScore(
				new URL( getAdminLink( 'admin.php?page=different-page' ) ),
				getAdminLink( 'admin.php?page=testpage' )
			)
		).toBe( 0 );
	} );

	it( 'should return match count for a custom match expression', () => {
		expect(
			getMatchScore(
				new URL(
					getAdminLink( 'admin.php?page=different-page&param1=a' )
				),
				getAdminLink( 'admin.php?page=testpage' ),
				'param1=a'
			)
		).toBe( 1 );
	} );

	it( 'should return 0 for custom match expression that does not match', () => {
		expect(
			getMatchScore(
				new URL(
					getAdminLink( 'admin.php?page=different-page&param1=b' )
				),
				getAdminLink( 'admin.php?page=testpage' ),
				'param1=a'
			)
		).toBe( 0 );
	} );

	it( 'should return match count if params match but are out of order', () => {
		expect(
			getMatchScore(
				new URL( getAdminLink( 'admin.php?param1=a&page=testpage' ) ),
				getAdminLink( 'admin.php?page=testpage' )
			)
		).toBe( 2 );
	} );

	it( 'should return match count if multiple params match but are out of order', () => {
		expect(
			getMatchScore(
				new URL(
					getAdminLink( 'admin.php?param1=a&page=testpage&param2=b' )
				),
				getAdminLink( 'admin.php?page=testpage&param1=a' )
			)
		).toBe( 3 );
	} );
} );

describe( 'getFullUrl', () => {
	beforeAll( () => {
		delete window.location;
		window.location = new URL( getAdminLink( '/' ) );
	} );

	afterAll( () => {
		window.location = originalLocation;
	} );

	it( 'should get the full admin URL from a path', () => {
		expect( getFullUrl( 'admin.php?page=testpage' ) ).toBe(
			getAdminLink( 'admin.php?page=testpage' )
		);
	} );

	it( 'should return the same URL from an already complete URL', () => {
		expect( getFullUrl( getAdminLink( 'admin.php?page=testpage' ) ) ).toBe(
			getAdminLink( 'admin.php?page=testpage' )
		);
	} );
} );

describe( 'sortMenuItems', () => {
	it( 'should return an array of items sorted by the order property', () => {
		const menuItems = [
			{ id: 'second', title: 'second', order: 2 },
			{ id: 'first', title: 'three', order: 1 },
			{ id: 'third', title: 'four', order: 3 },
		];

		const sortedItems = sortMenuItems( menuItems );

		expect( sortedItems[ 0 ].id ).toBe( 'first' );
		expect( sortedItems[ 1 ].id ).toBe( 'second' );
		expect( sortedItems[ 2 ].id ).toBe( 'third' );
	} );

	it( 'should sort items alphabetically if order is the same', () => {
		const menuItems = [
			{ id: 'third', title: 'z', order: 2 },
			{ id: 'first', title: 'first', order: 1 },
			{ id: 'second', title: 'a', order: 2 },
		];

		const sortedItems = sortMenuItems( menuItems );

		expect( sortedItems[ 0 ].id ).toBe( 'first' );
		expect( sortedItems[ 1 ].id ).toBe( 'second' );
		expect( sortedItems[ 2 ].id ).toBe( 'third' );
	} );
} );

describe( 'getMappedItemsCategories', () => {
	it( 'should get the default category when none are provided', () => {
		const menuItems = [
			{
				id: 'child-one',
				title: 'child-one',
				isCategory: false,
				parent: 'woocommerce',
				menuId: 'plugins',
			},
		];
		const { categories, items } = getMappedItemsCategories( menuItems );

		expect( items.woocommerce ).toBeDefined();
		expect( items.woocommerce.plugins ).toBeDefined();
		expect( items.woocommerce.plugins.length ).toBe( 1 );

		expect( Object.keys( categories ).length ).toBe( 1 );
		expect( categories.woocommerce ).toBeDefined();
	} );

	it( 'should get a map of all items and categories', () => {
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
		const { categories, items } = getMappedItemsCategories( menuItems );

		expect( items.woocommerce ).toBeDefined();
		expect( items.woocommerce.plugins ).toBeDefined();
		expect( items.woocommerce.plugins.length ).toBe( 1 );

		expect( items.parent ).toBeDefined();
		expect( items.parent.plugins ).toBeDefined();
		expect( items.parent.plugins.length ).toBe( 2 );

		expect( Object.keys( categories ).length ).toBe( 2 );
		expect( categories.parent ).toBeDefined();
		expect( categories.woocommerce ).toBeDefined();
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
		const { categories, items } = getMappedItemsCategories( menuItems );

		expect( items[ 'grand-parent' ] ).toBeDefined();
		expect( items[ 'grand-parent' ] ).toBeDefined();
		expect( items[ 'grand-parent' ].plugins.length ).toBe( 1 );

		expect( items.child ).toBeDefined();
		expect( items.child.plugins.length ).toBe( 1 );

		expect( items[ 'grand-child' ] ).not.toBeDefined();

		expect( Object.keys( categories ).length ).toBe( 3 );
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
		const { items } = getMappedItemsCategories( menuItems );

		expect( items.parent ).toBeDefined();
		expect( items.parent.primary ).toBeDefined();
		expect( items.parent.primary.length ).toBe( 2 );
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
		const { items } = getMappedItemsCategories( menuItems );

		expect( items.parent ).toBeDefined();
		expect( items.parent.plugins ).toBeDefined();
		expect( items.parent.plugins.length ).toBe( 2 );

		expect( items.primary ).not.toBeDefined();
	} );

	it( 'should ignore bad menu IDs', () => {
		const menuItems = [
			{
				id: 'parent',
				title: 'parent',
				isCategory: false,
				parent: 'woocommerce',
				menuId: 'badId',
			},
			{
				id: 'primary-one',
				title: 'primary-one',
				isCategory: false,
				parent: 'woocommerce',
				menuId: 'primary',
			},
			{
				id: 'primary-two',
				title: 'primary-two',
				isCategory: false,
				parent: 'woocommerce',
				menuId: 'primary',
			},
		];
		const { categories, items } = getMappedItemsCategories( menuItems );

		expect( items.woocommerce ).toBeDefined();
		expect( items.woocommerce.primary ).toBeDefined();
		expect( items.woocommerce.primary.length ).toBe( 2 );

		expect( items.woocommerce ).toBeDefined();
		expect( items.woocommerce.badId ).not.toBeDefined();

		expect( Object.keys( categories ).length ).toBe( 1 );
	} );
} );
