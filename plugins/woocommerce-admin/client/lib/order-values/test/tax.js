/**
 * Internal dependencies
 */
import {
	getOrderDiscountTax,
	getOrderFeeTax,
	getOrderFeeTotalTax,
	getOrderLineItemTax,
	getOrderShippingTax,
	getOrderSubtotalTax,
	getOrderTotalTax,
} from '../tax';
import orderWithTax from './fixtures/order';
import orderWithoutTax from './fixtures/order-no-tax';
import orderWithCoupons from './fixtures/order-with-coupons';

describe( 'getOrderDiscountTax', () => {
	test( 'should get the correct tax amount', () => {
		expect( getOrderDiscountTax( orderWithTax ) ).toBe( 0.95 );
	} );

	test( 'should get the correct tax amount with multiple coupons', () => {
		expect( getOrderDiscountTax( orderWithCoupons ) ).toBe( 1.42 );
	} );

	test( 'should return 0 if there is no tax', () => {
		expect( getOrderDiscountTax( orderWithoutTax ) ).toBe( 0 );
	} );

	test( 'should return 0 if the order is malformed', () => {
		expect( getOrderDiscountTax( {} ) ).toBe( 0 );
	} );
} );

describe( 'getOrderFeeTax', () => {
	test( 'should get the correct tax amount', () => {
		expect( getOrderFeeTax( orderWithTax, 48 ) ).toBe( 0.1262 );
	} );

	test( 'should get the correct tax amount with multiple fees', () => {
		expect( getOrderFeeTax( orderWithCoupons, 41 ) ).toBe( 0.625 );
	} );

	test( 'should return 0 if there is no tax', () => {
		expect( getOrderFeeTax( orderWithoutTax, 2 ) ).toBe( 0 );
	} );

	test( 'should return 0 if there is no fee with that ID', () => {
		expect( getOrderFeeTax( orderWithoutTax, 50 ) ).toBe( 0 );
	} );
} );

describe( 'getOrderFeeTotalTax', () => {
	test( 'should get the correct tax amount', () => {
		expect( getOrderFeeTotalTax( orderWithTax ) ).toBe( 0.1262 );
	} );

	test( 'should get the correct tax amount with multiple fees', () => {
		expect( getOrderFeeTotalTax( orderWithCoupons ) ).toBe( 0.9375 );
	} );

	test( 'should return 0 if there is no tax', () => {
		expect( getOrderFeeTotalTax( orderWithoutTax ) ).toBe( 0 );
	} );
} );

describe( 'getOrderLineItemTax', () => {
	test( 'should get the correct tax amount', () => {
		expect( getOrderLineItemTax( orderWithTax, 15 ) ).toBe( 5.3964 );
	} );

	test( 'should get the correct tax amount for the second item', () => {
		expect( getOrderLineItemTax( orderWithTax, 19 ) ).toBe( 1.1424 );
	} );

	test( 'should return 0 if there is no tax', () => {
		expect( getOrderLineItemTax( orderWithoutTax, 1 ) ).toBe( 0 );
	} );

	test( 'should get the correct tax amount for an item with multiple coupons', () => {
		expect( getOrderLineItemTax( orderWithCoupons, 27 ) ).toBe( 2.3109 );
	} );
} );

describe( 'getOrderShippingTax', () => {
	test( 'should get the correct tax amount', () => {
		expect( getOrderShippingTax( orderWithTax ) ).toBe( 0.635 );
	} );

	test( 'should return 0 if there is no tax', () => {
		expect( getOrderShippingTax( orderWithoutTax ) ).toBe( 0 );
	} );

	test( 'should get the correct tax amount with multiple coupons', () => {
		expect( getOrderShippingTax( orderWithCoupons ) ).toBe( 0.635 );
	} );
} );

describe( 'getOrderSubtotalTax', () => {
	test( 'should get the correct tax amount', () => {
		expect( getOrderSubtotalTax( orderWithTax ) ).toBe( 6.5388 );
	} );

	test( 'should return 0 if there is no tax', () => {
		expect( getOrderSubtotalTax( orderWithoutTax ) ).toBe( 0 );
	} );

	test( 'should get the correct tax amount with multiple coupons', () => {
		expect( getOrderSubtotalTax( orderWithCoupons ) ).toBe( 3.7893 );
	} );
} );

describe( 'getOrderTotalTax', () => {
	test( 'should get the correct tax amount', () => {
		expect( getOrderTotalTax( orderWithTax ) ).toBe( 7.3 );
	} );

	test( 'should return 0 if there is no tax', () => {
		expect( getOrderTotalTax( orderWithoutTax ) ).toBe( 0 );
	} );

	test( 'should get the correct tax amount with multiple coupons', () => {
		expect( getOrderTotalTax( orderWithCoupons ) ).toBe( 5.3618 );
	} );
} );
