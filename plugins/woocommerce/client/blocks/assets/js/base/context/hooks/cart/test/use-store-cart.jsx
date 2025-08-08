/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { defaultCartData, useStoreCart } from '../use-store-cart';
import { useEditorContext } from '../../../providers/editor-context';

jest.mock( '../../../providers/editor-context', () => ( {
	useEditorContext: jest.fn(),
} ) );

jest.mock( '@woocommerce/block-data', () => ( {
	...jest.requireActual( '@woocommerce/block-data' ),
	__esModule: true,
} ) );

describe( 'useStoreCart', () => {
	let registry, renderer;

	const mockCartItems = [ { key: '1', id: 1, name: 'Lorem Ipsum' } ];
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

	const getWrappedComponents = ( Component ) => (
		<RegistryProvider value={ registry }>
			<Component />
		</RegistryProvider>
	);

	const getTestComponent = ( options ) => () => {
		const { receiveCart, receiveCartContents, ...results } =
			useStoreCart( options );
		return (
			<div
				data-results={ results }
				data-receiveCart={ receiveCart }
				data-receiveCartContents={ receiveCartContents }
			/>
		);
	};

	const setUpMocks = () => {
		const mocks = {
			selectors: {
				getCartData: jest.fn().mockReturnValue( mockCartData ),
				getCartErrors: jest.fn().mockReturnValue( mockCartErrors ),
				getCartTotals: jest.fn().mockReturnValue( mockCartTotals ),
				hasFinishedResolution: jest
					.fn()
					.mockReturnValue( ! mockCartIsLoading ),
				isCustomerDataUpdating: jest.fn().mockReturnValue( false ),
				isAddressFieldsForShippingRatesUpdating: jest
					.fn()
					.mockReturnValue( false ),
				hasPendingItemsOperations: jest.fn().mockReturnValue( false ),
			},
		};
		registry.registerStore( storeKey, {
			reducer: () => ( {} ),
			selectors: mocks.selectors,
		} );
	};

	beforeEach( () => {
		registry = createRegistry();
		renderer = null;
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
			const TestComponent = getTestComponent( {
				shouldSelect: false,
			} );

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const props = renderer.root.findByType( 'div' ).props; //eslint-disable-line testing-library/await-async-query
			const results = props[ 'data-results' ];
			const receiveCart = props[ 'data-receiveCart' ];
			const receiveCartContents = props[ 'data-receiveCartContents' ];

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
			const TestComponent = getTestComponent( {
				shouldSelect: true,
			} );

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const props = renderer.root.findByType( 'div' ).props; //eslint-disable-line testing-library/await-async-query
			const results = props[ 'data-results' ];
			const receiveCart = props[ 'data-receiveCart' ];
			const receiveCartContents = props[ 'data-receiveCartContents' ];

			expect( results ).toEqual( mockStoreCartData );
			expect( receiveCart ).toBeUndefined();
			expect( receiveCartContents ).toBeUndefined();
		} );
	} );
} );
