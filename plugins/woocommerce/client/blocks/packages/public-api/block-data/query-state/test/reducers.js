/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import queryStateReducer from '../reducers';
import { setQueryValue, setValueForQueryContext } from '../actions';

describe( 'queryStateReducer', () => {
	const originalState = deepFreeze( {
		context: JSON.stringify( {
			foo: 'bar',
			cheese: 'pizza',
		} ),
	} );
	it(
		'returns original state when the action is not of the type being ' +
			'processed',
		() => {
			expect(
				queryStateReducer( originalState, { type: 'invalid' } )
			).toBe( originalState );
		}
	);
	describe( 'SET_QUERY_KEY_VALUE action', () => {
		it(
			'returns original state when incoming query-state key value ' +
				'matches what is already in the state',
			() => {
				expect(
					queryStateReducer(
						originalState,
						setQueryValue( 'context', 'foo', 'bar' )
					)
				).toBe( originalState );
			}
		);
		it(
			'returns new state when incoming query-state key exist ' +
				'but the value is a new value',
			() => {
				const newState = queryStateReducer(
					originalState,
					setQueryValue( 'context', 'foo', 'zed' )
				);
				expect( newState ).not.toBe( originalState );
				expect( newState ).toEqual( {
					context: JSON.stringify( {
						foo: 'zed',
						cheese: 'pizza',
					} ),
				} );
			}
		);
		it(
			'returns new state when incoming query-state key does not ' +
				'exist',
			() => {
				const newState = queryStateReducer(
					originalState,
					setQueryValue( 'context', 'burger', 'pizza' )
				);
				expect( newState ).not.toBe( originalState );
				expect( newState ).toEqual( {
					context: JSON.stringify( {
						foo: 'bar',
						cheese: 'pizza',
						burger: 'pizza',
					} ),
				} );
			}
		);
	} );
	describe( 'SET_QUERY_CONTEXT_VALUE action', () => {
		it(
			'returns original state when incoming context value matches ' +
				'what is already in the state',
			() => {
				expect(
					queryStateReducer(
						originalState,
						setValueForQueryContext( 'context', {
							foo: 'bar',
							cheese: 'pizza',
						} )
					)
				).toBe( originalState );
			}
		);
		it(
			'returns new state when incoming context value is different ' +
				'than what is already in the state',
			() => {
				const newState = queryStateReducer(
					originalState,
					setValueForQueryContext( 'context', {
						bar: 'foo',
						pizza: 'cheese',
					} )
				);
				expect( newState ).not.toBe( originalState );
				expect( newState ).toEqual( {
					context: JSON.stringify( {
						bar: 'foo',
						pizza: 'cheese',
					} ),
				} );
			}
		);
		it(
			'returns new state when incoming context does not exist in the ' +
				'state',
			() => {
				const newState = queryStateReducer(
					originalState,
					setValueForQueryContext( 'contextb', {
						foo: 'bar',
					} )
				);
				expect( newState ).not.toBe( originalState );
				expect( newState ).toEqual( {
					context: JSON.stringify( {
						foo: 'bar',
						cheese: 'pizza',
					} ),
					contextb: JSON.stringify( {
						foo: 'bar',
					} ),
				} );
			}
		);
	} );
} );
