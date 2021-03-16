/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import * as mockUseStoreCart from '../use-store-cart';
import { useStoreCartItemQuantity } from '../use-store-cart-item-quantity';

jest.mock( '../use-store-cart', () => ( {
	useStoreCart: jest.fn(),
} ) );

jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	CART_STORE_KEY: 'test/store',
} ) );

// Make debounce instantaneous.
jest.mock( 'use-debounce', () => ( {
	useDebounce: ( a ) => [ a ],
} ) );

describe( 'useStoreCartItemQuantity', () => {
	let registry, renderer;

	const getWrappedComponents = ( Component ) => (
		<RegistryProvider value={ registry }>
			<Component />
		</RegistryProvider>
	);

	const getTestComponent = ( options ) => () => {
		const props = useStoreCartItemQuantity( options );
		return <div { ...props } />;
	};

	let mockRemoveItemFromCart;
	let mockChangeCartItemQuantity;
	const setupMocks = ( { isPendingDelete, isPendingQuantity } ) => {
		mockRemoveItemFromCart = jest
			.fn()
			.mockReturnValue( { type: 'removeItemFromCartAction' } );
		mockChangeCartItemQuantity = jest
			.fn()
			.mockReturnValue( { type: 'changeCartItemQuantityAction' } );
		registry.registerStore( storeKey, {
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
	};

	beforeEach( () => {
		registry = createRegistry();
		renderer = null;
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
			const TestComponent = getTestComponent( {
				key: '123',
				quantity: 1,
			} );

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const { setItemQuantity, quantity } = renderer.root.findByType(
				'div'
			).props;

			expect( quantity ).toBe( 1 );

			act( () => {
				setItemQuantity( 2 );
			} );

			const { quantity: newQuantity } = renderer.root.findByType(
				'div'
			).props;

			expect( newQuantity ).toBe( 2 );
		} );

		it( 'removeItem should call the dispatch action', () => {
			const TestComponent = getTestComponent( {
				key: '123',
				quantity: 1,
			} );

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const { removeItem } = renderer.root.findByType( 'div' ).props;

			act( () => {
				removeItem();
			} );

			expect( mockRemoveItemFromCart ).toHaveBeenCalledWith( '123' );
		} );

		it( 'setItemQuantity should call the dispatch action', () => {
			const TestComponent = getTestComponent( {
				key: '123',
				quantity: 1,
			} );

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const { setItemQuantity } = renderer.root.findByType( 'div' ).props;

			act( () => {
				setItemQuantity( 2 );
			} );

			expect( mockChangeCartItemQuantity.mock.calls ).toEqual( [
				[ '123', 2 ],
			] );
		} );
	} );

	it( 'should expose store errors', () => {
		const mockCartErrors = [ { message: 'Test error' } ];
		setupMocks( { isPendingDelete: false, isPendingQuantity: false } );
		mockUseStoreCart.useStoreCart.mockReturnValue( {
			cartErrors: mockCartErrors,
		} );

		const TestComponent = getTestComponent( {
			key: '123',
			quantity: 1,
		} );

		act( () => {
			renderer = TestRenderer.create(
				getWrappedComponents( TestComponent )
			);
		} );

		const { cartItemQuantityErrors } = renderer.root.findByType(
			'div'
		).props;

		expect( cartItemQuantityErrors ).toEqual( mockCartErrors );
	} );

	it( 'isPendingDelete should depend on the value provided by the store', () => {
		setupMocks( { isPendingDelete: true, isPendingQuantity: false } );
		mockUseStoreCart.useStoreCart.mockReturnValue( {
			cartErrors: {},
		} );

		const TestComponent = getTestComponent( {
			key: '123',
			quantity: 1,
		} );

		act( () => {
			renderer = TestRenderer.create(
				getWrappedComponents( TestComponent )
			);
		} );

		const { isPendingDelete } = renderer.root.findByType( 'div' ).props;

		expect( isPendingDelete ).toBe( true );
	} );
} );
