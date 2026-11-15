/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import {
	getActivityPanelCounts,
	getActivityPanelCountsError,
} from '../selectors';

describe( 'activity-panel selectors', () => {
	it( 'getActivityPanelCounts returns the counts from state', () => {
		const counts = {
			orders_to_fulfill_count: 2,
			reviews_to_moderate_count: 1,
			products_low_in_stock_count: 3,
		};

		expect( getActivityPanelCounts( { counts } ) ).toEqual( counts );
	} );

	it( 'getActivityPanelCounts returns undefined when not yet resolved', () => {
		expect( getActivityPanelCounts( {} ) ).toBeUndefined();
	} );

	it( 'getActivityPanelCountsError returns the error from state', () => {
		expect( getActivityPanelCountsError( { error: 'boom' } ) ).toBe(
			'boom'
		);
	} );
} );
