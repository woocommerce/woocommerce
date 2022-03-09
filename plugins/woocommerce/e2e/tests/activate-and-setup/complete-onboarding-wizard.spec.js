const { test, expect } = require('@playwright/test');

test.describe('Store owner can complete onboarding wizard', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });
	test('can start the profile wizard', async ({ page }) => {
		await page.goto('wp-admin/admin.php?page=wc-admin&path=/setup-wizard');
		const pageHeading = await page.textContent('div.woocommerce-profile-wizard__step-header > h2');
		expect(pageHeading).toContain('Welcome to WooCommerce');
	});
	test('can complete the store details section', async ({ page }) => {
		await page.goto('wp-admin/admin.php?page=wc-admin&path=/setup-wizard');
		// Fill store's address - first line
		await page.fill('#inspector-text-control-0', 'addr 1');
		// Fill store's address - second line
		await page.fill('#inspector-text-control-1', 'addr 2');
		// Type the requested country/region
		await page.fill('#woocommerce-select-control-0__control-input', 'United States (US) — California');
		// Fill the city where the store is located
		await page.fill('#inspector-text-control-2', 'San Francisco');
		// Fill postcode of the store
		await page.fill('#inspector-text-control-3', '94107');
		// Fill store's email address
		await page.fill('#inspector-text-control-4', 'admin@woocommercecoree2etestsuite.com');
		// Verify that checkbox next to "Get tips, product updates and inspiration straight to your mailbox" is selected
		await page.check('#inspector-checkbox-control-0');
		// Click continue button
		await page.click('button >> text=Continue');
		// Usage tracking dialog
		const dialogHeading = await page.textContent('.components-modal__header-heading')
		expect(dialogHeading).toContain('Build a better WooCommerce');
		await page.click('button >> text=No thanks');
	});
	test('can complete the industry section', async ({ page }) => {
		// There are 8 checkboxes on the page, adjust this constant if we change that
		const expectedIndustries = 8;
		await page.goto('wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=industry');
		const pageHeading = await page.textContent('div.woocommerce-profile-wizard__step-header > h2');
		expect(pageHeading).toContain('In which industry does the store operate?');
		// Check that there are the correct number of options listed
		const numCheckboxes = await page.$$('.components-checkbox-control__input');
		expect(numCheckboxes.length === expectedIndustries).toBeTruthy();
		// Check the fashion and health & beauty industries
		await page.check('label >> text=Fashion, apparel, and accessories');
		await page.check('label >> text=Health and beauty');
		await page.click('button >> text=Continue');
	});
	test('can complete the product types section', async ({ page }) => {
		// There are 7 checkboxes on the page, adjust this constant if we change that
		const expectedProductTypes = 7;
		await page.goto('wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=product-types');
		const pageHeading = await page.textContent('div.woocommerce-profile-wizard__step-header > h2');
		expect(pageHeading).toContain('What type of products will be listed?');
		// Check that there are the correct number of options listed
		const numCheckboxes = await page.$$('.components-checkbox-control__input');
		expect(numCheckboxes.length === expectedProductTypes).toBeTruthy();
		// Check the Physical and Downloadable products
		await page.check('label >> text=Physical products');
		await page.check('label >> text=Downloads');
		await page.click('button >> text=Continue');
	});
	test('can complete the business section', async ({ page }) => {
		await page.goto('wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=business-details');
		const pageHeading = await page.textContent('div.woocommerce-profile-wizard__step-header > h2');
		expect(pageHeading).toContain('Tell us about your business');
		// Select 1 - 10 for products
		await page.click('#woocommerce-select-control-0__control-input', { force: true });
		await page.click('#woocommerce-select-control__option-0-1-10');
		// Select No for selling elsewhere
		await page.click('#woocommerce-select-control-1__control-input', { force: true });
		await page.click('#woocommerce-select-control__option-1-no');
		await page.click('button >> text=Continue');
	});
	test('can unselect all business features and continue', async ({ page }) => {

	});
	test('can complete the theme selection section', async ({ page }) => {

	});
});

test.describe('A japanese store can complete the selective bundle install but does not include WCPay.', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });

});
