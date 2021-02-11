/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	clickTab,
	verifyPublishAndTrash
} = require( '@woocommerce/e2e-utils' );

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

			// Publish coupon, verify that it was published. Trash coupon, verify that it was trashed.
			await verifyPublishAndTrash(
				'#publish',
				'.notice',
				'Coupon updated.',
				'1 coupon moved to the Trash.'
			);

		});
	});
}

module.exports = runCreateCouponTest;
