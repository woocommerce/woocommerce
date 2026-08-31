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
// `undefined` stands for the setting never reaching the payload, which is not
// the same thing as an empty list: the Cart and Checkout blocks register it
// themselves, so a `woocommerce_shared_settings` callback that trims the
// settings drops it and `getSetting` falls back to what the caller passed.
let mockIncompatibleExtensions:
	| Array< { id: string; title: string } >
	| undefined = [];

const SITE_A = 1;
const SITE_B = 2;

let mockSiteId = SITE_A;
let mockIsMultisite = false;

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	get CURRENT_SITE_ID() {
		return mockSiteId;
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

const storageKey = () => getEditorStorageKey();

const storedNotices = ( key = storageKey() ) =>
	JSON.parse( window.localStorage.getItem( key ) || '[]' );

describe( 'useCombinedIncompatibilityNotice', () => {
	beforeEach( () => {
		window.localStorage.clear();
		mockIncompatiblePaymentMethods = {};
		mockIncompatibleExtensions = [];
		mockPaymentMethodsLoaded = true;
		mockSiteId = SITE_A;
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

	// The notice stays dismissed while everything currently incompatible has been
	// acknowledged, even when the acknowledgement covers more than that. Asserted
	// with the payment store still loading, because once it has loaded the prune
	// makes the two sets equal and a comparison demanding equality would pass
	// here too — which is the #42469 bug.
	it( 'stays dismissed off a wider acknowledgement while the store loads', () => {
		mockIncompatibleExtensions = [ { id: 'ext_x', title: 'Ext X' } ];
		mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
		mountAndDismiss( CHECKOUT );
		expect( storedNotices() ).toEqual( [
			{ [ CHECKOUT ]: [ 'ext_x', 'gw_a' ] },
		] );

		// Reload with the store still loading: it reports no gateways, so only
		// the extension is incompatible, and the acknowledgement covers it and
		// one thing more.
		mockPaymentMethodsLoaded = false;

		expect( mountVisibility( CHECKOUT ) ).toBe( false );
		// Held back, so nothing was pruned on the strength of an empty set.
		expect( storedNotices() ).toEqual( [
			{ [ CHECKOUT ]: [ 'ext_x', 'gw_a' ] },
		] );
	} );

	// Pruning turns "this is no longer incompatible" into a write that drops the
	// acknowledgement, so every reason the incompatible set can read as empty has
	// to be told apart from it genuinely being empty. The payment store has its
	// own guard; the extensions half needs the same one.
	describe( 'when the list of incompatible extensions is not available', () => {
		it( 'leaves the acknowledgement alone when the setting is missing', () => {
			mockIncompatibleExtensions = [ { id: 'ext_x', title: 'Ext X' } ];
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
			mountAndDismiss( CHECKOUT );

			mockIncompatibleExtensions = undefined;
			mountVisibility( CHECKOUT );

			expect( storedNotices() ).toEqual( [
				{ [ CHECKOUT ]: [ 'ext_x', 'gw_a' ] },
			] );
		} );

		it( 'still hides the notice on the next load with the setting back', () => {
			mockIncompatibleExtensions = [ { id: 'ext_x', title: 'Ext X' } ];
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
			mountAndDismiss( CHECKOUT );

			mockIncompatibleExtensions = undefined;
			mountVisibility( CHECKOUT );

			mockIncompatibleExtensions = [ { id: 'ext_x', title: 'Ext X' } ];
			expect( mountVisibility( CHECKOUT ) ).toBe( false );
		} );

		// The control: with the list delivered and the extension really gone,
		// the lapsed acknowledgement is dropped as it should be.
		it( 'does drop a lapsed acknowledgement once the list arrives', () => {
			mockIncompatibleExtensions = [ { id: 'ext_x', title: 'Ext X' } ];
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
			mountAndDismiss( CHECKOUT );

			mockIncompatibleExtensions = [];
			mountVisibility( CHECKOUT );

			expect( storedNotices() ).toEqual( [
				{ [ CHECKOUT ]: [ 'gw_a' ] },
			] );
		} );
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

	// Sites on a subdirectory multisite share one localStorage origin, so the
	// editor hook must use the current site's key rather than another site's
	// acknowledgement.
	describe( 'site scoping', () => {
		it( 'does not carry a dismissal to another site on the same origin', () => {
			mockIsMultisite = true;
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
			mountAndDismiss( CHECKOUT );
			expect( mountVisibility( CHECKOUT ) ).toBe( false );

			mockSiteId = SITE_B;

			expect( mountVisibility( CHECKOUT ) ).toBe( true );
		} );
	} );

	// The dismissals merchants already made live under the unscoped key, so
	// without a migration every one of them would see the notice one more time.
	// The storage contract's full matrix is pinned in test/storage.ts; these
	// cases pin the editor hook's wiring to it.
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

		// Only an absent scoped key may open migration. Otherwise a corrupt value
		// would revive a dismissal the merchant has since replaced.
		it( 'does not migrate over a scoped value it cannot parse', () => {
			window.localStorage.setItem( storageKey(), '{not valid json' );
			seedUnscoped( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
			mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };

			expect( mountVisibility( CHECKOUT ) ).toBe( true );
			expect( console ).toHaveErrored();
		} );
	} );
} );
