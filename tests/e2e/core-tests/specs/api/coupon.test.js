/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const { HTTPClientFactory, Coupon } = require( '@woocommerce/api' );

/**
 * External dependencies
 */
const config = require( 'config' );
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

/**
 * Create the default coupon and tests interactions with it via the API.
 */
const runCouponApiTest = () => {
	describe('REST API > Coupon', () => {
		let client;
		let percentageCoupon;
		let coupon;
		let repository;

		beforeAll(async () => {
			percentageCoupon = config.get( 'coupons.percentage' );
			const admin = config.get( 'users.admin' );
			const url = config.get( 'url' );

			client = HTTPClientFactory.build( url )
				.withBasicAuth( admin.username, admin.password )
				.withIndexPermalinks()
				.create();
		} );

		it('can create a coupon', async () => {
			repository = Coupon.restRepository( client );

			// Check properties of the coupon in the create coupon response.
			coupon = await repository.create( percentageCoupon );
			expect( coupon ).toEqual( expect.objectContaining( percentageCoupon ) );
		});

		it('can retrieve a coupon', async () => {
			const couponProperties = {
				id: coupon.id,
				code: percentageCoupon.code,
				discount_type: percentageCoupon.discountType,
				amount: percentageCoupon.amount,
			};

			// Read coupon directly from API to compare.
			const response = await client.get( `/wc/v3/coupons/${coupon.id}` );
			expect( response.statusCode ).toBe( 200 );
			expect( response.data ).toEqual( expect.objectContaining( couponProperties ) );
		});

		it('can update a coupon', async () => {
			const updatedCouponProperties = {
				amount: '75.00',
				discount_type: 'fixed_cart',
				free_shipping: true,
			};

			await repository.update( coupon.id, updatedCouponProperties );

			// Check the coupon response for the updated values.
			const response = await client.get( `/wc/v3/coupons/${coupon.id}` );
			expect( response.statusCode ).toBe( 200 );
			expect( response.data ).toEqual( expect.objectContaining( updatedCouponProperties ) );
		});

		it('can delete a coupon', async () => {
			// Delete the coupon
			const status = await repository.delete( coupon.id );

			// If the delete is successful, the response comes back truthy
			expect( status ).toBeTruthy();
		});
	});
};

module.exports = runCouponApiTest;
