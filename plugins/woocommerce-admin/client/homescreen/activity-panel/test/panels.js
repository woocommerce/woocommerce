/**
 * Internal dependencies
 */
import { getAllPanels } from '../panels';

describe( 'ActivityPanel', () => {
	it( 'should exclude the orders panel when there are no orders', () => {
		const panels = getAllPanels( {
			countUnreadOrders: 0,
			orderStatuses: [],
			totalOrderCount: 0,
		} );

		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'orders-panel' } ),
			] )
		);
	} );

	it( 'should include the orders panel when there are orders', () => {
		const panels = getAllPanels( {
			countUnreadOrders: 1,
			orderStatuses: [],
			totalOrderCount: 10,
		} );

		expect( panels ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( { id: 'orders-panel' } ),
			] )
		);
	} );
} );
