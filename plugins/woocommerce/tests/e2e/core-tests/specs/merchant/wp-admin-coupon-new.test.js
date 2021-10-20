/**
 * Internal dependencies
 */
const {
	merchant,
	clickTab,
	AdminEdit,
	factories,
} = require( '@woocommerce/e2e-utils' );
const { Coupon } = require( '@woocommerce/api' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runCreateCouponTest = () => {
	describe('Add New Coupon Page', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can create new coupon', async () => {
			// Go to "add coupon" page
			await merchant.openNewCoupon();

			// Make sure we're on the add coupon page
			await expect(page.title()).resolves.toMatch('Add new coupon');

			// Fill in coupon code and description
			await expect(page).toFill('#title', 'code-' + new Date().getTime().toString());
			await expect(page).toFill('#woocommerce-coupon-description', 'test coupon');

			// Set general coupon data
			await clickTab('General');
			await expect(page).toSelect('#discount_type', 'Fixed cart discount');
			await expect(page).toFill('#coupon_amount', '100');

			// Publish coupon, verify that it was published.
			const adminEdit = new AdminEdit();
			await adminEdit.verifyPublish(
				'#publish',
				'.notice',
				'Coupon updated.',
			);
			// Delete the coupon
			const couponId = await adminEdit.getId();
			if ( couponId ) {
				const repository = Coupon.restRepository( factories.api.withDefaultPermalinks );
				await repository.delete( couponId );
			}
		});
	});
}

module.exports = runCreateCouponTest;
