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

( wpData.useSelect as jest.Mock ).mockImplementation( ( mapSelect ) =>
	mapSelect( () => ( {
		getIncompatiblePaymentMethods: () => mockIncompatiblePaymentMethods,
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

describe( 'useCombinedIncompatibilityNotice', () => {
	beforeEach( () => {
		window.localStorage.clear();
		mockIncompatiblePaymentMethods = {};
		mockIncompatibleExtensions = [];
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

	// Re-enabling a previously-dismissed gateway must not re-trigger the notice,
	// even after it was disabled and the (now smaller) notice was dismissed again.
	it( 'keeps a re-enabled gateway dismissed once it has been acknowledged', () => {
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};
		mountAndDismiss( CHECKOUT );

		// Disable B. The notice stays hidden, so there is no dismissal to make
		// here — the merchant simply never sees it again.
		mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
		expect( mountVisibility( CHECKOUT ) ).toBe( false );

		// Re-enable B — it was already acknowledged, so the notice stays hidden.
		mockIncompatiblePaymentMethods = {
			gw_a: 'Gateway A',
			gw_b: 'Gateway B',
		};

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
		const STORAGE_KEY =
			'wc-blocks_dismissed_incompatible_extensions_notices';

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
