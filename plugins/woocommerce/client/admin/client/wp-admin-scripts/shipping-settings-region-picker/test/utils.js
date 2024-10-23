/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
/**
 * Internal dependencies
 */
import { recursivelyTransformLabels } from '../utils';

describe( 'recursivelyTransformLabels', () => {
	function toUpperCase( label ) {
		return label.toUpperCase();
	}

	test( 'Single node with label', () => {
		const node = { label: 'test' };
		const result = recursivelyTransformLabels( node, toUpperCase );
		expect( result.label ).toBe( 'TEST' );
	} );

	test( 'Single node without label', () => {
		const node = { value: 42 };
		const result = recursivelyTransformLabels( node, toUpperCase );
		expect( result.value ).toBe( 42 );
		expect( result.label ).toBeUndefined();
	} );

	test( 'Nested nodes', () => {
		const node = {
			label: 'parent',
			value: 'par',
			children: [
				{ label: 'child1', value: 'ch1' },
				{ label: 'child2', value: 'ch2' },
			],
		};
		const result = recursivelyTransformLabels( node, toUpperCase );
		expect( result.label ).toBe( 'PARENT' );
		expect( result.value ).toBe( 'par' );
		expect( result.children[ 0 ].label ).toBe( 'CHILD1' );
		expect( result.children[ 0 ].value ).toBe( 'ch1' );
		expect( result.children[ 1 ].label ).toBe( 'CHILD2' );
		expect( result.children[ 1 ].value ).toBe( 'ch2' );
	} );

	test( 'Nested nodes with mixed children', () => {
		const node = {
			label: 'parent',
			children: [
				{ label: 'child1' },
				{ value: 42 },
				{ label: 'child2' },
			],
		};
		const result = recursivelyTransformLabels( node, toUpperCase );
		expect( result.label ).toBe( 'PARENT' );
		expect( result.children[ 0 ].label ).toBe( 'CHILD1' );
		expect( result.children[ 1 ].value ).toBe( 42 );
		expect( result.children[ 1 ].label ).toBeUndefined();
		expect( result.children[ 2 ].label ).toBe( 'CHILD2' );
	} );

	test( 'Array of nodes', () => {
		const node = [ { label: 'node1' }, { label: 'node2' } ];
		const result = recursivelyTransformLabels( node, toUpperCase );
		expect( result[ 0 ].label ).toBe( 'NODE1' );
		expect( result[ 1 ].label ).toBe( 'NODE2' );
	} );

	test( 'Deeply nested nodes', () => {
		const node = {
			label: 'root',
			children: [
				{
					label: 'branch1',
					value: 'br1',
					children: [
						{ label: 'leaf1', value: 'le1' },
						{ label: 'leaf2' },
					],
				},
				{ label: 'branch2' },
			],
		};
		const result = recursivelyTransformLabels( node, toUpperCase );
		expect( result.label ).toBe( 'ROOT' );
		expect( result.children[ 0 ].label ).toBe( 'BRANCH1' );
		expect( result.children[ 0 ].value ).toBe( 'br1' );
		expect( result.children[ 0 ].children[ 0 ].label ).toBe( 'LEAF1' );
		expect( result.children[ 0 ].children[ 0 ].value ).toBe( 'le1' );
		expect( result.children[ 0 ].children[ 1 ].label ).toBe( 'LEAF2' );
		expect( result.children[ 1 ].label ).toBe( 'BRANCH2' );
	} );
} );

describe( 'recursivelyTransformLabels and decodeEntities should work together', () => {
	test( 'Deeply nested nodes', () => {
		const node = {
			label: 'root',
			children: [
				{
					label: 'branch1',
					value: 'br1',
					children: [
						{ label: 'Cura&ccedil;ao', value: 'le1' },
						{ label: 'leaf2' },
					],
				},
				{ label: 'branch2' },
			],
		};
		const result = recursivelyTransformLabels( node, decodeEntities );
		expect( result.label ).toBe( 'root' );
		expect( result.children[ 0 ].label ).toBe( 'branch1' );
		expect( result.children[ 0 ].value ).toBe( 'br1' );
		expect( result.children[ 0 ].children[ 0 ].label ).toBe( 'Curaçao' );
		expect( result.children[ 0 ].children[ 0 ].value ).toBe( 'le1' );
		expect( result.children[ 0 ].children[ 1 ].label ).toBe( 'leaf2' );
		expect( result.children[ 1 ].label ).toBe( 'branch2' );
	} );
} );
