const { couponsApi } = require('../../endpoints/coupons');
const { coupon } = require('../../data');

/**
 * Tests for the WooCommerce Coupons API.
 *
 * @group api
 * @group coupons
 *
 */
describe('Coupons API tests', () => {
	let couponId;

	it('can create a coupon', async () => {
		const response = await couponsApi.create.coupon(coupon);

		expect(response.statusCode).toEqual(couponsApi.create.responseCode);
		expect(response.body.id).toBeDefined();
		couponId = response.body.id;

		// Validate the created coupon object has the correct values
		expect(response.body.code).toEqual(coupon.code);
		expect(response.body.amount).toEqual(Number(coupon.amount).toFixed(2));
		expect(response.body.discount_type).toEqual(coupon.discount_type);
	});

	it('can retrieve a coupon', async () => {
		const response = await couponsApi.retrieve.coupon( couponId );
		expect( response.statusCode ).toEqual( couponsApi.retrieve.responseCode );
		expect( response.body.id ).toEqual( couponId );
	});

	it('can permanently delete a coupon', async () => {
		const response = await couponsApi.delete.coupon(couponId, true);

		expect(response.statusCode).toEqual(couponsApi.delete.responseCode);

		const getCouponResponse = await couponsApi.retrieve.coupon(couponId);
		expect(getCouponResponse.statusCode).toEqual(404);
	});

	

	// it('can add shipping and billing contacts to an order', async () => {
	// 	// Update the billing and shipping fields on the order
	// 	order.billing = updatedCustomerBilling;
	// 	order.shipping = updatedCustomerShipping;

	// 	const response = await couponsApi.update.coupon( couponId, order );
	// 	expect( response.statusCode).toEqual( couponsApi.update.responseCode );

	// 	expect( response.body.billing ).toEqual( updatedCustomerBilling );
	// 	expect( response.body.shipping ).toEqual( updatedCustomerShipping );
	// });
});
