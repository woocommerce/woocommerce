/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { getAllBindingsSources, getBindingsSource } from '../selectors';

describe( 'selectors', () => {
	describe( 'getAllBindingsSources', () => {
		it( 'should return all registered bindings sources', () => {
			const state = {
				sources: {
					'namespace/source-name_1': {
						name: 'namespace/source-name_1',
						label: 'The first source handler of the namespace',
						anotherAttribute: 'anotherValue',
					},
					'namespace/source-name_2': {
						name: 'namespace/source-name_2',
						label: 'The second source handler of the namespace',
						anotherAttribute: 'anotherValue',
					},
				},
			};
			deepFreeze( state );

			const result = getAllBindingsSources( state );
			expect( result ).toEqual( state.sources );
		} );
	} );

	describe( 'getBindingsSource', () => {
		it( 'should return a specific bindings source given its name', () => {
			const state = {
				sources: {
					'namespace/source-name_1': {
						name: 'namespace/source-name_1',
						label: 'The first source handler of the namespace',
						anotherAttribute: 'anotherValue',
					},
					'namespace/source-name_2': {
						name: 'namespace/source-name_2',
						label: 'The second source handler of the namespace',
						anotherAttribute: 'anotherValue',
					},
				},
			};
			deepFreeze( state );

			const result = getBindingsSource(
				state,
				'namespace/source-name_2'
			);
			expect( result ).toEqual(
				state.sources[ 'namespace/source-name_2' ]
			);
		} );

		it( 'should return undefined if the bindings source does not exist', () => {
			const state = {
				sources: {
					sources: {
						'namespace/source-name_1': {
							name: 'namespace/source-name_1',
							label: 'The first source handler of the namespace',
							anotherAttribute: 'anotherValue',
						},
						'namespace/source-name_2': {
							name: 'namespace/source-name_2',
							label: 'The second source handler of the namespace',
							anotherAttribute: 'anotherValue',
						},
					},
				},
			};
			deepFreeze( state );

			const result = getBindingsSource(
				state,
				'namespace/source-name_3'
			);
			expect( result ).toBeUndefined();
		} );
	} );
} );
