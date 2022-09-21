/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { previewCart } from '@woocommerce/resource-previews';
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
	CART_STORE_KEY: 'test/store',
} ) );

describe( 'useStoreCart', () => {
	let registry, renderer;

	const receiveCartMock = () => {};

	const previewCartData = {
		cartCoupons: previewCart.coupons,
		cartItems: previewCart.items,
		crossSellsProducts: previewCart.cross_sells,
		cartFees: previewCart.fees,
		cartItemsCount: previewCart.items_count,
		cartItemsWeight: previewCart.items_weight,
		cartNeedsPayment: previewCart.needs_payment,
		cartNeedsShipping: previewCart.needs_shipping,
		cartTotals: previewCart.totals,
		cartIsLoading: false,
		cartItemErrors: [],
		cartErrors: [],
		billingData: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			email: '',
			phone: '',
		},
		billingAddress: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			email: '',
			phone: '',
		},
		shippingAddress: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			phone: '',
		},
		shippingRates: previewCart.shipping_rates,
		extensions: {},
		isLoadingRates: false,
		cartHasCalculatedShipping: true,
	};

	const mockCartItems = [ { key: '1', id: 1, name: 'Lorem Ipsum' } ];
	const mockShippingAddress = {
		city: 'New York',
	};
	const mockCartData = {
		coupons: [],
		items: mockCartItems,
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
		receiveCart: undefined,
		paymentRequirements: [],
	};
	const mockCartTotals = {
		currency_code: 'USD',
	};
	const mockCartIsLoading = false;
	const mockCartErrors = [];
	const mockStoreCartData = {
		cartCoupons: [],
		cartItems: mockCartItems,
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
		receiveCart: undefined,
		paymentRequirements: [],
	};

	const getWrappedComponents = ( Component ) => (
		<RegistryProvider value={ registry }>
			<Component />
		</RegistryProvider>
	);

	const getTestComponent = ( options ) => () => {
		const { receiveCart, ...results } = useStoreCart( options );
		return <div results={ results } receiveCart={ receiveCart } />;
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

			const { results, receiveCart } =
				renderer.root.findByType( 'div' ).props; //eslint-disable-line testing-library/await-async-query
			const { receiveCart: defaultReceiveCart, ...remaining } =
				defaultCartData;
			expect( results ).toEqual( remaining );
			expect( receiveCart ).toEqual( defaultReceiveCart );
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

			const { results, receiveCart } =
				renderer.root.findByType( 'div' ).props; //eslint-disable-line testing-library/await-async-query

			expect( results ).toEqual( mockStoreCartData );
			expect( receiveCart ).toBeUndefined();
		} );
	} );

	describe( 'in editor', () => {
		beforeEach( () => {
			useEditorContext.mockReturnValue( {
				isEditor: true,
				previewData: {
					previewCart: {
						...previewCart,
						receiveCart: receiveCartMock,
					},
				},
			} );
		} );

		it( 'return preview data in editor', () => {
			const TestComponent = getTestComponent();

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const { results, receiveCart } =
				renderer.root.findByType( 'div' ).props; //eslint-disable-line testing-library/await-async-query

			expect( results ).toEqual( previewCartData );
			expect( receiveCart ).toEqual( receiveCartMock );
		} );
	} );
} );
