/**
 * The prune effect must keep converging when the incompatible set shrinks more
 * than once while the gate stays open.
 *
 * `__internalUpdateAvailablePaymentMethods` awaits `checkPaymentMethodsCanPay`
 * twice, and each call dispatches its own set action, so the express and
 * regular halves of the incompatible set can shrink in two separate renders
 * with both initialized flags already true.
 *
 * This suite deliberately runs outside React's act environment and drives a
 * real registered store: `act()` flushes the prune's own state update between
 * the two dispatches, which hides exactly the interleaving that once let a
 * dependency-keyed prune effect skip the second shrink.
 *
 * What this pins is a **boolean** dependency, `}, [ hasStaleAcknowledgements ] )`,
 * which reads true through both shrinks and so prunes only the first. The
 * serialised `}, [ prunedAcknowledgementKey ] )` form was already correct, and
 * is an equivalent mutant no test can separate from today's effect.
 *
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { createReduxStore, register, dispatch } from '@wordpress/data';
import { createRoot } from 'react-dom/client';

const STORE = 'test/prune-convergence-payment';

let mockIncompatibleExtensions: Array< { id: string; title: string } > = [];

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	get CURRENT_SITE_ID() {
		return 1;
	},
	get IS_MULTISITE() {
		return false;
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

// Point the hook's payment store at a real registered store so the genuine
// `useSelect` subscription drives re-renders.
jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	paymentStore: 'test/prune-convergence-payment',
} ) );

/**
 * Internal dependencies
 */
import { useCombinedIncompatibilityNotice } from '../use-combined-incompatibility-notice';
import { getEditorStorageKey } from '../storage';

type State = {
	express: Record< string, string >;
	regular: Record< string, string >;
	initialized: boolean;
};

const store = createReduxStore( STORE, {
	reducer: (
		state: State = { express: {}, regular: {}, initialized: false },
		action: { type: string; value?: Record< string, string > }
	): State => {
		switch ( action.type ) {
			case 'SET_EXPRESS':
				return { ...state, express: action.value ?? {} };
			case 'SET_REGULAR':
				return { ...state, regular: action.value ?? {} };
			case 'INITIALIZE':
				return { ...state, initialized: true };
			case 'DEINITIALIZE':
				return { ...state, initialized: false };
			default:
				return state;
		}
	},
	actions: {
		setExpress: ( value: Record< string, string > ) => ( {
			type: 'SET_EXPRESS',
			value,
		} ),
		setRegular: ( value: Record< string, string > ) => ( {
			type: 'SET_REGULAR',
			value,
		} ),
		initialize: () => ( { type: 'INITIALIZE' } ),
		deinitialize: () => ( { type: 'DEINITIALIZE' } ),
	},
	selectors: {
		// Memoised on the same state slices production's `createSelector` uses,
		// so `useSelect` sees a stable reference for unchanged state exactly as
		// it does against the real payment store.
		getIncompatiblePaymentMethods: ( () => {
			let lastExpress: unknown;
			let lastRegular: unknown;
			let lastResult: Record< string, string > = {};
			return ( state: State ) => {
				if (
					state.express !== lastExpress ||
					state.regular !== lastRegular
				) {
					lastExpress = state.express;
					lastRegular = state.regular;
					lastResult = { ...state.express, ...state.regular };
				}
				return lastResult;
			};
		} )(),
		paymentMethodsInitialized: ( state: State ) => state.initialized,
		expressPaymentMethodsInitialized: ( state: State ) => state.initialized,
	},
} );
register( store );

const CHECKOUT = 'woocommerce/checkout';

const Harness = () => {
	useCombinedIncompatibilityNotice( CHECKOUT );
	return null;
};

const acknowledgedSlugs = () =>
	JSON.parse(
		window.localStorage.getItem( getEditorStorageKey() ) || 'null'
	)?.[ 0 ]?.[ CHECKOUT ];

// Lets React's scheduler run its post-commit work, the way a real frame does.
// Several turns, not one: passive effects flush on a scheduler task, and the
// state update a prune makes needs a further render to reach `localStorage`.
const settle = async () => {
	for ( let turn = 0; turn < 5; turn++ ) {
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	}
};

describe( 'prune convergence across consecutive shrinks', () => {
	let container: HTMLDivElement;
	let root: ReturnType< typeof createRoot >;
	let wasActEnvironment: unknown;

	beforeEach( () => {
		wasActEnvironment = ( global as Record< string, unknown > )
			.IS_REACT_ACT_ENVIRONMENT;
		( global as Record< string, unknown > ).IS_REACT_ACT_ENVIRONMENT =
			false;

		window.localStorage.clear();
		mockIncompatibleExtensions = [ { id: 'ext_x', title: 'Ext X' } ];
		dispatch( STORE ).setExpress( { gw_express: 'Express Gateway' } );
		dispatch( STORE ).setRegular( { gw_regular: 'Regular Gateway' } );
		dispatch( STORE ).initialize();

		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
	} );

	afterEach( () => {
		root.unmount();
		container.remove();
		( global as Record< string, unknown > ).IS_REACT_ACT_ENVIRONMENT =
			wasActEnvironment;
	} );

	// The one prune scenario the act-based suite cannot represent; everything
	// else about pruning lives in test/use-combined-incompatibility-notice.ts.
	it( 'drops both halves when the express and regular sets shrink in separate renders', async () => {
		// The merchant acknowledged everything that was incompatible.
		window.localStorage.setItem(
			getEditorStorageKey(),
			JSON.stringify( [
				{ [ CHECKOUT ]: [ 'ext_x', 'gw_express', 'gw_regular' ] },
			] )
		);

		root.render( createElement( Harness ) );
		await settle();
		expect( acknowledgedSlugs() ).toEqual( [
			'ext_x',
			'gw_express',
			'gw_regular',
		] );

		// Mirrors __internalUpdateAvailablePaymentMethods: two awaited calls,
		// each dispatching its own set action, with the gate already open.
		await Promise.resolve();
		dispatch( STORE ).setExpress( {} );
		await Promise.resolve();
		dispatch( STORE ).setRegular( {} );
		await settle();

		// Both gateways stopped being incompatible, so neither acknowledgement
		// may survive: if either comes back it has to warn again.
		expect( acknowledgedSlugs() ).toEqual( [ 'ext_x' ] );
	} );
} );
