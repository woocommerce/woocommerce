/**
 * External dependencies
 */
import * as wpData from '@wordpress/data';
import { renderHook, act } from '@testing-library/react';

// Mock @wordpress/data before importing the hook under test.
jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

// Incompatible payment gateways returned by the payment store, controllable per render.
let mockIncompatiblePaymentMethods: Record< string, string > = {};

// Whether the payment store has finished loading. Until it has, it reports an
// empty incompatible set, which the hook must not read as "nothing is
// incompatible any more".
let mockPaymentMethodsLoaded = true;

// Incompatible extensions returned from settings, controllable per render.
let mockIncompatibleExtensions: Array< { id: string; title: string } > = [];

// Two sites of one subdirectory multisite. Same origin, so they share the
// browser's localStorage; different home URL, so they must not share a key.
const SITE_A = 'https://example.com/';
const SITE_B = 'https://example.com/site-b/';

let mockHomeUrl = SITE_A;
let mockIsMultisite = false;

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	// Getters, not values: the site under test changes between renders.
	get HOME_URL() {
		return mockHomeUrl;
	},
	get IS_MULTISITE() {
		return mockIsMultisite;
	},
	getSetting: jest.fn().mockImplementation( ( name: string, ...rest ) => {
		if ( name === 'incompatibleExtensions' ) {
			return mockIncompatibleExtensions;
		}
		return jest
			.requireActual( '@woocommerce/settings' )
			.getSetting( name, ...rest );
	} ),
} ) );

/**
 * Internal dependencies
 */
import { useCombinedIncompatibilityNotice } from '../use-combined-incompatibility-notice';
import { getEditorStorageKey, UNSCOPED_STORAGE_KEY } from '../storage';

( wpData.useSelect as jest.Mock ).mockImplementation( ( mapSelect ) =>
	mapSelect( () => ( {
		getIncompatiblePaymentMethods: () =>
			mockPaymentMethodsLoaded ? mockIncompatiblePaymentMethods : {},
		paymentMethodsInitialized: () => mockPaymentMethodsLoaded,
		expressPaymentMethodsInitialized: () => mockPaymentMethodsLoaded,
	} ) )
);

const CHECKOUT = 'woocommerce/checkout';
const CART = 'woocommerce/cart';

// Returns whether the notice is visible for the given block on a fresh mount
// (fresh mount == a page reload, since state is seeded from localStorage).
const mountVisibility = ( block: string ) => {
	const { result, unmount } = renderHook( () =>
		useCombinedIncompatibilityNotice( block )
	);
	const isVisible = result.current[ 0 ];
	unmount();
	return isVisible;
};

// Dismisses the notice for the given block on a fresh mount, then unmounts.
const mountAndDismiss = ( block: string ) => {
	const { result, unmount } = renderHook( () =>
		useCombinedIncompatibilityNotice( block )
	);
	act( () => {
		result.current[ 1 ]();
	} );
	unmount();
};

// A function, not a constant: the key carries the site, which tests change.
const storageKey = () => getEditorStorageKey();

const storedNotices = ( key = storageKey() ) =>
	JSON.parse( window.localStorage.getItem( key ) || '[]' );

describe( 'useCombinedIncompatibilityNotice', () => {
	beforeEach( () => {
		window.localStorage.clear();
		mockIncompatiblePaymentMethods = {};
		mockIncompatibleExtensions = [];
		mockPaymentMethodsLoaded = true;
		mockHomeUrl = SITE_A;
		mockIsMultisite = false;
	} );

	it( 'shows the notice when there is an incompatible gateway', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};

		expect( mountVisibility( CHECKOUT ) ).toBe( true );
	} );

	it( 'hides the notice when there are no incompatibilities', () => {
		expect( mountVisibility( CHECKOUT ) ).toBe( false );
	} );

	it( 'keeps the notice hidden after the merchant dismisses it', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};

		const { result, rerender } = renderHook( () =>
			useCombinedIncompatibilityNotice( CHECKOUT )
		);
		expect( result.current[ 0 ] ).toBe( true );

		act( () => {
			result.current[ 1 ]();
		} );
		rerender();

		expect( result.current[ 0 ] ).toBe( false );
	} );

	// Core of issue #42469.
	it( 'stays dismissed when an incompatible gateway is disabled', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};
		mountAndDismiss( CHECKOUT );

		// Reload after disabling gateway B — only A remains incompatible.
		mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

		expect( mountVisibility( CHECKOUT ) ).toBe( false );
	} );

	it( 'shows the notice again when a new incompatible gateway appears', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};
		mountAndDismiss( CHECKOUT );

		// A brand-new, never-acknowledged incompatible gateway appears.
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
			gw_c: 'Gateway C',
		};

		expect( mountVisibility( CHECKOUT ) ).toBe( true );
	} );

	// An acknowledgement lasts only while the gateway stays incompatible: the
	// merchant accepted it being unusable at checkout *then*, which says nothing
	// about turning it back on later.
	it( 'warns again when an acknowledged gateway is disabled and re-enabled', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};
		mountAndDismiss( CHECKOUT );

		// Disable B. The notice stays hidden for the still-incompatible A,
		// which is the #42469 fix.
		mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
		expect( mountVisibility( CHECKOUT ) ).toBe( false );

		// Re-enable B. It stopped being incompatible in between, so it counts
		// as a fresh incompatibility and warns.
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};

		expect( mountVisibility( CHECKOUT ) ).toBe( true );
	} );

	it( 'drops the acknowledgement of a disabled gateway from storage', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};
		mountAndDismiss( CHECKOUT );

		mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
		mountVisibility( CHECKOUT );

		expect( storedNotices() ).toEqual( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
	} );

	// Pruning must only ever remove slugs. If it wrote the current set wholesale
	// it would silently acknowledge the gateway the merchant is being warned about.
	it( 'does not acknowledge a new gateway while the notice is on screen', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};
		mountAndDismiss( CHECKOUT );

		// B goes away and a brand-new C arrives, so the notice is showing for C.
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_c: 'Gateway C',
		};
		expect( mountVisibility( CHECKOUT ) ).toBe( true );

		// B was pruned, but C was not silently accepted, so it still warns.
		expect( storedNotices() ).toEqual( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
		expect( mountVisibility( CHECKOUT ) ).toBe( true );
	} );

	// The payment store reports an empty set until it has loaded. Pruning on
	// that would wipe the acknowledgement on every single editor load.
	it( 'keeps acknowledgements while the payment store is still loading', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};
		mountAndDismiss( CHECKOUT );

		mockPaymentMethodsLoaded = false;
		expect( mountVisibility( CHECKOUT ) ).toBe( false );
		expect( storedNotices() ).toEqual( [
			{ [ CHECKOUT ]: [ 'gw_a', 'gw_b' ] },
		] );

		// Once it loads, both are still incompatible and still acknowledged.
		mockPaymentMethodsLoaded = true;
		expect( mountVisibility( CHECKOUT ) ).toBe( false );
	} );

	it( 'tracks dismissal independently per block', () => {
		mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
		mountAndDismiss( CHECKOUT );

		// The cart block has its own, still-undismissed notice.
		expect( mountVisibility( CART ) ).toBe( true );
	} );

	// Before the storefront banner had its own key, and before either key was
	// scoped to a site, both surfaces wrote into one. A migrated value can
	// therefore still hold shapes this hook never wrote: bare slug strings left
	// by the storefront, and more than one entry for the same block.
	describe( 'values left by earlier versions', () => {
		const seed = ( value: unknown ) =>
			window.localStorage.setItem(
				storageKey(),
				JSON.stringify( value )
			);

		it( 'reads acknowledgements split across several entries for one block', () => {
			seed( [
				{ [ CHECKOUT ]: [ 'gw_a' ] },
				{ [ CHECKOUT ]: [ 'gw_b' ] },
			] );
			mockIncompatiblePaymentMethods = {
				gw_a: 'Gateway A',
				gw_b: 'Gateway B',
			};

			expect( mountVisibility( CHECKOUT ) ).toBe( false );
		} );

		it( 'consolidates those entries into one on the next dismissal', () => {
			seed( [
				{ [ CHECKOUT ]: [ 'gw_a' ] },
				{ [ CHECKOUT ]: [ 'gw_b' ] },
			] );
			mockIncompatiblePaymentMethods = {
				gw_a: 'Gateway A',
				gw_b: 'Gateway B',
				gw_c: 'Gateway C',
			};

			mountAndDismiss( CHECKOUT );

			expect( storedNotices() ).toEqual( [
				{ [ CHECKOUT ]: [ 'gw_a', 'gw_b', 'gw_c' ] },
			] );
		} );

		it( 'ignores bare slug strings the storefront banner left behind', () => {
			seed( [ 'gw_a' ] );
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

			// The string is the storefront's acknowledgement, not this block's,
			// so the editor notice is still owed to the merchant.
			expect( mountVisibility( CHECKOUT ) ).toBe( true );
		} );

		it.each( [
			[ 'an object', { [ CHECKOUT ]: [ 'gw_a' ] } ],
			[ 'a bare string', 'gw_a' ],
			[ 'a number', 7 ],
			[ 'null', null ],
			[ 'an array holding junk', [ null, 7, { [ CHECKOUT ]: 'gw_a' } ] ],
		] )( 'survives a stored value that is %s', ( _label, value ) => {
			seed( value );
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

			// Nothing readable was acknowledged, so the notice is still owed,
			// and reading it must not throw.
			expect( mountVisibility( CHECKOUT ) ).toBe( true );
		} );

		it( 'preserves those strings when writing its own dismissal', () => {
			seed( [ 'gw_a' ] );
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

			mountAndDismiss( CHECKOUT );

			expect( storedNotices() ).toEqual( [
				'gw_a',
				{ [ CHECKOUT ]: [ 'gw_a' ] },
			] );
		} );
	} );

	// localStorage is keyed by origin, not by path, so on a subdirectory
	// multisite every site sees the same storage. Without the site in the key, a
	// dismissal on one of them hides another one's live warning.
	describe( 'site scoping', () => {
		it( 'does not carry a dismissal to another site on the same origin', () => {
			mockIncompatiblePaymentMethods = {
				gw_a: 'Gateway A',
				gw_b: 'Gateway B',
			};
			mountAndDismiss( CHECKOUT );
			expect( mountVisibility( CHECKOUT ) ).toBe( false );

			// Same browser, same origin, different site of the network.
			mockHomeUrl = SITE_B;

			expect( mountVisibility( CHECKOUT ) ).toBe( true );
		} );

		// The reviewed revision matched a subset, so site B's single
		// incompatibility counted as covered by site A's larger acknowledgement.
		it( 'does not let a larger acknowledgement elsewhere cover this site', () => {
			mockIncompatiblePaymentMethods = {
				gw_a: 'Gateway A',
				gw_b: 'Gateway B',
			};
			mountAndDismiss( CHECKOUT );

			mockHomeUrl = SITE_B;
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

			expect( mountVisibility( CHECKOUT ) ).toBe( true );
		} );

		it( "keeps each site's acknowledgements in its own key", () => {
			const siteAKey = storageKey();
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
			mountAndDismiss( CHECKOUT );

			mockHomeUrl = SITE_B;
			mockIncompatiblePaymentMethods = { gw_b: 'Gateway B' };
			mountAndDismiss( CHECKOUT );

			expect( storageKey() ).not.toBe( siteAKey );
			expect( storedNotices( siteAKey ) ).toEqual( [
				{ [ CHECKOUT ]: [ 'gw_a' ] },
			] );
			expect( storedNotices() ).toEqual( [
				{ [ CHECKOUT ]: [ 'gw_b' ] },
			] );
		} );
	} );

	// The dismissals merchants already made live under the unscoped key, so
	// without a migration every one of them would see the notice one more time.
	describe( 'migration from before site scoping', () => {
		const seedUnscoped = ( value: unknown ) =>
			window.localStorage.setItem(
				UNSCOPED_STORAGE_KEY,
				JSON.stringify( value )
			);

		it( 'stays dismissed for a merchant who dismissed before scoping', () => {
			seedUnscoped( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

			expect( mountVisibility( CHECKOUT ) ).toBe( false );
		} );

		// Leaving the old value intact keeps a revert of this change harmless.
		it( 'never writes to the unscoped key', () => {
			const before = JSON.stringify( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
			window.localStorage.setItem( UNSCOPED_STORAGE_KEY, before );
			mockIncompatiblePaymentMethods = {
				gw_a: 'Gateway A',
				gw_b: 'Gateway B',
			};

			mountAndDismiss( CHECKOUT );

			expect( window.localStorage.getItem( UNSCOPED_STORAGE_KEY ) ).toBe(
				before
			);
		} );

		// The unscoped value names no site and every site on the origin sees it,
		// so on a multisite there is no telling whose dismissal it is. Warning
		// once more beats inheriting a dismissal made on a different site.
		it( 'does not migrate on a multisite', () => {
			mockIsMultisite = true;
			seedUnscoped( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

			expect( mountVisibility( CHECKOUT ) ).toBe( true );
		} );

		it( 'ignores the unscoped value once this site has its own', () => {
			window.localStorage.setItem( storageKey(), JSON.stringify( [] ) );
			seedUnscoped( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

			expect( mountVisibility( CHECKOUT ) ).toBe( true );
		} );
	} );
} );
