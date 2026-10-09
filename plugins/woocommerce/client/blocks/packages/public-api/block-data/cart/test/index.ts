/**
 * External dependencies
 */
import {
	createReduxStore,
	dispatch as wpDispatch,
	select as wpSelect,
	subscribe as wpSubscribe,
} from '@wordpress/data';
import type { Cart } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	hasCartSession,
	persistenceLayer,
	isAddingToCart,
} from '../persistence-layer';

// Mock all dependencies before importing the module that contains the event listener
jest.mock( '@wordpress/data' );
jest.mock( '@woocommerce/utils', () => ( {
	isSiteEditorPage: jest.fn().mockReturnValue( true ),
} ) );
jest.mock( '../persistence-layer' );

const mockHasCartSession = jest.mocked( hasCartSession );
const mockIsAddingToCart = jest.mocked( isAddingToCart );
const mockPersistenceLayerGet = jest.mocked( persistenceLayer.get );
const mockWpDispatch = jest.mocked( wpDispatch );
const mockWpSelect = jest.mocked( wpSelect );
const mockWpSubscribe = jest.mocked( wpSubscribe );
// Named stand-in stores, so the cart store's subscribers can be told apart from other stores' subscribers.
jest.mocked( createReduxStore ).mockImplementation(
	( name: string ) =>
		( { name } ) as unknown as ReturnType< typeof createReduxStore >
);

describe( 'Window load event handler', () => {
	let mockFinishResolution: jest.Mock;
	let originalAddEventListener: typeof window.addEventListener;
	let loadHandler: ( event: Event ) => void;

	beforeAll( () => {
		// Capture the addEventListener calls to extract the load handler
		originalAddEventListener = window.addEventListener;
		window.addEventListener = jest.fn(
			(
				event: string,
				handler: Parameters< typeof window.addEventListener >[ 1 ]
			) => {
				if ( event === 'load' && typeof handler === 'function' ) {
					loadHandler = handler;
				}
				return originalAddEventListener.call( window, event, handler );
			}
		);

		// Now import the module to register the event listener
		jest.requireActual( '../index' );
	} );

	beforeEach( () => {
		mockFinishResolution = jest.fn();
		mockWpDispatch.mockReturnValue( {
			finishResolution: mockFinishResolution,
		} as unknown as ReturnType< typeof wpDispatch > );
	} );

	afterAll( () => {
		window.addEventListener = originalAddEventListener;
	} );

	it( 'should skip API request when no cart session and not adding to cart with /?add-to-cart=', () => {
		mockHasCartSession.mockReturnValue( false );
		mockIsAddingToCart.mockReturnValue( false );
		mockPersistenceLayerGet.mockReturnValue( null );

		loadHandler( new Event( 'load' ) );

		expect( mockFinishResolution ).toHaveBeenCalledWith( 'getCartData' );
	} );

	it( 'should skip API request when cached cart has items and not adding to cart with /?add-to-cart=', () => {
		mockHasCartSession.mockReturnValue( true );
		mockIsAddingToCart.mockReturnValue( false );
		mockPersistenceLayerGet.mockReturnValue( {
			itemsCount: 2,
		} as unknown as Cart );

		loadHandler( new Event( 'load' ) );

		expect( mockFinishResolution ).toHaveBeenCalledWith( 'getCartData' );
	} );

	it( 'should make API request when has cart session but cached cart is empty', () => {
		mockHasCartSession.mockReturnValue( true );
		mockIsAddingToCart.mockReturnValue( false );
		mockPersistenceLayerGet.mockReturnValue( {
			itemsCount: 0,
		} as unknown as Cart );

		loadHandler( new Event( 'load' ) );

		expect( mockFinishResolution ).not.toHaveBeenCalled();
	} );

	it( 'should make API request when has cart session but cached cart is null', () => {
		mockHasCartSession.mockReturnValue( true );
		mockIsAddingToCart.mockReturnValue( false );
		mockPersistenceLayerGet.mockReturnValue( null );

		loadHandler( new Event( 'load' ) );

		expect( mockFinishResolution ).not.toHaveBeenCalled();
	} );

	it( 'should make API request when currently adding to cart with /?add-to-cart=', () => {
		mockHasCartSession.mockReturnValue( false );
		mockIsAddingToCart.mockReturnValue( true );
		mockPersistenceLayerGet.mockReturnValue( null );

		loadHandler( new Event( 'load' ) );

		expect( mockFinishResolution ).not.toHaveBeenCalled();
	} );

	it( 'should make API request when has cart session, cached cart has items, but adding to cart with /?add-to-cart=', () => {
		mockHasCartSession.mockReturnValue( true );
		mockIsAddingToCart.mockReturnValue( true );
		mockPersistenceLayerGet.mockReturnValue( {
			itemsCount: 2,
		} as unknown as Cart );

		loadHandler( new Event( 'load' ) );

		expect( mockFinishResolution ).not.toHaveBeenCalled();
	} );
} );

describe( 'iAPI store sync subscriber', () => {
	let syncSubscriber: () => void;
	let initialCart: object;
	let setTriggerStoreSyncEvent: ( value: boolean ) => void;
	let dispatchEventSpy: jest.SpyInstance;

	const selectCart = ( cartData: object ) => {
		mockWpSelect.mockReturnValue( {
			getCartData: () => cartData,
		} as unknown as ReturnType< typeof wpSelect > );
	};
	const syncEvents = () =>
		dispatchEventSpy.mock.calls.filter(
			( [ event ] ) => event.type === 'wc-blocks_store_sync_required'
		);

	beforeAll( () => {
		// Required lazily: importing these at the top would load the store module before the load listener is captured.
		initialCart =
			jest.requireActual( '../index' ).config.initialState.cartData;
		( { setTriggerStoreSyncEvent } = jest.requireActual( '../utils' ) );
		const { pushChanges } = jest.requireActual( '../push-changes' );
		// The sync subscriber is the cart store subscriber that isn't pushChanges.
		syncSubscriber = mockWpSubscribe.mock.calls.find(
			( [ callback, storeDescriptor ]: unknown[] ) =>
				( storeDescriptor as { name?: string } )?.name ===
					'wc/store/cart' && callback !== pushChanges
		)?.[ 0 ] as () => void;
	} );

	beforeEach( () => {
		dispatchEventSpy = jest.spyOn( window, 'dispatchEvent' );
	} );

	afterEach( () => {
		dispatchEventSpy.mockRestore();
		setTriggerStoreSyncEvent( true );
	} );

	// Runs first, so the subscriber has not seen any cart before this change.
	it( 'should emit a sync event for the first cart change, such as an extension cart update', () => {
		selectCart( { ...initialCart, itemsCount: 2 } );

		syncSubscriber();

		expect( syncEvents() ).toHaveLength( 1 );
		expect( syncEvents()[ 0 ][ 0 ].detail ).toEqual( {
			type: 'from_@wordpress/data',
		} );
	} );

	it( 'should not emit a sync event when the cart has not changed', () => {
		const cart = { ...initialCart, itemsCount: 3 };
		selectCart( cart );
		syncSubscriber();
		dispatchEventSpy.mockClear();

		syncSubscriber();

		expect( syncEvents() ).toHaveLength( 0 );
	} );

	it( 'should not emit a sync event while syncing is turned off', () => {
		setTriggerStoreSyncEvent( false );
		selectCart( { ...initialCart, itemsCount: 4 } );

		syncSubscriber();

		expect( syncEvents() ).toHaveLength( 0 );
	} );
} );
