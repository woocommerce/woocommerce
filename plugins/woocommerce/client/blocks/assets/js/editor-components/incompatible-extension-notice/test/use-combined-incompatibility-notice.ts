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

		// Disable B, then dismiss again while only A is present.
		mockIncompatiblePaymentMethods = { gw_a: 'Gateway A' };
		expect( mountVisibility( CHECKOUT ) ).toBe( false );
		mountAndDismiss( CHECKOUT );

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
} );
