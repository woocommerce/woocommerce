/**
 * Internal dependencies
 */
import { taskSort } from '../tasks';

describe( 'task sort', () => {
	const list = [
		{ key: 'comp', completed: true },
		{ key: 'comp-l1', completed: true, level: 1 },
		{ key: 'comp-l2', completed: true, level: 2 },
		{ key: 'uncomp-l3', completed: false, level: 3 },
		{ key: 'uncomp', completed: false },
		{ key: 'uncomp-l2', completed: false, level: 2 },
		{ key: 'uncomp-l1', completed: false, level: 1 },
	];
	it( 'should put all l1 levels at the top if not completed', () => {
		const sorted = [ ...list ].sort( taskSort );
		expect( sorted[ 0 ].key ).toEqual( 'uncomp-l1' );
		expect( sorted[ 1 ].key ).toEqual( 'uncomp-l2' );
		expect( sorted[ 2 ].key ).toEqual( 'uncomp-l3' );
		expect( sorted[ 3 ].key ).toEqual( 'uncomp' );
	} );

	it( 'should put all completed items at the bottom', () => {
		const sorted = [ ...list, { key: 'test', completed: true } ].sort(
			taskSort
		);
		for ( let i = 1; i < 5; i++ ) {
			expect( sorted[ sorted.length - i ].completed ).toEqual( true );
		}
	} );
} );
