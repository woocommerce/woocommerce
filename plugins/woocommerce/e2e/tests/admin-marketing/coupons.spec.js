const { test, expect } = require( '@playwright/test' );

test.describe( 'Coupons page', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test( 'A user can view the coupons overview without it crashing', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/edit.php?post_type=shop_coupon&legacy_coupon_menu=1'
		);
		await expect( page.locator( 'h1.wp-heading-inline' ) ).toHaveText(
			'Coupons'
		);
		await expect( page.locator( 'a.page-title-action' ) ).toHaveText(
			'Add coupon'
		);
	} );
} );
