/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { COLLECTIONS_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useCollection } from '../use-collection';

jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	COLLECTIONS_STORE_KEY: 'test/store',
} ) );

describe( 'useCollection', () => {
	let registry, mocks;

	const wrapper = ( { children } ) => (
		<RegistryProvider value={ registry }>{ children }</RegistryProvider>
	);

	const renderUseCollection = ( options ) =>
		renderHook( ( props ) => useCollection( props.options ), {
			initialProps: { options },
			wrapper,
		} );

	// useCollection throws during render for invalid options or store errors;
	// renderHook rethrows that error, so we capture it here.
	const renderUseCollectionError = ( options ) => {
		let error;
		try {
			renderUseCollection( options );
		} catch ( caught ) {
			error = caught;
		}
		return error;
	};

	const setUpMocks = () => {
		// Memoize the fixture by selector args so wp-data's SCRIPT_DEBUG
		// unstable-reference check (which double-invokes the selector with
		// the same state) sees the same object reference each time. Real
		// Redux selectors return stable references when args and state are
		// unchanged; a naive `() => ({ foo: 'bar' })` mock returns a fresh
		// object every call, which wp-data correctly flags.
		const collectionCache = new Map();
		const getCollection = jest.fn().mockImplementation( ( ...args ) => {
			const key = JSON.stringify( args );
			if ( ! collectionCache.has( key ) ) {
				collectionCache.set( key, { foo: 'bar' } );
			}
			return collectionCache.get( key );
		} );
		mocks = {
			selectors: {
				getCollectionError: jest.fn().mockReturnValue( false ),
				getCollection,
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
		setUpMocks();
	} );
	it(
		'should throw an error if an options object is provided without ' +
			'a namespace property',
		() => {
			const error = renderUseCollectionError( {
				resourceName: 'products',
				query: { bar: 'foo' },
			} );
			expect( error.message ).toMatch( /options object/ );
			expect( console ).toHaveErrored( /your React components:/ );
		}
	);
	it(
		'should throw an error if an options object is provided without ' +
			'a resourceName property',
		() => {
			const error = renderUseCollectionError( {
				namespace: 'test/store',
				query: { bar: 'foo' },
			} );
			expect( error.message ).toMatch( /options object/ );
			expect( console ).toHaveErrored( /your React components:/ );
		}
	);
	it(
		'should return expected behaviour for equivalent query on props ' +
			'across renders',
		() => {
			const { result, rerender } = renderUseCollection( {
				namespace: 'test/store',
				resourceName: 'products',
				query: { bar: 'foo' },
			} );
			const { results } = result.current;
			// rerender
			rerender( {
				options: {
					namespace: 'test/store',
					resourceName: 'products',
					query: { bar: 'foo' },
				},
			} );
			// re-render should result in same products object because although
			// query-state is a different instance, it's still equivalent.
			const { results: newResults } = result.current;
			expect( newResults ).toBe( results );
			// now let's change the query passed through to verify new object
			// is created.
			// remember this won't actually change the results because the mock
			// selector is returning an equivalent object when it is called,
			// however it SHOULD be a new object instance.
			rerender( {
				options: {
					namespace: 'test/store',
					resourceName: 'products',
					query: { foo: 'bar' },
				},
			} );
			const { results: resultsVerification } = result.current;
			expect( resultsVerification ).not.toBe( results );
			expect( resultsVerification ).toEqual( results );
		}
	);
	it(
		'should return expected behaviour for equivalent resourceValues on' +
			' props across renders',
		() => {
			const { result, rerender } = renderUseCollection( {
				namespace: 'test/store',
				resourceName: 'products',
				resourceValues: [ 10, 20 ],
			} );
			const { results } = result.current;
			// rerender
			rerender( {
				options: {
					namespace: 'test/store',
					resourceName: 'products',
					resourceValues: [ 10, 20 ],
				},
			} );
			// re-render should result in same products object because although
			// query-state is a different instance, it's still equivalent.
			const { results: newResults } = result.current;
			expect( newResults ).toBe( results );
			// now let's change the query passed through to verify new object
			// is created.
			// remember this won't actually change the results because the mock
			// selector is returning an equivalent object when it is called,
			// however it SHOULD be a new object instance.
			rerender( {
				options: {
					namespace: 'test/store',
					resourceName: 'products',
					resourceValues: [ 20, 10 ],
				},
			} );
			const { results: resultsVerification } = result.current;
			expect( resultsVerification ).not.toBe( results );
			expect( resultsVerification ).toEqual( results );
		}
	);
	it( 'should return previous query results if `shouldSelect` is false', () => {
		// Memoize by args so wp-data's SCRIPT_DEBUG unstable-reference check
		// sees the same array reference across the two selector invocations
		// it does within a single render cycle. The test intentionally uses
		// `args` as the stored value to verify the selector was called.
		const cache = new Map();
		mocks.selectors.getCollection.mockImplementation(
			( state, ...args ) => {
				const key = JSON.stringify( args );
				if ( ! cache.has( key ) ) {
					cache.set( key, args );
				}
				return cache.get( key );
			}
		);
		const { result, rerender } = renderUseCollection( {
			namespace: 'test/store',
			resourceName: 'products',
			resourceValues: [ 10, 20 ],
		} );
		const { results } = result.current;
		// Capture the call count after the first render so the next assertion
		// measures whether the rerender caused additional invocations rather
		// than the absolute total (wp-data's SCRIPT_DEBUG unstable-reference
		// check double-invokes the useSelect mapping, so the absolute count
		// is implementation-dependent).
		const callsAfterFirstRender =
			mocks.selectors.getCollection.mock.calls.length;
		// rerender but with shouldSelect to false
		rerender( {
			options: {
				namespace: 'test/store',
				resourceName: 'productsb',
				resourceValues: [ 10, 30 ],
				shouldSelect: false,
			},
		} );
		const { results: results2 } = result.current;
		expect( results2 ).toBe( results );
		// `shouldSelect: false` should not have triggered any new selector
		// invocations; the cached previous results should be returned.
		expect( mocks.selectors.getCollection.mock.calls.length ).toBe(
			callsAfterFirstRender
		);

		// rerender again but set shouldSelect to true again and we should see
		// new results
		rerender( {
			options: {
				namespace: 'test/store',
				resourceName: 'productsb',
				resourceValues: [ 10, 30 ],
				shouldSelect: true,
			},
		} );
		const { results: results3 } = result.current;
		expect( results3 ).not.toEqual( results );
		expect( results3 ).toEqual( [
			'test/store',
			'productsb',
			{},
			[ 10, 30 ],
		] );
	} );
	const renderWithStoreError = ( errorValue ) => {
		mocks.selectors.getCollectionError.mockReturnValue( errorValue );
		return renderUseCollectionError( {
			namespace: 'test/store',
			resourceName: 'products',
			query: { bar: 'foo' },
		} );
	};

	it( 'should propagate an Error instance from the store via the error boundary', () => {
		const error = new Error( 'A real error' );
		const caught = renderWithStoreError( error );
		expect( caught ).toBeInstanceOf( Error );
		expect( caught.message ).toBe( 'A real error' );
		expect( console ).toHaveErrored( /your React components:/ );
	} );
	it( 'should convert a non-Error object with a message into an Error instance', () => {
		const error = { code: 'rest_no_route', message: 'No route found.' };
		const caught = renderWithStoreError( error );
		expect( caught ).toBeInstanceOf( Error );
		expect( caught.message ).toBe( 'No route found.' );
		expect( console ).toHaveErrored( /your React components:/ );
	} );
	it( 'should use a fallback message when a non-Error object has no message', () => {
		const caught = renderWithStoreError( { code: 500 } );
		expect( caught ).toBeInstanceOf( Error );
		expect( caught.message ).toBe(
			'Something went wrong while loading data.'
		);
		expect( console ).toHaveErrored( /your React components:/ );
	} );
	it( 'should use a fallback message when a primitive value is returned from the store', () => {
		const caught = renderWithStoreError( 'oops' );
		expect( caught ).toBeInstanceOf( Error );
		expect( caught.message ).toBe(
			'Something went wrong while loading data.'
		);
		expect( console ).toHaveErrored( /your React components:/ );
	} );
} );
