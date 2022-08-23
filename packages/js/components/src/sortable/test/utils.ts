/**
 * External dependencies
 */
import { DragEvent } from 'react';

/**
 * Internal dependencies
 */
import {
	moveIndex,
	isBefore,
	isDraggingOverAfter,
	isDraggingOverBefore,
	isLastDroppable,
} from '../utils';

describe( 'moveIndex', () => {
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
} );

describe( 'isBefore', () => {
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
		expect( isBefore( event as DragEvent< HTMLLIElement > ) ).toBeTruthy();
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
		expect( isBefore( event as DragEvent< HTMLLIElement > ) ).toBeTruthy();
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
		expect( isBefore( event as DragEvent< HTMLLIElement > ) ).toBeFalsy();
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
		expect( isBefore( event as DragEvent< HTMLLIElement > ) ).toBeFalsy();
	} );

	it( 'should return false when the cursor is over the right half of the element', () => {
		const event = {
			clientX: 152,
			target: {
				offsetWidth: 100,
				getBoundingClientRect: () => ( {
					left: 0,
				} ),
			},
		} as unknown;
		expect(
			isBefore( event as DragEvent< HTMLLIElement >, true )
		).toBeFalsy();
	} );

	it( 'should return true when the cursor is over the left half of the element', () => {
		const event = {
			clientX: 27,
			target: {
				offsetWidth: 100,
				getBoundingClientRect: () => ( {
					left: 0,
				} ),
			},
		} as unknown;
		expect(
			isBefore( event as DragEvent< HTMLLIElement >, true )
		).toBeTruthy();
	} );

	it( 'should return true when the element is further right on the page', () => {
		const event = {
			clientX: 150,
			target: {
				offsetWidth: 100,
				getBoundingClientRect: () => ( {
					left: 150,
				} ),
			},
		} as unknown;
		expect(
			isBefore( event as DragEvent< HTMLLIElement >, true )
		).toBeTruthy();
	} );
} );

describe( 'isDraggingOverAfter', () => {
	it( 'should return true when the drop index is immediately after this item', () => {
		expect( isDraggingOverAfter( 0, 1, 1 ) ).toBeTruthy();
	} );

	it( 'should return false when the drop index is not immediately after', () => {
		expect( isDraggingOverAfter( 0, 5, 2 ) ).toBeFalsy();
	} );

	it( 'should return false when the item is being dragged', () => {
		expect( isDraggingOverAfter( 2, 2, 3 ) ).toBeFalsy();
	} );

	it( 'should return true when the item after is being dragged and the drop index is immediately after', () => {
		expect( isDraggingOverAfter( 3, 4, 5 ) ).toBeTruthy();
	} );
} );

describe( 'isDraggingOverBefore', () => {
	it( 'should return true when the item is the same as the drop index', () => {
		expect( isDraggingOverBefore( 0, 1, 0 ) ).toBeTruthy();
	} );

	it( 'should return false when the item is being dragged', () => {
		expect( isDraggingOverBefore( 1, 1, 1 ) ).toBeFalsy();
	} );

	it( 'should return false when the drop index is different', () => {
		expect( isDraggingOverBefore( 2, 1, 5 ) ).toBeFalsy();
	} );

	it( 'should return true when the item before is being dragged and is also the drop index', () => {
		expect( isDraggingOverBefore( 3, 2, 2 ) ).toBeTruthy();
	} );
} );

describe( 'isLastDroppable', () => {
	it( 'should return false when the item is being dragged', () => {
		expect( isLastDroppable( 1, 1, 2 ) ).toBeFalsy();
	} );

	it( 'should return true when the index is the last item', () => {
		expect( isLastDroppable( 4, 1, 5 ) ).toBeTruthy();
	} );

	it( 'should return true on the second to last item when the last item is being dragged', () => {
		expect( isLastDroppable( 3, 4, 5 ) ).toBeTruthy();
	} );
} );
