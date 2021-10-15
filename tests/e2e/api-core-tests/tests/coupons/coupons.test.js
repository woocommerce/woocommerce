const { couponsApi } = require('../../endpoints/coupons');
const { coupon } = require('../../data');

/**
 * New coupon details
 */
const updatedCouponDetails = {
	description: '10% off storewide',
	maximum_amount: '500.00',
	usage_limit_per_user: 1,
	free_shipping: true,
};

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

		// Validate the created coupon object has the correct code, amount, and discount type
		expect(response.body).toEqual(
			expect.objectContaining({
				code: coupon.code,
				amount: Number(coupon.amount).toFixed(2),
				discount_type: coupon.discount_type,
			})
		);
	});

	it('can retrieve a coupon', async () => {
		const response = await couponsApi.retrieve.coupon(couponId);
		expect(response.statusCode).toEqual(couponsApi.retrieve.responseCode);
		expect(response.body.id).toEqual(couponId);
	});

	// mytodo turn this into a describe
	// mytodo test all list parameters
	it('can list all coupons', async () => {
		const response = await couponsApi.listAll.coupons();
		expect(response.statusCode).toEqual(couponsApi.listAll.responseCode);
		expect(response.body).toHaveLength(1);
	});

	it('can update a coupon', async () => {
		const response = await couponsApi.update.coupon(
			couponId,
			updatedCouponDetails
		);
		expect(response.statusCode).toEqual(couponsApi.update.responseCode);
		expect(response.body).toEqual(
			expect.objectContaining(updatedCouponDetails)
		);
	});

	it('can permanently delete a coupon', async () => {
		const response = await couponsApi.delete.coupon(couponId, true);

		expect(response.statusCode).toEqual(couponsApi.delete.responseCode);

		const getCouponResponse = await couponsApi.retrieve.coupon(couponId);
		expect(getCouponResponse.statusCode).toEqual(404);
	});

	// mytodo batch create, update, delete
});
