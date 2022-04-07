const { test, expect } = require('@playwright/test');

const sanFranciscoZIP = '94107';
const shippingZoneNameUS = 'US with Flat rate';
const shippingZoneNameFL = 'CA with Free shipping';
const shippingZoneNameSF = 'SF with Local pickup'

test.describe('WooCommerce Shipping Settings - Add new shipping zone', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });

	test.beforeEach(async ({ page }) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping'
		);
		const existingZones = await page.locator('.wc-shipping-zone-delete');
		const count = await existingZones.count();
		for (let i = 0; i < count; i++) {
			page.on('dialog', dialog => dialog.accept());
			await page.dispatchEvent('.wc-shipping-zone-delete >> nth=0', 'click');
		}
	});

	test('add shipping zone for San Francisco with free Local pickup', async ({ page }) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
		);
		await page.fill('#zone_name', shippingZoneNameSF);

		await page.click('.select2-search__field');
		await page.type('.select2-search__field', 'California, United States');
		await page.click('.select2-results__option.select2-results__option--highlighted');

		await page.click('.wc-shipping-zone-postcodes-toggle');
		await page.fill('#zone_postcodes', sanFranciscoZIP);

		await page.click('text=Add shipping method');

		await page.selectOption('select[name=add_method_id]', 'local_pickup');
		await page.click('#btn-ok');

		await page.goto('wp-admin/admin.php?page=wc-settings&tab=shipping');
		await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first

		await expect(page.locator('.wc-shipping-zone-name >> nth=1')).toHaveText(`${shippingZoneNameSF} Edit | Delete`);
		await expect(page.locator('.wc-shipping-zone-region >> nth=1')).toHaveText(`California, ${sanFranciscoZIP}`);
		await expect(page.locator('.wc-shipping-zone-methods >> nth=1')).toHaveText('Local pickup');

		// clean up
		await page.dispatchEvent('.wc-shipping-zone-delete >> nth=0', 'click');
	});

	test('add shipping zone for California with Free shipping', async ({ page }) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
		);
		await page.fill('#zone_name', shippingZoneNameFL);

		await page.click('.select2-search__field');
		await page.type('.select2-search__field', 'California, United States');
		await page.click('.select2-results__option.select2-results__option--highlighted');

		await page.click('text=Add shipping method');

		await page.selectOption('select[name=add_method_id]', 'free_shipping');
		await page.click('#btn-ok');

		await page.goto('wp-admin/admin.php?page=wc-settings&tab=shipping');
		await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first

		await expect(page.locator('.wc-shipping-zone-name >> nth=1')).toHaveText(`${shippingZoneNameFL} Edit | Delete`);
		await expect(page.locator('.wc-shipping-zone-region >> nth=1')).toHaveText('California');
		await expect(page.locator('.wc-shipping-zone-methods >> nth=1')).toHaveText('Free shipping');

		// clean up
		await page.dispatchEvent('.wc-shipping-zone-delete >> nth=0', 'click');
	});

	test('add shipping zone for the US with Flat rate', async ({ page }) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping&zone_id=new'
		);
		await page.fill('#zone_name', shippingZoneNameUS);

		await page.click('.select2-search__field');
		await page.type('.select2-search__field', 'United States');
		await page.click('.select2-results__option.select2-results__option--highlighted');

		await page.click('text=Add shipping method');

		await page.selectOption('select[name=add_method_id]', 'flat_rate');
		await page.click('#btn-ok');

		await page.goto('wp-admin/admin.php?page=wc-settings&tab=shipping');
		await page.reload(); // Playwright runs so fast, the location shows up as "Everywhere" at first

		await expect(page.locator('.wc-shipping-zone-name >> nth=1')).toHaveText(`${shippingZoneNameUS} Edit | Delete`);
		await expect(page.locator('.wc-shipping-zone-region >> nth=1')).toHaveText('United States (US)');
		await expect(page.locator('.wc-shipping-zone-methods >> nth=1')).toHaveText('Flat rate');

		// clean up
		await page.dispatchEvent('.wc-shipping-zone-delete >> nth=0', 'click');
	});
});
