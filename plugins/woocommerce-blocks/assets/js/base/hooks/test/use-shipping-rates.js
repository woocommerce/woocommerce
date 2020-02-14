/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { COLLECTIONS_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useShippingRates } from '../use-shipping-rates';

jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	COLLECTIONS_STORE_KEY: 'test/store',
} ) );

// Make debounce instantaneous.
jest.mock( 'use-debounce', () => ( {
	useDebounce: ( a ) => [ a ],
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

	const getWrappedComponents = ( Component, props ) => (
		<RegistryProvider value={ registry }>
			<Component { ...props } />
		</RegistryProvider>
	);

	const getTestComponent = () => ( { query } ) => {
		const items = useShippingRates( query );
		return <div { ...items } />;
	};

	const setUpMocks = () => {
		mocks = {
			selectors: {
				getCollectionError: jest.fn().mockReturnValue( false ),
				getCollection: jest
					.fn()
					.mockImplementation( () => ( { foo: 'bar' } ) ),
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
	it(
		'should return expected behaviour for equivalent query on props ' +
			'across renders',
		() => {
			const TestComponent = getTestComponent();
			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent, {
						query: { bar: 'foo' },
					} )
				);
			} );
			const { shippingRates } = getProps( renderer );
			// rerender
			act( () => {
				renderer.update(
					getWrappedComponents( TestComponent, {
						query: { bar: 'foo' },
					} )
				);
			} );
			// re-render should result in same shippingRates object because although
			// query-state is a different instance, it's still equivalent.
			const { shippingRates: newShippingRates } = getProps( renderer );
			expect( newShippingRates ).toBe( shippingRates );
			// now let's change the query passed through to verify new object
			// is created.
			// remember this won't actually change the results because the mock
			// selector is returning an equivalent object when it is called,
			// however it SHOULD be a new object instance.
			act( () => {
				renderer.update(
					getWrappedComponents( TestComponent, {
						query: { foo: 'bar' },
					} )
				);
			} );
			const { shippingRates: shippingRatesVerification } = getProps(
				renderer
			);
			expect( shippingRatesVerification ).not.toBe( shippingRates );
			expect( shippingRatesVerification ).toEqual( shippingRates );
			renderer.unmount();
		}
	);
} );
