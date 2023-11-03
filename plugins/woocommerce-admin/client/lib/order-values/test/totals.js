/**
 * Internal dependencies
 */
import {
	getOrderDiscountTotal,
	getOrderFeeCost,
	getOrderFeeTotal,
	getOrderItemCost,
	getOrderRefundTotal,
	getOrderShippingTotal,
	getOrderSubtotal,
	getOrderTotal,
} from '../totals';
import orderWithTax from './fixtures/order';
import orderWithoutTax from './fixtures/order-no-tax';
import orderWithCoupons from './fixtures/order-with-coupons';
import orderWithRefunds from './fixtures/order-with-refunds';

describe( 'getOrderDiscountTotal', () => {
	it( 'should get the correct discount amount', () => {
		expect( getOrderDiscountTotal( orderWithRefunds ) ).toBe( 15 );
	} );

	it( 'should return 0 if there are no discounts', () => {
		expect( getOrderDiscountTotal( orderWithoutTax ) ).toBe( 0 );
	} );

	it( 'should get the correct discount amount with multiple coupons', () => {
		expect( getOrderDiscountTotal( orderWithCoupons ) ).toBe( 22.3 );
	} );
} );

describe( 'getOrderFeeCost', () => {
	test( 'should get the correct fee value', () => {
		expect( getOrderFeeCost( orderWithTax, 48 ) ).toBe( 1.53 );
	} );

	test( 'should get the correct tax amount with multiple fees', () => {
		expect( getOrderFeeCost( orderWithCoupons, 41 ) ).toBe( 10 );
	} );

	test( "should return 0 if this fee doesn't exist", () => {
		// orderWithRefunds has no fees
		expect( getOrderFeeCost( orderWithoutTax, 3 ) ).toBe( 0 );
	} );

	test( 'should return 0 if there are no fees', () => {
		// orderWithRefunds has no fees
		expect( getOrderFeeCost( orderWithRefunds, 2 ) ).toBe( 0 );
	} );
} );

describe( 'getOrderFeeTotal', () => {
	test( 'should get the correct fee total', () => {
		expect( getOrderFeeTotal( orderWithTax ) ).toBe( 1.53 );
	} );

	test( 'should get the correct fee total with multiple fees', () => {
		expect( getOrderFeeTotal( orderWithCoupons ) ).toBe( 15 );
	} );

	test( 'should return 0 if there are no fees', () => {
		// orderWithRefunds has no fees
		expect( getOrderFeeTotal( orderWithRefunds ) ).toBe( 0 );
	} );
} );

describe( 'getOrderItemCost', () => {
	it( 'should get the singular cost of an item', () => {
		expect( getOrderItemCost( orderWithTax, 19 ) ).toBe( 17.99 );
	} );

	it( 'should get the singular cost of an item, before discounts', () => {
		expect( getOrderItemCost( orderWithTax, 15 ) ).toBe( 49.99 );
	} );

	it( 'should get the singular cost of an item, even if quantity > 1', () => {
		expect( getOrderItemCost( orderWithCoupons, 26 ) ).toBe( 15.99 );
	} );

	it( 'should return 0 if this ID does not exist in line_items', () => {
		expect( getOrderItemCost( orderWithoutTax, 2 ) ).toBe( 0 );
	} );
} );

describe( 'getOrderRefundTotal', () => {
	it( 'should get the correct refund amount', () => {
		expect( getOrderRefundTotal( orderWithCoupons ) ).toBe( -10.0 );
	} );

	it( 'should return 0 if there are no refunds', () => {
		expect( getOrderRefundTotal( orderWithoutTax ) ).toBe( 0 );
	} );

	it( 'should get the correct refund amount with multiple refunds', () => {
		expect( getOrderRefundTotal( orderWithRefunds ) ).toBe( -25.0 );
	} );
} );

describe( 'getOrderShippingTotal', () => {
	it( 'should get the correct shipping amount', () => {
		expect( getOrderShippingTotal( orderWithTax ) ).toBe( 10 );
	} );

	it( 'should return 0 if there is no shipping', () => {
		expect( getOrderShippingTotal( orderWithoutTax ) ).toBe( 0 );
	} );
} );

describe( 'getOrderSubtotal', () => {
	it( 'should get the sum of line_item totals', () => {
		expect( getOrderSubtotal( orderWithTax ) ).toBe( 67.98 );
	} );

	it( 'should get the sum of line_item totals regardless of coupons', () => {
		expect( getOrderSubtotal( orderWithCoupons ) ).toBe( 81.97 );
	} );

	it( 'should return 0 if there are no line_items', () => {
		expect( getOrderSubtotal( { line_items: [] } ) ).toBe( 0 );
	} );
} );

describe( 'getOrderTotal', () => {
	it( 'should get the sum of line_item totals', () => {
		expect( getOrderTotal( orderWithTax ).toFixed( 2 ) ).toBe( '64.51' );
	} );

	it( 'should get the sum of line_item totals regardless of coupons', () => {
		expect( getOrderTotal( orderWithCoupons ).toFixed( 2 ) ).toBe(
			'74.67'
		);
	} );

	it( 'should return 0 if there is nothing in the order', () => {
		expect( getOrderTotal( { line_items: [] } ) ).toBe( 0 );
	} );
} );
