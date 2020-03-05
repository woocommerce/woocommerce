/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useShippingRates } from '../use-shipping-rates';

jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	CART_STORE_KEY: 'test/store',
} ) );

describe( 'useShippingRates', () => {
	let registry, mocks, renderer;
	const getProps = ( testRenderer ) => {
		const {
			shippingRates,
			shippingRatesLoading,
		} = testRenderer.root.findByType( 'div' ).props;
		return {
			shippingRates,
			shippingRatesLoading,
		};
	};

	const getWrappedComponents = ( Component ) => (
		<RegistryProvider value={ registry }>
			<Component />
		</RegistryProvider>
	);

	const getTestComponent = () => () => {
		const items = useShippingRates();
		return <div { ...items } />;
	};

	const mockCartData = {
		coupons: [],
		items: [ { foo: 'bar' } ],
		itemsCount: 123,
		itemsWeight: 123,
		needsShipping: false,
		shippingRates: { foo: 'bar' },
	};

	const setUpMocks = () => {
		mocks = {
			selectors: {
				getCartData: jest.fn().mockReturnValue( mockCartData ),
				getCartErrors: jest.fn().mockReturnValue( false ),
				getCartTotals: jest.fn().mockReturnValue( 123 ),
				areShippingRatesLoading: jest.fn().mockReturnValue( false ),
				hasFinishedResolution: jest.fn().mockReturnValue( true ),
			},
		};
		registry.registerStore( storeKey, {
			reducer: () => ( {} ),
			selectors: mocks.selectors,
		} );
	};

	beforeEach( () => {
		registry = createRegistry();
		mocks = {};
		renderer = null;
		setUpMocks();
	} );
	it( 'should return expected shipping rates provided by the store', () => {
		const TestComponent = getTestComponent();
		act( () => {
			renderer = TestRenderer.create(
				getWrappedComponents( TestComponent )
			);
		} );
		const { shippingRates } = getProps( renderer );
		expect( shippingRates ).toBe( mockCartData.shippingRates );
		// rerender
		act( () => {
			renderer.update( getWrappedComponents( TestComponent ) );
		} );
		// re-render should result in same shippingRates object.
		const { shippingRates: newShippingRates } = getProps( renderer );
		expect( newShippingRates ).toBe( shippingRates );
		renderer.unmount();
	} );
} );
