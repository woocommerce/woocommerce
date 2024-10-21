/**
 * Internal dependencies
 */
import { getAllPanels } from '../panels';

describe( 'ActivityPanel', () => {
	it( 'should exclude the orders and stock panels when there are no orders', () => {
		const panels = getAllPanels( {
			unreadOrdersCount: 0,
			orderStatuses: [],
			totalOrderCount: 0,
			publishedProductCount: 1,
			manageStock: 'yes',
			isTaskListHidden: 'yes',
		} );

		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'orders-panel' } ),
			] )
		);
		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'stock-panel' } ),
			] )
		);
	} );

	it( 'should exclude the reviews and stock panels when there are no published products', () => {
		const panels = getAllPanels( {
			unreadOrdersCount: 0,
			orderStatuses: [],
			totalOrderCount: 1, // Yes, I realize this isn't "possible".
			publishedProductCount: 0,
			manageStock: 'yes',
			reviewsEnabled: 'yes',
			isTaskListHidden: 'yes',
		} );

		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'reviews-panel' } ),
			] )
		);
		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'stock-panel' } ),
			] )
		);
	} );

	it( 'should exclude any panel when the setup task list is visible', () => {
		const panels = getAllPanels( {
			unreadOrdersCount: 0,
			orderStatuses: [],
			totalOrderCount: 1,
			publishedProductCount: 0,
			manageStock: 'yes',
			reviewsEnabled: 'yes',
			isTaskListHidden: false,
		} );

		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'orders-panel' } ),
			] )
		);
		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'reviews-panel' } ),
			] )
		);
		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'stock-panel' } ),
			] )
		);
	} );

	it( 'should include the orders panel when there are orders', () => {
		const panels = getAllPanels( {
			unreadOrdersCount: 1,
			orderStatuses: [],
			totalOrderCount: 10,
			isTaskListHidden: 'yes',
		} );

		expect( panels ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( { id: 'orders-panel' } ),
			] )
		);
	} );

	it( 'should include the stock panel when there are orders, products, and inventory management is enabled', () => {
		const panels = getAllPanels( {
			unreadOrdersCount: 1,
			orderStatuses: [],
			totalOrderCount: 10,
			publishedProductCount: 2,
			manageStock: 'yes',
			isTaskListHidden: 'yes',
		} );

		expect( panels ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( { id: 'stock-panel' } ),
			] )
		);
	} );

	it( 'should exclude the reviews panel when there are no reviews', () => {
		const panels = getAllPanels( {
			publishedProductCount: 5,
			reviewsEnabled: 'yes',
			isTaskListHidden: 'yes',
		} );

		expect( panels ).toEqual(
			expect.not.arrayContaining( [
				expect.objectContaining( { id: 'reviews-panel' } ),
			] )
		);
	} );

	it( 'should include the reviews panel when they are enabled, there are products and reviews', () => {
		const panels = getAllPanels( {
			publishedProductCount: 5,
			reviewsEnabled: 'yes',
			isTaskListHidden: 'yes',
			unapprovedReviewsCount: 3,
		} );

		expect( panels ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( { id: 'reviews-panel' } ),
			] )
		);
	} );
} );
