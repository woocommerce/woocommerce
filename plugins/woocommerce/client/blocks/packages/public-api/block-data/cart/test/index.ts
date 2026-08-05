/**
 * External dependencies
 */
import { dispatch as wpDispatch } from '@wordpress/data';

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

describe( 'Window load event handler', () => {
	let mockFinishResolution: jest.Mock;
	let originalAddEventListener: typeof window.addEventListener;
	let loadHandler: EventListener;

	beforeAll( () => {
		// Capture the addEventListener calls to extract the load handler
		originalAddEventListener = window.addEventListener;
		window.addEventListener = jest.fn(
			( event: string, handler: EventListenerOrEventListenerObject ) => {
				if ( event === 'load' && typeof handler === 'function' ) {
					loadHandler = handler;
				}
				return originalAddEventListener.call( window, event, handler );
			}
		);

		// Now import the module to register the event listener
		require( '../index' );
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
		mockPersistenceLayerGet.mockReturnValue( { itemsCount: 2 } );

		loadHandler( new Event( 'load' ) );

		expect( mockFinishResolution ).toHaveBeenCalledWith( 'getCartData' );
	} );

	it( 'should make API request when has cart session but cached cart is empty', () => {
		mockHasCartSession.mockReturnValue( true );
		mockIsAddingToCart.mockReturnValue( false );
		mockPersistenceLayerGet.mockReturnValue( { itemsCount: 0 } );

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
		mockPersistenceLayerGet.mockReturnValue( { itemsCount: 2 } );

		loadHandler( new Event( 'load' ) );

		expect( mockFinishResolution ).not.toHaveBeenCalled();
	} );
} );
