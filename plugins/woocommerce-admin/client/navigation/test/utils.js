/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import {
	addHistoryListener,
	getDefaultMatchExpression,
	getFullUrl,
	getMatchingItem,
	getMatchScore,
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
		url:
			'admin.php?page=wc-admin&path=/test-path&section=section-name&version=22',
	},
	{
		id: 'hash-and-multiple-args',
		title: 'Page with multiple arguments and a hash',
		url:
			'admin.php?page=wc-admin&path=/test-path&section=section-name#anchor',
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

	const adminUrl = new URL( getAdminLink( '/' ) );

	it( 'should get the full URL from a path', () => {
		expect( getFullUrl( '/wp-admin/admin.php?page=testpage' ) ).toBe(
			adminUrl.origin + '/wp-admin/admin.php?page=testpage'
		);
	} );

	it( 'should return the same URL from an already complete URL', () => {
		expect( getFullUrl( getAdminLink( 'admin.php?page=testpage' ) ) ).toBe(
			getAdminLink( 'admin.php?page=testpage' )
		);
	} );
} );

describe( 'addHistoryListener', () => {
	it( 'should add a custom event to the browser pushState', () => {
		const mockCallback = jest.fn();
		const removeListener = addHistoryListener( mockCallback );
		window.history.pushState( {}, 'Test pushState' );
		window.history.pushState( {}, 'Test pushState 2' );

		expect( mockCallback.mock.calls.length ).toBe( 2 );

		// Check that events are no longer called after removing the listener.
		removeListener();
		window.history.pushState( {}, 'Test pushState 3' );
		expect( mockCallback.mock.calls.length ).toBe( 2 );
	} );

	it( 'should add a custom event to the browser replaceState', () => {
		const mockCallback = jest.fn();
		const removeListener = addHistoryListener( mockCallback );
		window.history.replaceState( {}, 'Test replaceState' );
		window.history.replaceState( {}, 'Test replaceState 2' );

		expect( mockCallback.mock.calls.length ).toBe( 2 );

		// Check that events are no longer called after removing the listener.
		removeListener();
		window.history.replaceState( {}, 'Test replaceState 3' );
		expect( mockCallback.mock.calls.length ).toBe( 2 );
	} );
} );
