/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

describe( 'activity-panel reducer', () => {
	it( 'should return a default state', () => {
		// @ts-expect-error reducer action should not be empty but it is
		const state = reducer( undefined, {} );
		expect( state ).toEqual( {} );
	} );

	it( 'should handle GET_ACTIVITY_PANEL_COUNTS_SUCCESS', () => {
		const counts = {
			orders_to_fulfill_count: 2,
			reviews_to_moderate_count: 1,
			products_low_in_stock_count: 3,
		};
		const state = reducer(
			{},
			{
				type: TYPES.GET_ACTIVITY_PANEL_COUNTS_SUCCESS,
				counts,
			}
		);

		expect( state.counts ).toEqual( counts );
	} );

	it( 'should handle GET_ACTIVITY_PANEL_COUNTS_ERROR', () => {
		const state = reducer(
			{},
			{
				type: TYPES.GET_ACTIVITY_PANEL_COUNTS_ERROR,
				error: 'Something went wrong',
			}
		);

		expect( state.error ).toBe( 'Something went wrong' );
	} );
} );
