/* eslint-disable */ 
const { test, expect } = require('@playwright/test');
const { couponsApi } = require( '../../endpoints/coupons' );
//const { ordersApi } = require( '../../endpoints/orders' );
const { coupon } = require( '../../data' );

/**
 * Tests for the WooCommerce Coupons API.
 */
test.describe( 'Coupons API tests', () => {
	let couponId;

	test( 'can create a coupon', async ({request}) => {
		const testCoupon = {
			...coupon,
			code: `${ coupon.code }-${ Date.now() }`,
		};
		
		const response = await request.post( '/wp-json/wc/v3/coupons', {
			data: testCoupon
		});

		expect( response.status() ).toEqual( couponsApi.create.responseCode );
		expect( JSON.parse((await response.body()).toString())['id'] ).toBeDefined();
		
		couponId = JSON.parse((await response.body()).toString())['id'];

		expect( JSON.parse((await response.body()).toString()) ).toEqual(
			expect.objectContaining( {
				code: testCoupon.code,
				amount: Number( coupon.amount ).toFixed( 2 ),
				discount_type: coupon.discount_type,
			} )
		);
	} );

	test( 'can retrieve a coupon', async ({request}) => {
		const response = await request.get(`/wp-json/wc/v3/coupons/${ couponId }`);
		expect( response.status() ).toEqual(
			couponsApi.retrieve.responseCode
		);
		expect( JSON.parse((await response.body()).toString())['id'] ).toEqual( couponId );
	} );

	test( 'can update a coupon', async ({request}) => {
		const updatedCouponDetails = {
			description: '10% off storewide',
			maximum_amount: '500.00',
			usage_limit_per_user: 1,
			free_shipping: true,
		};

		const response = await request.post(`/wp-json/wc/v3/coupons/${ couponId }`,{
			data: updatedCouponDetails
		});
		expect( response.status() ).toEqual( couponsApi.update.responseCode );
		expect( JSON.parse((await response.body()).toString())).toEqual(
			expect.objectContaining( updatedCouponDetails )
		);
	} );

	test( 'can permanently delete a coupon', async ({request}) => {
		//console.log("couponId",couponId);
		//const response = await couponsApi.delete.coupon( couponId, true );
		const response = await request.delete(`/wp-json/wc/v3/coupons/${ couponId }`,{
			data:{force:true}
		});

		expect( response.status() ).toEqual( couponsApi.delete.responseCode );

		//const getCouponResponse = await couponsApi.retrieve.coupon( couponId );
		const getCouponResponse = await request.get(`/wp-json/wc/v3/coupons/${ couponId }`);
		
		expect( getCouponResponse.status() ).toEqual( 404 );
	} );

} );
