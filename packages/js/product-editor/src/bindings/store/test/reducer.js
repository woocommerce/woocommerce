/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import { ACTION_REGISTER_BINDINGS_SOURCE } from '../constants';

describe( 'reducer', () => {
	describe( 'ACTION_REGISTER_BINDINGS_SOURCE', () => {
		it( 'should handle the action and update the state accordingly', () => {
			const initialState = {
				sources: {},
			};
			deepFreeze( initialState );

			const source = {
				name: 'namespace/source-name',
				label: 'Source handler of the namespace',
				anotherAttribute: 'anotherValue',
			};

			const action = {
				type: ACTION_REGISTER_BINDINGS_SOURCE,
				...source,
			};

			deepFreeze( action );

			const expectedState = {
				sources: {
					'namespace/source-name': {
						label: 'Source handler of the namespace',
						anotherAttribute: 'anotherValue',
					},
				},
			};

			const resultState = reducer( initialState, action );
			expect( resultState ).toEqual( expectedState );
		} );

		it( 'should add new sources without overwriting existing ones', () => {
			const initialState = {
				sources: {
					'namespace/old-source-name': {
						label: 'An old source',
						anotherAttribute: 'someValue',
					},
				},
			};
			deepFreeze( initialState );

			const newSource = {
				name: 'namespace/new-source-name',
				label: 'A new source',
				newAttribute: 'newValue',
			};

			const action = {
				type: ACTION_REGISTER_BINDINGS_SOURCE,
				...newSource,
			};

			deepFreeze( action );

			const expectedState = {
				...initialState,
				sources: {
					...initialState.sources,
					'namespace/new-source-name': {
						label: 'A new source',
						newAttribute: 'newValue',
					},
				},
			};

			const resultState = reducer( initialState, action );
			expect( resultState ).toEqual( expectedState );
		} );

		it( 'should not modify state if action type does not match', () => {
			const initialState = {
				sources: {
					'namespace/old-source-name': {
						label: 'An old source',
						anotherAttribute: 'someValue',
					},
				},
			};
			deepFreeze( initialState );

			const action = {
				type: 'UNKNOWN_ACTION',
				name: 'namespace/new-source',
			};
			deepFreeze( action );

			const resultState = reducer( initialState, action );
			expect( resultState ).toEqual( initialState );
		} );
	} );
} );
