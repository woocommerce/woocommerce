const { test, expect } = require('@playwright/test');

const checkHeadingAndElement = async function (pageTitle) {
  let element = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '.d3-chart__empty-message';
  let elementText = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'No data for the selected date range';
  await expect(page).toMatchElement('h1', {
    text: pageTitle
  }); // Depending on order of tests the chart may not be empty.

  const found = await page.$(element);

  if (found) {
    await expect(page).toMatchElement(element, {
      text: elementText
    });
  } else {
    await expect(page).toMatchElement('.woocommerce-chart');
  }
}; // Analytics pages that we'll test against

const pages = [['Overview'],
['Products'], ['Revenue'], ['Orders'], ['Variations'], ['Categories'], ['Coupons'], ['Taxes'], ['Downloads'], ['Stock', '.components-button > span', 'Product / Variation'], ['Settings', 'h2', 'Analytics Settings']];

test.describe('Analytics > Opening Top Level Pages', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });

	for (let aPages of [
			'Overview',
			'Products',
			'Revenue',
			'Orders',
			'Variations',
			'Categories',
			'Coupons',
			'Taxes',
			'Downloads',
			'Stock',
			'Settings']) {
		test(`can see ${aPages} page properly`, async ({ page }) => {
			const urlTitle = aPages.toLowerCase();
			await page.goto(`/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2F${urlTitle}`);
			const pageTitle = page.locator('h1');
			await expect(pageTitle).toHaveText(aPages);
		});
	}
});
