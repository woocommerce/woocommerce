const { couponsApi } = require('../../endpoints/coupons');
const { coupon } = require('../../data');
const { batch } = require('../../data/shared');

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

	it('can batch update coupons', async () => {
		const couponToBeDeleted = {
			code: 'BATCH10OFF',
			discount_type: 'percent',
			amount: '10.00',
		};
		const batchUpdatePayload = {
			create: [
				{
					code: 'BATCH20OFF',
					discount_type: 'percent',
					amount: '20',
					individual_use: true,
					exclude_sale_items: true,
					minimum_amount: '100.00',
				},
				{
					code: 'BATCH30OFF',
					discount_type: 'percent',
					amount: '30',
					individual_use: true,
					exclude_sale_items: true,
					minimum_amount: '100.00',
				},
			],
			update: [{ id: couponId, free_shipping: true }],
			delete: [],
		};

		// Create a coupon that will be deleted later through the batch update endpoint
		const singleCreateResponse = await couponsApi.create.coupon(
			couponToBeDeleted
		);
		const couponIdToBeDeleted = singleCreateResponse.body.id;
		batchUpdatePayload.delete.push(couponIdToBeDeleted);

		// Use the Batch Update endpoint to create 2 new coupons, update 1 coupon to free shipping, and delete 1 coupon.
		const batchUpdateResponse = await couponsApi.batch.coupons(
			batchUpdatePayload
		);
		expect(batchUpdateResponse.status).toEqual(
			couponsApi.batch.responseCode
		);

		// Verify that the 2 new coupons were created
		const createdCoupons = batchUpdateResponse.create;
		expect(batchUpdateResponse.body.create).toHaveLength(
			batchUpdatePayload.create.length
		);
		for (let i = 0; i < createdCoupons.length; i++) {
			const { id, code } = createdCoupons[i];
			const expectedCode = batchUpdatePayload.create[i].code;

			expect(id).toBeDefined();
			expect(code).toEqual(expectedCode);
		}

		// Verify that 1 coupon was updated to free shipping
		const updatedCoupons = batchUpdateResponse.body.update;
		expect(updatedCoupons).toHaveLength(batchUpdatePayload.update.length);
		expect(updatedCoupons[0].id).toEqual(couponId);
		expect(updatedCoupons[0].free_shipping).toEqual(true);

		// Verify that the expected coupon was deleted
		const deletedCoupons = batchUpdateResponse.body.delete;
		const getDeletedCouponResponse = await couponsApi.retrieve.coupon(
			couponIdToBeDeleted
		);
		expect(deletedCoupons).toHaveLength(batchUpdatePayload.delete.length);
		expect(updatedCoupons[0].id).toEqual(couponIdToBeDeleted);
		expect(getDeletedCouponResponse.status).toEqual(404);

		// Cleanup the 2 created coupons by the Batch Update Endpoint
		const cleanupPayload = { delete: createdCoupons.map(({ id }) => id) };
		await couponsApi.batch.coupons(cleanupPayload);
	});

	it('can permanently delete a coupon', async () => {
		const response = await couponsApi.delete.coupon(couponId, true);

		expect(response.statusCode).toEqual(couponsApi.delete.responseCode);

		const getCouponResponse = await couponsApi.retrieve.coupon(couponId);
		expect(getCouponResponse.statusCode).toEqual(404);
	});

	describe('List all coupons', () => {
		// mytodo test all list parameters

		it('can list all coupons', async () => {
			const response = await couponsApi.listAll.coupons();
			expect(response.statusCode).toEqual(
				couponsApi.listAll.responseCode
			);
			expect(response.body).toHaveLength(1);
		});
	});
});
