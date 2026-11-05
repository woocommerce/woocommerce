/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { config as checkoutStoreConfig } from '@woocommerce/block-data/checkout';
import * as mockUseStoreCart from '../use-store-cart';
import { useStoreCartItemQuantity } from '../use-store-cart-item-quantity';

jest.mock( '../use-store-cart', () => ( {
	useStoreCart: jest.fn(),
} ) );

jest.mock( '@woocommerce/block-data', () => {
	const cart = jest.requireActual( '@woocommerce/block-data/cart' );
	const checkout = jest.requireActual( '@woocommerce/block-data/checkout' );
	const utils = jest.requireActual( '@woocommerce/block-data/utils' );

	return {
		__esModule: true,
		CART_STORE_KEY: cart.CART_STORE_KEY,
		CHECKOUT_STORE_KEY: checkout.CHECKOUT_STORE_KEY,
		cartStore: cart.store,
		checkoutStore: checkout.store,
		processErrorResponse: utils.processErrorResponse,
	};
} );

// Make debounce instantaneous.
jest.mock( 'use-debounce', () => ( {
	useDebounce: ( a ) => [ a ],
} ) );

describe( 'useStoreCartItemQuantity', () => {
	let registry;

	const wrapper = ( { children } ) => (
		<RegistryProvider value={ registry }>{ children }</RegistryProvider>
	);

	const renderStoreCartItemQuantityHook = ( options ) =>
		renderHook( () => useStoreCartItemQuantity( options ), { wrapper } );

	let mockRemoveItemFromCart;
	let mockChangeCartItemQuantity;
	const setupMocks = ( { isPendingDelete, isPendingQuantity } ) => {
		// Register mock cart store
		mockRemoveItemFromCart = jest
			.fn()
			.mockReturnValue( { type: 'removeItemFromCartAction' } );
		mockChangeCartItemQuantity = jest
			.fn()
			.mockReturnValue( { type: 'changeCartItemQuantityAction' } );

		registry.registerStore( CART_STORE_KEY, {
			reducer: () => ( {} ),
			actions: {
				removeItemFromCart: mockRemoveItemFromCart,
				changeCartItemQuantity: mockChangeCartItemQuantity,
			},
			selectors: {
				isItemPendingDelete: jest
					.fn()
					.mockReturnValue( isPendingDelete ),
				isItemPendingQuantity: jest
					.fn()
					.mockReturnValue( isPendingQuantity ),
			},
		} );

		// Register actual checkout store
		registry.registerStore( CHECKOUT_STORE_KEY, checkoutStoreConfig );
	};

	beforeEach( () => {
		registry = createRegistry();
	} );

	afterEach( () => {
		mockRemoveItemFromCart.mockReset();
		mockChangeCartItemQuantity.mockReset();
	} );

	describe( 'with no errors and not pending', () => {
		beforeEach( () => {
			setupMocks( { isPendingDelete: false, isPendingQuantity: false } );
			mockUseStoreCart.useStoreCart.mockReturnValue( {
				cartErrors: {},
			} );
		} );

		it( 'update quantity value should happen instantly', () => {
			const { result } = renderStoreCartItemQuantityHook( {
				key: '123',
				quantity: 1,
			} );

			expect( result.current.quantity ).toBe( 1 );

			act( () => {
				result.current.setItemQuantity( 2 );
			} );

			expect( result.current.quantity ).toBe( 2 );
		} );

		it( 'removeItem should call the dispatch action', () => {
			const { result } = renderStoreCartItemQuantityHook( {
				key: '123',
				quantity: 1,
			} );

			act( () => {
				result.current.removeItem();
			} );

			expect( mockRemoveItemFromCart ).toHaveBeenCalledWith( '123' );
		} );

		it( 'setItemQuantity should call the dispatch action', () => {
			const { result } = renderStoreCartItemQuantityHook( {
				key: '123',
				quantity: 1,
			} );

			act( () => {
				result.current.setItemQuantity( 2 );
			} );

			expect( mockChangeCartItemQuantity.mock.calls ).toEqual( [
				[ '123', 2 ],
			] );
		} );
	} );

	it( 'should expose store errors', () => {
		const mockCartErrors = [ { message: 'Test error' } ];
		setupMocks( {
			isPendingDelete: false,
			isPendingQuantity: false,
		} );
		mockUseStoreCart.useStoreCart.mockReturnValue( {
			cartErrors: mockCartErrors,
		} );

		const { result } = renderStoreCartItemQuantityHook( {
			key: '123',
			quantity: 1,
		} );

		expect( result.current.cartItemQuantityErrors ).toEqual(
			mockCartErrors
		);
	} );

	it( 'isPendingDelete should depend on the value provided by the store', () => {
		setupMocks( {
			isPendingDelete: true,
			isPendingQuantity: false,
		} );
		mockUseStoreCart.useStoreCart.mockReturnValue( {
			cartErrors: {},
		} );

		const { result } = renderStoreCartItemQuantityHook( {
			key: '123',
			quantity: 1,
		} );

		expect( result.current.isPendingDelete ).toBe( true );
	} );
} );
