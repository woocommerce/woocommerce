const { couponsApi } = require('../../endpoints/coupons');
const { ordersApi } = require('../../endpoints/orders');
const { coupon, order } = require('../../data');

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
		const testCoupon = {
			...coupon,
			code: `${coupon.code}-${Date.now()}`,
		};
		const response = await couponsApi.create.coupon(testCoupon);

		expect(response.statusCode).toEqual(couponsApi.create.responseCode);
		expect(response.body.id).toBeDefined();
		couponId = response.body.id;

		// Validate the created coupon object has the correct code, amount, and discount type
		expect(response.body).toEqual(
			expect.objectContaining({
				code: testCoupon.code,
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
		const updatedCouponDetails = {
			description: '10% off storewide',
			maximum_amount: '500.00',
			usage_limit_per_user: 1,
			free_shipping: true,
		};
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

	describe('Batch update coupons', () => {
		/**
		 * Coupons to be created, updated, and deleted.
		 */
		const expectedCoupons = [
			{
				code: `batchcoupon-${Date.now()}`,
				discount_type: 'percent',
				amount: '10',
				free_shipping: false,
			},
			{
				code: `batchcoupon-${Date.now() + 1}`,
				discount_type: 'percent',
				amount: '20',
			},
		];

		it('can batch create coupons', async () => {
			// Batch create 2 new coupons.
			const batchCreatePayload = {
				create: expectedCoupons,
			};
			const batchCreateResponse = await couponsApi.batch.coupons(
				batchCreatePayload
			);
			expect(batchCreateResponse.status).toEqual(
				couponsApi.batch.responseCode
			);

			// Verify that the 2 new coupons were created
			const actualCoupons = batchCreateResponse.body.create;
			expect(actualCoupons).toHaveLength(expectedCoupons.length);
			for (let i = 0; i < actualCoupons.length; i++) {
				const { id, code } = actualCoupons[i];
				const expectedCouponCode = expectedCoupons[i].code;

				expect(id).toBeDefined();
				expect(code).toEqual(expectedCouponCode);

				// Save the coupon id
				expectedCoupons[i].id = id;
			}
		});

		it('can batch update coupons', async () => {
			// Update the 1st coupon to free shipping.
			// Update the amount of the 2nd coupon to 25.
			const batchUpdatePayload = {
				update: [
					{
						id: expectedCoupons[0].id,
						free_shipping: true,
					},
					{
						id: expectedCoupons[1].id,
						amount: '25.00',
					},
				],
			};
			const batchUpdateResponse = await couponsApi.batch.coupons(
				batchUpdatePayload
			);

			// Verify the response code and the number of coupons that were updated.
			const updatedCoupons = batchUpdateResponse.body.update;
			expect(batchUpdateResponse.status).toEqual(
				couponsApi.batch.responseCode
			);
			expect(updatedCoupons).toHaveLength(expectedCoupons.length);

			// Verify that the 1st coupon was updated to free shipping.
			expect(updatedCoupons[0].id).toEqual(expectedCoupons[0].id);
			expect(updatedCoupons[0].free_shipping).toEqual(true);

			// Verify that the amount of the 2nd coupon was updated to 25.
			expect(updatedCoupons[1].id).toEqual(expectedCoupons[1].id);
			expect(updatedCoupons[1].amount).toEqual('25.00');
		});

		it('can batch delete coupons', async () => {
			// Batch delete the 2 coupons.
			const couponIdsToDelete = expectedCoupons.map(({ id }) => id);
			const batchDeletePayload = {
				delete: couponIdsToDelete,
			};
			const batchDeleteResponse = await couponsApi.batch.coupons(
				batchDeletePayload
			);

			// Verify that the response shows the 2 coupons.
			const deletedCouponIds = batchDeleteResponse.body.delete.map(
				({ id }) => id
			);
			expect(batchDeleteResponse.status).toEqual(
				couponsApi.batch.responseCode
			);
			expect(deletedCouponIds).toEqual(couponIdsToDelete);

			// Verify that the 2 deleted coupons cannot be retrieved.
			for (const couponId of couponIdsToDelete) {
				const { status } = await couponsApi.retrieve.coupon(couponId);

				expect(status).toEqual(404);
			}
		});
	});

	describe('List coupons', () => {
		const allCoupons = [
			{
				...coupon,
				code: `listcoupons-01-${Date.now()}`,
				description: `description-01-${Date.now()}`,
			},
			{
				...coupon,
				code: `listcoupons-02-${Date.now()}`,
				description: `description-02-${Date.now()}`,
			},
			{
				...coupon,
				code: `listcoupons-03-${Date.now()}`,
				description: `description-03-${Date.now()}`,
			},
		];

		beforeAll(async () => {
			// Create list of coupons for testing.
			const response = await couponsApi.batch.coupons({
				create: allCoupons,
			});
			const actualCreatedCoupons = response.body.create;

			// Save their coupon ID's
			for (const coupon of allCoupons) {
				const { id } = actualCreatedCoupons.find(
					({ code }) => coupon.code === code
				);

				coupon.id = id;
			}
		});

		afterAll(async () => {
			// Clean up created coupons
			const batchDeletePayload = {
				delete: allCoupons.map(({ id }) => id),
			};
			await couponsApi.batch.coupons(batchDeletePayload);
		});

		it('can list all coupons by default', async () => {
			const response = await couponsApi.listAll.coupons();
			const listedCoupons = response.body;
			const actualCouponIdsList = listedCoupons.map(({ id }) => id);
			const expectedCouponIdsList = allCoupons.map(({ id }) => id);

			expect(response.status).toEqual(couponsApi.listAll.responseCode);
			expect(actualCouponIdsList).toEqual(
				expect.arrayContaining(expectedCouponIdsList)
			);
		});

		it('can limit result set to matching code', async () => {
			const matchingCoupon = allCoupons[1];
			const payload = { code: matchingCoupon.code };
			const { status, body } = await couponsApi.listAll.coupons(payload);

			expect(status).toEqual(couponsApi.listAll.responseCode);
			expect(body).toHaveLength(1);
			expect(body[0].id).toEqual(matchingCoupon.id);
		});

		it('can paginate results', async () => {
			const pageSize = 2;
			const payload = {
				page: 1,
				per_page: pageSize,
			};
			const { status, body } = await couponsApi.listAll.coupons(payload);

			expect(status).toEqual(couponsApi.listAll.responseCode);
			expect(body).toHaveLength(pageSize);
		});

		it('can limit results to matching string', async () => {
			// Search by description
			const matchingCoupon = allCoupons[2];
			const matchingString = matchingCoupon.description;
			const payload = {
				search: matchingString,
			};

			const { status, body } = await couponsApi.listAll.coupons(payload);

			expect(status).toEqual(couponsApi.listAll.responseCode);
			expect(body).toHaveLength(1);
			expect(body[0].id).toEqual(matchingCoupon.id);
		});
	});

	describe('Add coupon to order', () => {
		const testCoupon = {
			code: `coupon-${Date.now()}`,
			discount_type: 'percent',
			amount: '10',
		};
		let orderId;

		beforeAll(async () => {
			// Create a coupon
			const createCouponResponse = await couponsApi.create.coupon(
				testCoupon
			);
			testCoupon.id = createCouponResponse.body.id;
		});

		// Clean up created coupon and order
		afterAll(async () => {
			await couponsApi.delete.coupon(testCoupon.id, true);
			await ordersApi.delete.order(orderId, true);
		});

		it('can add coupon to an order', async () => {
			const orderWithCoupon = {
				...order,
				coupon_lines: [{ code: testCoupon.code }],
			};
			const { status, body } = await ordersApi.create.order(
				orderWithCoupon
			);
			orderId = body.id;

			expect(status).toEqual(ordersApi.create.responseCode);
			expect(body.coupon_lines).toHaveLength(1);
			expect(body.coupon_lines[0].code).toEqual(testCoupon.code);
		});
	});
});