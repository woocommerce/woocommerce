import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { defaultCartData, useStoreCart } from '../use-store-cart';
import { useEditorContext } from '../../../providers/editor-context';
vi.mock( '../../../providers/editor-context', () => {
	const mock = {
		useEditorContext: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/block-data', async () => {
	const constants = await vi.importActual(
		'@woocommerce/block-data/constants'
	);
	const cart = await vi.importActual( '@woocommerce/block-data/cart' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		...constants,
		CART_STORE_KEY: cart.CART_STORE_KEY,
		cartStore: cart.store,
	} );
} );
describe( 'useStoreCart', () => {
	let registry;
	const mockCartItems = [
		{
			key: '1',
			id: 1,
			name: 'Lorem Ipsum',
		},
	];
	const mockShippingAddress = {
		city: 'New York',
	};
	const mockCartData = {
		coupons: [],
		items: mockCartItems,
		crossSells: [],
		fees: [],
		itemsCount: 1,
		itemsWeight: 10,
		needsPayment: true,
		needsShipping: true,
		billingAddress: {},
		shippingAddress: mockShippingAddress,
		shippingRates: [],
		hasCalculatedShipping: true,
		extensions: {},
		errors: [],
		paymentRequirements: [],
		receiveCart: () => undefined,
		receiveCartContents: () => undefined,
		paymentMethods: [],
		hasPendingItemsOperations: false,
	};
	const mockCartTotals = {
		currency_code: 'USD',
	};
	const mockCartIsLoading = false;
	const mockCartErrors = [];
	const mockStoreCartData = {
		cartCoupons: [],
		cartItems: mockCartItems,
		crossSellsProducts: [],
		cartItemErrors: [],
		cartItemsCount: 1,
		cartItemsWeight: 10,
		cartNeedsPayment: true,
		cartNeedsShipping: true,
		cartTotals: mockCartTotals,
		cartIsLoading: mockCartIsLoading,
		cartErrors: mockCartErrors,
		cartFees: [],
		billingData: {},
		billingAddress: {},
		shippingAddress: mockShippingAddress,
		shippingRates: [],
		extensions: {},
		isLoadingRates: false,
		cartHasCalculatedShipping: true,
		paymentMethods: [],
		paymentRequirements: [],
		hasPendingItemsOperations: false,
	};
	const wrapper = ( { children } ) => (
		<RegistryProvider value={ registry }>{ children }</RegistryProvider>
	);
	const renderStoreCartHook = ( options ) =>
		renderHook( () => useStoreCart( options ), {
			wrapper,
		} );
	const setUpMocks = () => {
		const mocks = {
			selectors: {
				getCartData: vi.fn().mockReturnValue( mockCartData ),
				getCartErrors: vi.fn().mockReturnValue( mockCartErrors ),
				getCartTotals: vi.fn().mockReturnValue( mockCartTotals ),
				hasFinishedResolution: vi
					.fn()
					.mockReturnValue( ! mockCartIsLoading ),
				isCustomerDataUpdating: vi.fn().mockReturnValue( false ),
				isAddressFieldsForShippingRatesUpdating: vi
					.fn()
					.mockReturnValue( false ),
				hasPendingItemsOperations: vi.fn().mockReturnValue( false ),
			},
		};
		registry.registerStore( storeKey, {
			reducer: () => ( {} ),
			selectors: mocks.selectors,
		} );
	};
	beforeEach( () => {
		registry = createRegistry();
		setUpMocks();
	} );
	afterEach( () => {
		useEditorContext.mockReset();
	} );
	describe( 'in frontend', () => {
		beforeEach( () => {
			useEditorContext.mockReturnValue( {
				isEditor: false,
			} );
		} );
		it( 'return default data when shouldSelect is false', () => {
			const { result } = renderStoreCartHook( {
				shouldSelect: false,
			} );
			const { receiveCart, receiveCartContents, ...results } =
				result.current;
			const {
				receiveCart: defaultReceiveCart,
				receiveCartContents: defaultReceiveCartContents,
				...remaining
			} = defaultCartData;
			expect( results ).toEqual( remaining );
			expect( receiveCart ).toEqual( defaultReceiveCart );
			expect( receiveCartContents ).toEqual( defaultReceiveCartContents );
		} );
		it( 'return store data when shouldSelect is true', () => {
			const { result } = renderStoreCartHook( {
				shouldSelect: true,
			} );
			const { receiveCart, receiveCartContents, ...results } =
				result.current;
			expect( results ).toEqual( mockStoreCartData );
			expect( receiveCart ).toBeUndefined();
			expect( receiveCartContents ).toBeUndefined();
		} );
	} );
} );
