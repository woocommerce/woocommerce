/**
 * The storage contract both incompatibility notices share.
 *
 * Tested directly rather than only through the two notices, because the notices
 * prune their stored value on mount and that prune hides what these helpers
 * decide: by the time a rendered notice is asserted on, the stored set and the
 * incompatible set have been made equal, so a comparison that demanded equality
 * instead of containment would look correct there. Containment is the #42469
 * fix, so it is pinned here where nothing has run before it.
 */

// Two sites of one subdirectory multisite. Same origin, so they share the
// browser's localStorage; different blog IDs, so they must not share a key.
const SITE_A = 1;
const SITE_B = 2;

let mockSiteId = SITE_A;
let mockIsMultisite = false;
// Exposed only so the tests below can prove the keys ignore it.
let mockHomeUrl = 'http://example.com/';

jest.mock( '@woocommerce/settings', () => ( {
	// Getters, not values: the site under test changes between calls.
	get CURRENT_SITE_ID() {
		return mockSiteId;
	},
	get IS_MULTISITE() {
		return mockIsMultisite;
	},
	get HOME_URL() {
		return mockHomeUrl;
	},
} ) );

/**
 * Internal dependencies
 */
import {
	getEditorStorageKey,
	getFrontendStorageKey,
	isSubsetOf,
	readDismissalsFromBeforeScoping,
	readInitialDismissals,
	UNSCOPED_STORAGE_KEY,
} from '../storage';

describe( 'incompatible extension notice storage', () => {
	beforeEach( () => {
		window.localStorage.clear();
		mockSiteId = SITE_A;
		mockIsMultisite = false;
		mockHomeUrl = 'http://example.com/';
	} );

	describe( 'storage keys', () => {
		it( 'gives the two surfaces different keys', () => {
			expect( getEditorStorageKey() ).not.toBe( getFrontendStorageKey() );
		} );

		it( 'keeps both keys off the value earlier versions wrote', () => {
			expect( getEditorStorageKey() ).not.toBe( UNSCOPED_STORAGE_KEY );
			expect( getFrontendStorageKey() ).not.toBe( UNSCOPED_STORAGE_KEY );
		} );

		it( 'scopes both keys to the site', () => {
			const editorOnA = getEditorStorageKey();
			const frontendOnA = getFrontendStorageKey();

			mockSiteId = SITE_B;

			expect( getEditorStorageKey() ).not.toBe( editorOnA );
			expect( getFrontendStorageKey() ).not.toBe( frontendOnA );
		} );

		// The home URL is not an input, so the per-request variation it carries
		// (here a language directory) cannot orphan a dismissal. Only
		// same-origin variation matters: an actual move to another origin gets
		// its own localStorage whatever the key is called.
		it( 'keeps the same keys when the home URL varies on one origin', () => {
			const editorBefore = getEditorStorageKey();
			const frontendBefore = getFrontendStorageKey();

			mockHomeUrl = 'http://example.com/fr/';

			expect( getEditorStorageKey() ).toBe( editorBefore );
			expect( getFrontendStorageKey() ).toBe( frontendBefore );
		} );

		it( 'builds both keys from the blog ID', () => {
			expect( getEditorStorageKey() ).toBe(
				`${ UNSCOPED_STORAGE_KEY }__${ SITE_A }`
			);
			expect( getFrontendStorageKey() ).toBe(
				`${ UNSCOPED_STORAGE_KEY }_frontend__${ SITE_A }`
			);
		} );
	} );

	describe( 'readDismissalsFromBeforeScoping', () => {
		it( 'returns the stored list', () => {
			window.localStorage.setItem(
				UNSCOPED_STORAGE_KEY,
				JSON.stringify( [
					'ext-one',
					{ 'woocommerce/cart': [ 'gw' ] },
				] )
			);

			expect( readDismissalsFromBeforeScoping() ).toEqual( [
				'ext-one',
				{ 'woocommerce/cart': [ 'gw' ] },
			] );
		} );

		it( 'returns nothing, and stays quiet, when the key is absent', () => {
			expect( readDismissalsFromBeforeScoping() ).toEqual( [] );
		} );

		it.each( [
			[ 'unparseable', 'not json at all' ],
			[ 'an object', JSON.stringify( { a: 1 } ) ],
			[ 'a bare string', JSON.stringify( 'ext-one' ) ],
			[ 'null', JSON.stringify( null ) ],
		] )( 'discards a value that is %s, and says so', ( _label, stored ) => {
			window.localStorage.setItem( UNSCOPED_STORAGE_KEY, stored );

			expect( readDismissalsFromBeforeScoping() ).toEqual( [] );
			expect( console ).toHaveErrored();
		} );

		// The value names no site and every site on the origin sees it, so on a
		// multisite there is no telling whose dismissal it is.
		it( 'claims nothing on a multisite', () => {
			mockIsMultisite = true;
			window.localStorage.setItem(
				UNSCOPED_STORAGE_KEY,
				JSON.stringify( [ 'ext-one' ] )
			);

			expect( readDismissalsFromBeforeScoping() ).toEqual( [] );
		} );

		it( 'does not even read the key on a multisite', () => {
			mockIsMultisite = true;
			const getItem = jest.spyOn( Storage.prototype, 'getItem' );

			readDismissalsFromBeforeScoping();

			expect( getItem ).not.toHaveBeenCalled();
			getItem.mockRestore();
		} );
	} );

	describe( 'readInitialDismissals', () => {
		const KEY = 'some-key';
		const migrate = () => [ 'migrated' ];

		it( 'migrates when the key has never been written', () => {
			expect( readInitialDismissals( KEY, migrate ) ).toEqual( [
				'migrated',
			] );
		} );

		// `useLocalStorageState` falls back to the initial value both when the
		// key is missing and when it holds something it cannot parse, and cannot
		// tell the two apart. Only absence may open the migration: seeding a
		// corrupt value from the pre-scoping key would revive a dismissal the
		// merchant has since replaced and hide a warning that is owed now.
		it.each( [
			[ 'a readable value', JSON.stringify( [ 'stored' ] ) ],
			[ 'an empty array', JSON.stringify( [] ) ],
			[ 'something unparseable', '{not valid json' ],
			// The case the `=== null` check exists for: an empty string is a
			// write that failed, which is stored data, not an absent key.
			[ 'an empty string', '' ],
		] )( 'starts empty when the key already holds %s', ( _l, stored ) => {
			window.localStorage.setItem( KEY, stored );
			const migrateSpy = jest.fn( migrate );

			expect( readInitialDismissals( KEY, migrateSpy ) ).toEqual( [] );
			expect( migrateSpy ).not.toHaveBeenCalled();
		} );

		// Private browsing and blocked cookies can make storage throw outright.
		it( 'starts empty when storage cannot be read at all', () => {
			const getItem = jest
				.spyOn( Storage.prototype, 'getItem' )
				.mockImplementation( () => {
					throw new Error( 'denied' );
				} );
			const migrateSpy = jest.fn( migrate );

			expect( readInitialDismissals( KEY, migrateSpy ) ).toEqual( [] );
			expect( migrateSpy ).not.toHaveBeenCalled();
			getItem.mockRestore();
		} );
	} );

	// Containment, not equality. The notices stay dismissed while everything
	// currently incompatible has been acknowledged, and an acknowledgement that
	// covers more than that — because an extension was deactivated since — still
	// counts. Demanding equality is exactly the #42469 bug.
	describe( 'isSubsetOf', () => {
		it.each( [
			{
				when: 'nothing is incompatible',
				sub: [],
				sup: [ 'a', 'b' ],
				is: true,
			},
			{
				when: 'both sides match',
				sub: [ 'a', 'b' ],
				sup: [ 'a', 'b' ],
				is: true,
			},
			{
				when: 'the order differs',
				sub: [ 'a', 'b' ],
				sup: [ 'b', 'a' ],
				is: true,
			},
			// One of two acknowledged extensions was deactivated: #42469.
			{
				when: 'more was acknowledged than is incompatible',
				sub: [ 'a' ],
				sup: [ 'a', 'b' ],
				is: true,
			},
			{
				when: 'nothing was acknowledged',
				sub: [ 'a' ],
				sup: [],
				is: false,
			},
			{
				when: 'one incompatible item is new',
				sub: [ 'a', 'b' ],
				sup: [ 'a' ],
				is: false,
			},
			{
				when: 'the two sets are disjoint',
				sub: [ 'a' ],
				sup: [ 'b' ],
				is: false,
			},
			{ when: 'neither side has anything', sub: [], sup: [], is: true },
		] )( 'is $is when $when', ( { sub, sup, is } ) => {
			expect( isSubsetOf( sub, sup ) ).toBe( is );
		} );

		it( 'does not consider the two sides interchangeable', () => {
			expect( isSubsetOf( [ 'a' ], [ 'a', 'b' ] ) ).toBe( true );
			expect( isSubsetOf( [ 'a', 'b' ], [ 'a' ] ) ).toBe( false );
		} );
	} );
} );
