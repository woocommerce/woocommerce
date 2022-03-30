const { test, expect } = require('@playwright/test');

test.describe('Add New Coupon Page', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });

	test('can create new coupon', async ({ page }) => {
		await page.goto('wp-admin/post-new.php?post_type=shop_coupon');
		await page.fill('#title', `code-${new Date().getTime().toString()}`);
		await page.fill('#woocommerce-coupon-description', 'test coupon');

		await page.fill('#coupon_amount', '100');

		await page.locator('#publish').click();

		await expect(page.locator('div.notice.notice-success')).toHaveText('Coupon updated.Dismiss this notice.');

		// delete the coupon
		await page.locator('a.submitdelete').click();
		await expect(page.locator('div.notice.is-dismissible >> p')).toHaveText('1 coupon moved to the Trash. Undo');
	});
});
