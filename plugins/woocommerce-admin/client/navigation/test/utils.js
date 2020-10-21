/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import {
	addHistoryListener,
	getFullUrl,
	getMatchingItem,
	getMatchScore,
	getParams,
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
];

describe( 'getMatchingItem', () => {
	beforeAll( () => {
		delete window.location;
	} );

	afterAll( () => {
		window.location = originalLocation;
	} );

	it( 'should get the closest matched item', () => {
		window.location = new URL( getAdminLink( 'admin.php?page=wc-admin' ) );
		const matchingItem = getMatchingItem( sampleMenuItems );
		expect( matchingItem.id ).toBe( 'main' );
	} );

	it( 'should match the item without hash if a better match does not exist', () => {
		window.location = new URL(
			getAdminLink( 'admin.php?page=wc-admin#hash' )
		);
		const matchingItem = getMatchingItem( sampleMenuItems );
		expect( matchingItem.id ).toBe( 'main' );
	} );

	it( 'should exactly match the item with a hash if it exists', () => {
		window.location = new URL(
			getAdminLink( 'admin.php?page=wc-admin&path=/test-path#anchor' )
		);
		const matchingItem = getMatchingItem( sampleMenuItems );
		expect( matchingItem.id ).toBe( 'hash' );
	} );

	it( 'should roughly match the item with the highest number of matching arguments', () => {
		window.location = new URL(
			getAdminLink(
				'admin.php?page=wc-admin&path=/test-path&section=section-name'
			)
		);
		const matchingItem = getMatchingItem( sampleMenuItems );
		expect( matchingItem.id ).toBe( 'multiple-args' );
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

	it( 'should return the largest integer for an exact match', () => {
		expect(
			getMatchScore(
				new URL( getAdminLink( 'admin.php?page=testpage' ) ),
				getAdminLink( 'admin.php?page=testpage' )
			)
		).toBe( Number.MAX_SAFE_INTEGER );
	} );

	it( 'should return the largest integer for an exact match with a partial URL', () => {
		expect(
			getMatchScore(
				new URL( getFullUrl( '/wp-admin/admin.php?page=testpage' ) ),
				'/wp-admin/admin.php?page=testpage'
			)
		).toBe( Number.MAX_SAFE_INTEGER );
	} );

	it( 'should return the largest integer - 1 for an exact match without a hash', () => {
		expect(
			getMatchScore(
				new URL( getAdminLink( 'admin.php?page=testpage#hash' ) ),
				getAdminLink( 'admin.php?page=testpage' )
			)
		).toBe( Number.MAX_SAFE_INTEGER - 1 );
	} );

	it( 'should return a score equal to the number of arguments matched', () => {
		expect(
			getMatchScore(
				new URL(
					getAdminLink(
						'admin.php?page=testpage&param1=a&param2=b&param3=c'
					)
				),
				getAdminLink( 'admin.php?page=testpage&param1=a&param2=b' )
			)
		).toBe( 3 );
	} );

	it( "should return 0 for paths that don't match", () => {
		expect(
			getMatchScore(
				new URL(
					getAdminLink(
						'admin.php?page=testpage&param1=a&param2=b&param3=c'
					)
				),
				getAdminLink( 'plugins.php?page=testpage&param1=a&param2=b' )
			)
		).toBe( 0 );
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

describe( 'getParams', () => {
	it( 'should get the params from a location', () => {
		const location = new URL(
			getAdminLink( 'admin.php?page=testpage&param1=a' )
		);
		const params = getParams( location );
		expect( Object.keys( params ).length ).toBe( 2 );
		expect( params.page ).toBe( 'testpage' );
		expect( params.param1 ).toBe( 'a' );
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
