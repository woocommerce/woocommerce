const { test, expect } = require('@playwright/test');

test.describe(
	'Store owner can login and make sure WooCommerce is activated',
	() => {
		test.use({ storageState: 'e2e/storage/adminState.json' });

		test('can make sure WooCommerce is activated.', async ({ page }) => {
			await page.goto('/wp-admin/plugins.php');
			// Expect the woo plugin to be displayed
			expect(page.locator(`//tr[@data-slug='woocommerce']`))
				.toBeVisible;
			// Expect it to have an active class
			expect(
				page.locator(`//tr[@data-slug='woocommerce']`)
			).toHaveClass('active is-uninstallable');
		});
	}
);
