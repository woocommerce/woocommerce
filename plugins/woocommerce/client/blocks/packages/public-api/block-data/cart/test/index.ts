import {
	afterAll,
	beforeAll,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
} from 'vitest';

/**
 * External dependencies
 */
import { dispatch as wpDispatch } from '@wordpress/data';
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
vi.mock( '@wordpress/data' );
vi.mock( '@woocommerce/utils', () => {
	const mock = {
		isSiteEditorPage: vi.fn().mockReturnValue( true ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../persistence-layer' );
const mockHasCartSession = vi.mocked( hasCartSession );
const mockIsAddingToCart = vi.mocked( isAddingToCart );
const mockPersistenceLayerGet = vi.mocked( persistenceLayer.get );
const mockWpDispatch = vi.mocked( wpDispatch );
describe( 'Window load event handler', () => {
	let mockFinishResolution: Mock;
	let originalAddEventListener: typeof window.addEventListener;
	let loadHandler: ( event: Event ) => void;
	beforeAll( async () => {
		// Capture the addEventListener calls to extract the load handler
		originalAddEventListener = window.addEventListener;
		window.addEventListener = vi.fn(
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
		await vi.importActual( '../index' );
	} );
	beforeEach( () => {
		mockFinishResolution = vi.fn();
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
