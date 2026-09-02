/**
 * Internal dependencies
 */
import { test, expect } from '../../../fixtures/api-tests-fixtures';
import { order } from '../../../data';

test.describe( 'Add coupon to order', () => {
	const testCoupon = {
		code: `coupon-${ Date.now() }`,
		discount_type: 'percent',
		amount: '10',
	};
	let orderId: number;

	test.beforeAll( async ( { request } ) => {
		// Create a coupon
		const createCouponResponse = await request.post(
			'./wp-json/wc/v3/coupons/',
			{
				data: testCoupon,
			}
		);
		const createCouponResponseJSON = await createCouponResponse.json();
		testCoupon.id = createCouponResponseJSON.id;
	} );

	// Clean up created coupon and order
	test.afterAll( async ( { request } ) => {
		await request.delete( `./wp-json/wc/v3/coupons/${ testCoupon.id }`, {
			data: { force: true },
		} );
		await request.delete( `./wp-json/wc/v3/orders/${ orderId }`, {
			data: { force: true },
		} );
	} );

	test( 'can add coupon to an order', async ( { request } ) => {
		const orderWithCoupon = {
			...order,
			coupon_lines: [ { code: testCoupon.code } ],
		};

		const response = await request.post( './wp-json/wc/v3/orders', {
			data: orderWithCoupon,
		} );
		const responseJSON = await response.json();
		orderId = responseJSON.id;

		expect( response.status() ).toEqual( 201 );
		expect( responseJSON.coupon_lines ).toHaveLength( 1 );
		expect( responseJSON.coupon_lines[ 0 ].code ).toEqual(
			testCoupon.code
		);
		// Test that the coupon meta data exists.
		// See: https://github.com/woocommerce/woocommerce/issues/28166.
		expect( responseJSON.coupon_lines[ 0 ].meta_data ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					key: 'coupon_info',
					value: `[${ testCoupon.id },"${ testCoupon.code }","${ testCoupon.discount_type }",${ testCoupon.amount }]`,
				} ),
			] )
		);
	} );
} );
