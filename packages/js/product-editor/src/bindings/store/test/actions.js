/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { ACTION_REGISTER_BINDINGS_SOURCE } from '../constants';
import { registerSourceHandler } from '../actions';

describe( 'actions', () => {
	describe( 'registerSourceHandler', () => {
		it( 'should return the ACTION_REGISTER_BINDINGS_SOURCE action', () => {
			const source = {
				name: 'namespace/source-name',
				label: 'The name source handler of the namespace',
				anotherAttribute: 'anotherValue',
			};

			deepFreeze( source );

			const result = registerSourceHandler( source );
			expect( result ).toEqual( {
				type: ACTION_REGISTER_BINDINGS_SOURCE,
				...source,
			} );
		} );
	} );
} );
