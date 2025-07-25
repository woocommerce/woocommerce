/**
 * External dependencies
 */
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { QUERY_STATE_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	useQueryStateByContext,
	useQueryStateByKey,
	useSynchronizedQueryState,
} from '../use-query-state';

jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	QUERY_STATE_STORE_KEY: 'test/store',
} ) );

/**
 * A helper for setting up the `mocks` object and the `registry` mock before
 * each test.
 *
 * @param {Object} registry
 * @param {Object} mocks
 * @param {string} actionMockName   This should be the name of the action
 *                                  that the hook returns. This will be
 *                                  mocked using `mocks.action`.
 * @param {string} selectorMockName This should be the name of the selector
 *                                  that the hook uses. This will be mocked
 *                                  using `mocks.selector`.
 */
const setupMocks = ( registry, mocks, actionMockName, selectorMockName ) => {
	mocks.action = jest.fn().mockReturnValue( { type: 'testAction' } );
	mocks.selector = jest.fn().mockReturnValue( { foo: 'bar' } );
	registry.registerStore( storeKey, {
		reducer: () => ( {} ),
		actions: {
			[ actionMockName ]: mocks.action,
		},
		selectors: {
			[ selectorMockName ]: mocks.selector,
		},
	} );
};

describe( 'Testing Query State Hooks', () => {
	let registry;
	let mocks;

	const wrapper = ( { children } ) => (
		<RegistryProvider value={ registry }>{ children }</RegistryProvider>
	);

	beforeEach( () => {
		registry = createRegistry();
		mocks = {};
	} );

	describe( 'useQueryStateByContext', () => {
		beforeEach( () => {
			setupMocks(
				registry,
				mocks,
				'setValueForQueryContext',
				'getValueForQueryContext'
			);
		} );

		it( 'calls useSelect with the provided context and returns expected values', () => {
			const { action, selector } = mocks;
			const { result } = renderHook(
				( { context } ) => useQueryStateByContext( context ),
				{
					initialProps: { context: 'test-context' },
					wrapper,
				}
			);
			const [ queryState, setQueryState ] = result.current;
			// the {} is because all selectors are called internally in the
			// registry with the first argument being the state which is empty.
			expect( selector ).toHaveBeenLastCalledWith(
				{},
				'test-context',
				undefined
			);
			expect( queryState ).toEqual( { foo: 'bar' } );
			expect( action ).not.toHaveBeenCalled();

			// execute dispatcher and make sure it's called.
			act( () => {
				setQueryState( { foo: 'bar' } );
			} );
			expect( action ).toHaveBeenCalledWith( 'test-context', {
				foo: 'bar',
			} );
		} );
	} );

	describe( 'useQueryStateByKey', () => {
		beforeEach( () => {
			setupMocks(
				registry,
				mocks,
				'setQueryValue',
				'getValueForQueryKey'
			);
		} );

		it( 'calls useSelect with the provided context and returns expected values', () => {
			const { selector, action } = mocks;
			const { result } = renderHook(
				( { context, queryKey } ) =>
					useQueryStateByKey( queryKey, undefined, context ),
				{
					initialProps: {
						context: 'test-context',
						queryKey: 'someValue',
					},
					wrapper,
				}
			);

			const [ queryState, setQueryState ] = result.current;
			// the {} is because all selectors are called internally in the
			// registry with the first argument being the state which is empty.
			expect( selector ).toHaveBeenLastCalledWith(
				{},
				'test-context',
				'someValue',
				undefined
			);
			expect( queryState ).toEqual( { foo: 'bar' } );
			expect( action ).not.toHaveBeenCalled();

			// execute dispatcher and make sure it's called.
			act( () => {
				setQueryState( { foo: 'bar' } );
			} );
			expect( action ).toHaveBeenCalledWith(
				'test-context',
				'someValue',
				{ foo: 'bar' }
			);
		} );
	} );

	// Note: these tests only add partial coverage because the state is not
	// actually updated by the action dispatch via our mocks.
	describe( 'useSynchronizedQueryState', () => {
		const initialQuery = { a: 'b' };

		beforeEach( () => {
			setupMocks(
				registry,
				mocks,
				'setValueForQueryContext',
				'getValueForQueryContext'
			);
		} );

		it( 'returns provided query state on initial render and merges state', () => {
			const { action, selector } = mocks;
			const { result } = renderHook(
				( { context, synchronizedQuery } ) =>
					useSynchronizedQueryState( synchronizedQuery, context ),
				{
					initialProps: {
						context: 'test-context',
						synchronizedQuery: initialQuery,
					},
					wrapper,
				}
			);

			const [ queryState ] = result.current;
			expect( queryState ).toBe( initialQuery );
			expect( selector ).toHaveBeenLastCalledWith(
				{},
				'test-context',
				undefined
			);
			expect( action ).toHaveBeenCalledWith( 'test-context', {
				foo: 'bar',
				a: 'b',
			} );
		} );

		it( 'returns merged queryState on subsequent render', () => {
			const { result, rerender } = renderHook(
				( { context, synchronizedQuery } ) =>
					useSynchronizedQueryState( synchronizedQuery, context ),
				{
					initialProps: {
						context: 'test-context',
						synchronizedQuery: initialQuery,
					},
					wrapper,
				}
			);

			rerender( {
				context: 'test-context',
				synchronizedQuery: initialQuery,
			} );

			// note our test doesn't interact with an actual reducer so the
			// store state is not updated. Here we're just verifying that
			// what is is returned by the state selector mock is returned.
			// However we DO expect this to be a new object.
			const [ queryState ] = result.current;
			expect( queryState ).not.toBe( initialQuery );
			expect( queryState ).toEqual( { foo: 'bar' } );
		} );
	} );
} );
