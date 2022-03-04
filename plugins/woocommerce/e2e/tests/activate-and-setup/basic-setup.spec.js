const { test, expect } = require('@playwright/test');

test.describe('Store owner can finish initial store setup', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });
	test('can enable tax rates and calculations', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=wc-settings');
		const enableTaxes = page.locator('#woocommerce_calc_taxes');
		await enableTaxes.click();
		await page.locator('text=Save changes').click();
		await expect(enableTaxes).toBeChecked();
	});
});
