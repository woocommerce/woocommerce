/**
 * Internal dependencies
 */
import { findComponentMeta } from '../find-component';

type ComponentProps = {
	context: string;
};

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

	it( 'should return undefined if there are circular references and no component', () => {
		const objA: Record< string, unknown > = {};
		const objB: Record< string, unknown > = { other: objA };
		objA.self = objA; // Creating a cyclic reference within objA
		objA.another = objB; // Adding another object to traverse

		const result = findComponentMeta( objA );
		expect( result ).toBeUndefined();
	} );

	it( 'should work correctly with mixed types', () => {
		const mixedObject = {
			number: 42,
			string: 'test',
			array: [ 1, 2, 3 ],
			nested: { component: 'found' },
		};

		const result = findComponentMeta( mixedObject );
		expect( result ).toEqual( { component: 'found' } );
	} );

	it( 'should work correctly with null and undefined values', () => {
		const objectWithNullAndUndefined = {
			key1: null,
			key2: undefined,
			nested: { component: 'found' },
		};

		const result = findComponentMeta( objectWithNullAndUndefined );
		expect( result ).toEqual( { component: 'found' } );
	} );

	it( 'should return the first component meta even if there are deeper ones', () => {
		const sparseComponentObject = {
			level1: {
				component: 'found',
				level2: {
					level3: { component: 'not this one' },
					otherLevel3: { component: 'not this one either' },
				},
			},
		};

		const result = findComponentMeta( sparseComponentObject );
		expect( result ).toEqual( {
			component: 'found',
			level2: {
				level3: { component: 'not this one' },
				otherLevel3: { component: 'not this one either' },
			},
		} );
	} );
} );
