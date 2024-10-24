const { test, expect } = require( '@playwright/test' );

test.describe( 'Coupons page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'A user can view the coupons overview without it crashing', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/edit.php?post_type=shop_coupon&legacy_coupon_menu=1'
		);

		await expect( page.locator( 'h1.wp-heading-inline' ) ).toHaveText(
			'Coupons'
		);

		// Use regex to allow for "Add coupon" or "Add new coupon"
		// making it compatible with WP <=6.6 & 6.7+
		const addCouponRegex = /Add (coupon|new coupon)/i;
		await expect( page.locator( 'a.page-title-action' ) ).toHaveText(
			addCouponRegex
		);
	} );
} );
