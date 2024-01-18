const {test: baseTest, expect} = require('@playwright/test');
const wcApi = require('@woocommerce/woocommerce-rest-api').default;

baseTest.describe('Products > Product Images', () => {
	baseTest.use({storageState: process.env.ADMINSTATE});

	const apiFixture = baseTest.extend({
		api: async ({baseURL}, use) => {
			const api = new wcApi({
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
				axiosConfig: {
					// allow 404s, so we can check if the product was deleted without try/catch
					validateStatus: function (status) {
						return status >= 200 && status < 300 || status === 404;
					},
				},
			});

			await use(api);
		},
	});

	const test = apiFixture.extend({
		product: async ({page, api}, use) => {
			let product = {
				id: 0,
				name: `Product with images ${Date.now()}`,
				type: 'simple',
				regular_price: '12.99',
				sale_price: '11.59'
			}

			await api
				.post('products', product)
				.then((response) => {
					product = response.data;
				});

			await test.step('Navigate to product edit page', async () => {
				await page.goto(`wp-admin/post.php?post=${product.id}&action=edit`);
			});

			await use(product);

			// Cleanup
			await api.delete(`products/${product.id}`, {force: true});
		},
	});

	test('can set product image', async ({page, product}) => {
		await test.step('Set product image', async () => {
			await page.getByRole('link', {name: 'Set product image'}).click();
			await page.getByRole('tab', {name: 'Media Library'}).click();
			const imageLocator = page.getByLabel('image-01').nth(0)
			await imageLocator.click();
			await expect(imageLocator).toBeChecked()
			await page.getByRole('button', {name: 'Set product image'}).click();

			await expect(page.locator('#set-post-thumbnail > img')).toBeVisible();

			await page.getByRole('button', {name: 'Update'}).click();
		});

		await test.step('Verify product image was set', async () => {
			// Verify product was updated
			await expect(page.getByText('Product updated.')).toBeVisible()

			// Verify image in admin area
			await expect(page.locator('#set-post-thumbnail > img')).toBeVisible();

			// Verify image in store frontend
			await page.goto(product.permalink);
			await expect(page.getByTitle(`image-01`)).toBeVisible();
		});
	});

	test('can update the product image', async ({page, product}) => {
		await test.step('Update product image', async () => {
		});

		await test.step('Verify product image was set', async () => {
			// Verify image in admin area

			// Verify image in store frontend
		});
	});

	test('can delete the product image', async ({page, product}) => {
		await test.step('Remove product image', async () => {
		});

		await test.step('Verify product image was set', async () => {
			// Verify image in admin area

			// Verify image in store frontend
		});
	});

	test('can create a product gallery', async ({page, product}) => {
		await test.step('Add product gallery images', async () => {
		});

		await test.step('Verify product image was set', async () => {
			// Verify image in admin area

			// Verify images in store frontend
		});
	});

});
