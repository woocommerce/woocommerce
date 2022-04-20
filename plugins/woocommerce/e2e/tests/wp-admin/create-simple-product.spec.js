const { test, expect } = require('@playwright/test');
const wcApi = require('@woocommerce/woocommerce-rest-api').default;

const virtualProductName = "Virtual Product Name";
const nonVirtualProductName = "Non Virtual Product Name";
const productPrice = "9.99";

test.describe('Add New Simple Product Page', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });

	test.beforeAll(async () => {
		// need to add a shipping zone
		const api = new wcApi({
			url: 'http://localhost:8084',
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		});
		// and the flat rate shipping method to that zone
		await api
			.post('shipping/zones', {
				name: 'Everywhere',
			})
			.then((response) => {
				zoneId = response.data.id;
			});
		await api.post(`shipping/zones/${zoneId}/methods`, {
			method_id: "flat_rate"
		});
	});

	test.afterAll(async () => {
		// cleans up all products after run
		const api = new wcApi({
			url: 'http://localhost:8084',
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		});
		await api.get('products').then((response) => {
			const products = response.data;
			for (product of products) {
				api.delete(`products/${product.id}`, { force: true }).then(
					(response) => {
						// nothing to do here.
					}
				);
			}
		});
		// delete the shipping zone
		await api.delete(`shipping/zones/${zoneId}`, { force: true });
	});

	test('can create simple virtual product', async ({ page }) => {
		await page.goto('wp-admin/post-new.php?post_type=product');
		await page.fill('#title', virtualProductName);
		await page.click('#_virtual');
		await page.fill('#_regular_price', productPrice);
		await page.click('#publish');
		await expect(page.locator('div.notice-success')).toHaveText(
			'Product published. View ProductDismiss this notice.'
		);
	});

	test('can have a shopper add the simple virtual product to the cart', async ({
		page,
	}) => {
		await page.goto('shop/');
		await page.click(`text=${virtualProductName}`);
		await page.click('text=Add to cart');
		await page.click('text=View cart');
		await expect(page.locator('td[data-title=Product]')).toHaveText(
			virtualProductName
		);
		await expect(
			page.locator('a.shipping-calculator-button')
		).not.toBeVisible();
		await page.click('a.remove');
	});

	test('can create simple non-virtual product', async ({ page }) => {
		await page.goto('wp-admin/post-new.php?post_type=product');
		await page.fill('#title', nonVirtualProductName);
		await page.fill('#_regular_price', productPrice);
		await page.click('#publish');
		await expect(page.locator('div.notice-success')).toHaveText(
			'Product published. View ProductDismiss this notice.'
		);
	});

	test('can have a shopper add the simple non-virtual product to the cart', async ({
		page,
	}) => {
		await page.goto('shop/');
		await page.click(`text=${nonVirtualProductName}`);
		await page.click('text=Add to cart');
		await page.click('text=View cart');
		await expect(page.locator('td[data-title=Product]')).toHaveText(
			nonVirtualProductName
		);
		await expect(
			page.locator('a.shipping-calculator-button')
		).toBeVisible();
		await page.click('a.remove');
	});
});
