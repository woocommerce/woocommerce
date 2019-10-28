/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { Component as ReactComponent } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useStoreProducts } from '../use-store-products';
import { COLLECTIONS_STORE_KEY as storeKey } from '@woocommerce/block-data';

jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	COLLECTIONS_STORE_KEY: 'test/store',
} ) );

class TestErrorBoundary extends ReactComponent {
	constructor( props ) {
		super( props );
		this.state = { hasError: false, error: {} };
	}
	static getDerivedStateFromError( error ) {
		// Update state so the next render will show the fallback UI.
		return { hasError: true, error };
	}

	render() {
		if ( this.state.hasError ) {
			return <div error={ this.state.error } />;
		}

		return this.props.children;
	}
}

describe( 'useStoreProducts', () => {
	let registry, mocks, renderer;
	const getProps = ( testRenderer ) => {
		const {
			products,
			totalProducts,
			productsLoading,
		} = testRenderer.root.findByType( 'div' ).props;
		return {
			products,
			totalProducts,
			productsLoading,
		};
	};

	const getWrappedComponents = ( Component, props ) => (
		<RegistryProvider value={ registry }>
			<TestErrorBoundary>
				<Component { ...props } />
			</TestErrorBoundary>
		</RegistryProvider>
	);

	const getTestComponent = ( options ) => ( { query } ) => {
		const items = useStoreProducts( query, options );
		return <div { ...items } />;
	};

	const setUpMocks = () => {
		mocks = {
			selectors: {
				getCollection: jest
					.fn()
					.mockImplementation( () => ( { foo: 'bar' } ) ),
				getCollectionHeader: jest.fn().mockReturnValue( 22 ),
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
		'should throw an error if an options object is provided without ' +
			'a namespace property',
		() => {
			const TestComponent = getTestComponent( { modelName: 'products' } );
			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent, {
						query: { bar: 'foo' },
					} )
				);
			} );
			const props = renderer.root.findByType( 'div' ).props;
			expect( props.error.message ).toMatch( /options object/ );
			expect( console ).toHaveErrored( /your React components:/ );
			renderer.unmount();
		}
	);
	it(
		'should throw an error if an options object is provided without ' +
			'a modelName property',
		() => {
			const TestComponent = getTestComponent( {
				namespace: '/wc/blocks',
			} );
			act( () => {
				renderer = TestRenderer.create(
					getWrappedComponents( TestComponent, {
						query: { bar: 'foo' },
					} )
				);
			} );
			const props = renderer.root.findByType( 'div' ).props;
			expect( props.error.message ).toMatch( /options object/ );
			expect( console ).toHaveErrored( /your React components:/ );
			renderer.unmount();
		}
	);
	it( 'should use the default options if options not provided', () => {
		const TestComponent = getTestComponent();
		const {
			getCollection,
			getCollectionHeader,
			hasFinishedResolution,
		} = mocks.selectors;
		act( () => {
			renderer = TestRenderer.create(
				getWrappedComponents( TestComponent, {
					query: { bar: 'foo' },
				} )
			);
		} );
		expect( getCollection ).toHaveBeenCalledWith(
			{},
			'/wc/blocks',
			'products',
			{ bar: 'foo' }
		);
		expect( getCollectionHeader ).toHaveBeenCalledWith(
			{},
			'x-wp-total',
			'/wc/blocks',
			'products',
			{ bar: 'foo' }
		);
		expect( hasFinishedResolution ).toHaveBeenCalledWith(
			{},
			'getCollection',
			[ '/wc/blocks', 'products', { bar: 'foo' } ]
		);
		renderer.unmount();
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
			const { products } = getProps( renderer );
			// rerender
			act( () => {
				renderer.update(
					getWrappedComponents( TestComponent, {
						query: { bar: 'foo' },
					} )
				);
			} );
			// re-render should result in same products object because although
			// query-state is a different instance, it's still equivalent.
			const { products: newProducts } = getProps( renderer );
			expect( newProducts ).toBe( products );
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
			const { products: productsVerification } = getProps( renderer );
			expect( productsVerification ).not.toBe( products );
			expect( productsVerification ).toEqual( products );
			renderer.unmount();
		}
	);
} );
