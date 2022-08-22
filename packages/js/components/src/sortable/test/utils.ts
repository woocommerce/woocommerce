/**
 * External dependencies
 */
import { DragEvent } from 'react';

/**
 * Internal dependencies
 */
import { moveIndex, isUpperHalf } from '../utils';

describe( 'utils', () => {
	it( 'should move the from index to a higher index', () => {
		const arr = [ 'apple', 'orange', 'banana' ];
		const newArr = moveIndex( 0, 2, arr );
		expect( newArr ).toEqual( [ 'orange', 'apple', 'banana' ] );
	} );

	it( 'should move the from index to the last index', () => {
		const arr = [ 'apple', 'orange', 'banana' ];
		const newArr = moveIndex( 0, 3, arr );
		expect( newArr ).toEqual( [ 'orange', 'banana', 'apple' ] );
	} );

	it( 'should move the from index to a lower index', () => {
		const arr = [ 'apple', 'orange', 'banana' ];
		const newArr = moveIndex( 2, 0, arr );
		expect( newArr ).toEqual( [ 'banana', 'apple', 'orange' ] );
	} );

	it( 'should return true when the cursor is in the upper half of an element', () => {
		const event = {
			clientY: 0,
			target: {
				offsetHeight: 100,
				getBoundingClientRect: () => ( {
					top: 0,
				} ),
			},
		} as unknown;
		expect(
			isUpperHalf( event as DragEvent< HTMLLIElement > )
		).toBeTruthy();
	} );

	it( 'should return true when the element is placed lower in the page', () => {
		const event = {
			clientY: 0,
			target: {
				offsetHeight: 100,
				getBoundingClientRect: () => ( {
					top: 70,
				} ),
			},
		} as unknown;
		expect(
			isUpperHalf( event as DragEvent< HTMLLIElement > )
		).toBeTruthy();
	} );

	it( 'should return false when the cursor is more than half way down', () => {
		const event = {
			clientY: 60,
			target: {
				offsetHeight: 100,
				getBoundingClientRect: () => ( {
					top: 0,
				} ),
			},
		} as unknown;
		expect(
			isUpperHalf( event as DragEvent< HTMLLIElement > )
		).toBeFalsy();
	} );

	it( 'should return false when the element is lower in the page', () => {
		const event = {
			clientY: 152,
			target: {
				offsetHeight: 100,
				getBoundingClientRect: () => ( {
					top: 100,
				} ),
			},
		} as unknown;
		expect(
			isUpperHalf( event as DragEvent< HTMLLIElement > )
		).toBeFalsy();
	} );
} );
