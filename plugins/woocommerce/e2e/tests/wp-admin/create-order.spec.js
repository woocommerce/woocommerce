const { test, expect } = require('@playwright/test');

test.describe('WooCommerce Orders > Add new order', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });

	test('can create new order', async ({ page }) => {

	});

	test('can create new complex order with multiple product types & tax classes', async ({ page }) => {

	});

});
