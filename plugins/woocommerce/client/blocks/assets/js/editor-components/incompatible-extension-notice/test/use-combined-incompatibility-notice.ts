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

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
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
import { DISMISSED_INCOMPATIBLE_EXTENSIONS_STORAGE_KEY } from '../storage';

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

const STORAGE_KEY = DISMISSED_INCOMPATIBLE_EXTENSIONS_STORAGE_KEY;

describe( 'useCombinedIncompatibilityNotice', () => {
	beforeEach( () => {
		window.localStorage.clear();
		mockIncompatiblePaymentMethods = {};
		mockIncompatibleExtensions = [];
		mockPaymentMethodsLoaded = true;
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

		expect(
			JSON.parse( window.localStorage.getItem( STORAGE_KEY ) || '[]' )
		).toEqual( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
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
		expect(
			JSON.parse( window.localStorage.getItem( STORAGE_KEY ) || '[]' )
		).toEqual( [ { [ CHECKOUT ]: [ 'gw_a' ] } ] );
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
		expect(
			JSON.parse( window.localStorage.getItem( STORAGE_KEY ) || '[]' )
		).toEqual( [ { [ CHECKOUT ]: [ 'gw_a', 'gw_b' ] } ] );

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

	// This key predates the storefront banner having its own, so a real site's
	// value can still hold shapes this hook never wrote: bare slug strings left
	// by the storefront, and more than one entry for the same block.
	describe( 'values left in the shared key by earlier versions', () => {
		const seed = ( value: unknown ) =>
			window.localStorage.setItem( STORAGE_KEY, JSON.stringify( value ) );

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

			expect(
				JSON.parse( window.localStorage.getItem( STORAGE_KEY ) || '[]' )
			).toEqual( [ { [ CHECKOUT ]: [ 'gw_a', 'gw_b', 'gw_c' ] } ] );
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

			expect(
				JSON.parse( window.localStorage.getItem( STORAGE_KEY ) || '[]' )
			).toEqual( [ 'gw_a', { [ CHECKOUT ]: [ 'gw_a' ] } ] );
		} );
	} );
} );
