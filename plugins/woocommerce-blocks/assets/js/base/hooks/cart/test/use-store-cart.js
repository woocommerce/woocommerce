/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import * as mockBaseContext from '@woocommerce/base-context';
import { previewCart } from '@woocommerce/resource-previews';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { defaultCartData, useStoreCart } from '../use-store-cart';

jest.mock( '@woocommerce/base-context', () => ( {
	useEditorContext: jest.fn(),
} ) );

jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	CART_STORE_KEY: 'test/store',
} ) );

describe( 'useStoreCart', () => {
	let registry, renderer;

	const previewCartData = {
		cartCoupons: previewCart.coupons,
		cartItems: previewCart.items,
		cartItemsCount: previewCart.items_count,
		cartItemsWeight: previewCart.items_weight,
		cartNeedsShipping: previewCart.needs_shipping,
		cartTotals: previewCart.totals,
		cartIsLoading: false,
		cartItemErrors: [],
		cartErrors: [],
		shippingRates: previewCart.shipping_rates,
		shippingAddress: {
			country: '',
			state: '',
			city: '',
			postcode: '',
		},
	};

	const mockCartItems = [ { key: '1', id: 1, name: 'Lorem Ipsum' } ];
	const mockShippingAddress = {
		city: 'New York',
	};
	const mockCartData = {
		coupons: [],
		items: mockCartItems,
		itemsCount: 1,
		itemsWeight: 10,
		needsShipping: true,
		shippingAddress: mockShippingAddress,
		shippingRates: [],
	};
	const mockCartTotals = {
		currency_code: 'USD',
	};
	const mockCartIsLoading = false;
	const mockCartErrors = [];
	const mockStoreCartData = {
		cartCoupons: [],
		cartItems: mockCartItems,
		cartItemsCount: 1,
		cartItemsWeight: 10,
		cartNeedsShipping: true,
		shippingAddress: mockShippingAddress,
		shippingRates: [],
		cartTotals: mockCartTotals,
		cartIsLoading: mockCartIsLoading,
		cartErrors: mockCartErrors,
	};

	const getWrappedComponents = ( Component ) => (
		<RegistryProvider value={ registry }>
			<Component />
		</RegistryProvider>
	);

	const getTestComponent = ( options ) => () => {
		const results = useStoreCart( options );
		return <div results={ results } />;
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
		mockBaseContext.useEditorContext.mockReset();
	} );

	describe( 'in frontend', () => {
		beforeEach( () => {
			mockBaseContext.useEditorContext.mockReturnValue( {
				isEditor: false,
			} );
		} );

		it( 'return default data when shouldSelect is false', () => {
			const TestComponent = getTestComponent( { shouldSelect: false } );

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const { results } = renderer.root.findByType( 'div' ).props;

			expect( results ).toEqual( defaultCartData );
		} );

		it( 'return store data when shouldSelect is true', () => {
			const TestComponent = getTestComponent( { shouldSelect: true } );

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const { results } = renderer.root.findByType( 'div' ).props;

			expect( results ).toEqual( mockStoreCartData );
		} );
	} );

	describe( 'in editor', () => {
		beforeEach( () => {
			mockBaseContext.useEditorContext.mockReturnValue( {
				isEditor: true,
			} );
		} );

		it( 'return preview data in editor', () => {
			const TestComponent = getTestComponent();

			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent )
				);
			} );

			const { results } = renderer.root.findByType( 'div' ).props;

			expect( results ).toEqual( previewCartData );
		} );
	} );
} );
