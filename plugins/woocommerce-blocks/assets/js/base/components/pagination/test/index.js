/**
 * Internal dependencies
 */
import { getIndexes } from '../utils.js';

describe( 'getIndexes', () => {
	describe( 'when on the first page', () => {
		test( 'indexes include the first pages available', () => {
			expect( getIndexes( 5, 1, 100 ) ).toEqual( { minIndex: 1, maxIndex: 5 } );
		} );

		test( 'indexes include the only available page if there is only one', () => {
			expect( getIndexes( 5, 1, 1 ) ).toEqual( { minIndex: 1, maxIndex: 1 } );
		} );
	} );

	describe( 'when on a page in the middle', () => {
		test( 'indexes include pages before and after the current page', () => {
			expect( getIndexes( 5, 50, 100 ) ).toEqual( { minIndex: 48, maxIndex: 52 } );
		} );
	} );

	describe( 'when on the last page', () => {
		test( 'indexes include the last pages available', () => {
			expect( getIndexes( 5, 100, 100 ) ).toEqual( { minIndex: 96, maxIndex: 100 } );
		} );
	} );
} );
