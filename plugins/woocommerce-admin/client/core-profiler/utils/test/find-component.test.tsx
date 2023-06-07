/**
 * Internal dependencies
 */
import { findComponentMeta, ComponentProps } from '../find-component';

describe( 'findComponentMeta', () => {
	it( 'should return the whole object once "component" key is found in a nested object', () => {
		const obj: Record< string, unknown > = {
			'coreProfiler.skipGuidedSetup.skipFlowBusinessLocation': {
				component: ( props: ComponentProps ) => (
					<div>{ props.context }</div>
				),
				progress: 50,
			},
		};

		const result = findComponentMeta( obj );
		expect( result ).toEqual( {
			component: expect.any( Function ),
			progress: 50,
		} );
	} );

	it( 'should return undefined if no "component" key is present', () => {
		const obj: Record< string, unknown > = {
			a: 1,
			b: {
				key: 'value',
			},
			c: 2,
		};

		const result = findComponentMeta( obj );
		expect( result ).toBeUndefined();
	} );

	it( 'should handle deeply nested objects', () => {
		const obj: Record< string, unknown > = {
			a: 1,
			b: {
				key: 'value',
				nested: {
					anotherKey: 'anotherValue',
					component: ( props: ComponentProps ) => (
						<div>{ props.context }</div>
					),
					progress: 100,
				},
			},
			c: 2,
		};

		const result = findComponentMeta( obj );
		expect( result ).toEqual( {
			anotherKey: 'anotherValue',
			component: expect.any( Function ),
			progress: 100,
		} );
	} );
} );
