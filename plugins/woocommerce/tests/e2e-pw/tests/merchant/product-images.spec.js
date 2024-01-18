const {test: baseTest, expect} = require('@playwright/test');
const wcApi = require('@woocommerce/woocommerce-rest-api').default;

baseTest.describe('Products > Product Images', () => {
	baseTest.use({storageState: process.env.ADMINSTATE});

	const test = baseTest.extend({
		api: async ({baseURL}, use) => {
			const api = new wcApi({
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3'
			});

			await use(api);
		},
		product: async ({page, api}, use) => {
			let product = {
				id: 0,
				name: `Product ${Date.now()}`,
				type: 'simple',
				regular_price: '12.99',
				sale_price: '11.59',
				images: [
					{
						src: "http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg"
					}
				]
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

	test('can set product image', async ({page, product, api}) => {
		// Remove the product image first and reload the page
		await api
			.put(`products/${product.id}`,
				{
					images: []
				}
			)
			.then((response) => {
				product = response.data;
			});
		await page.reload();

		await test.step('Set product image', async () => {
			await page.getByRole('link', {name: 'Set product image'}).click();
			await page.getByRole('tab', {name: 'Media Library'}).click();
			const imageLocator = page.getByLabel('image-01').nth(0)
			await imageLocator.click();
			await expect(imageLocator).toBeChecked()
			await page.getByRole('button', {name: 'Set product image'}).click();

			// Wait for the product image thumbnail to be updated.
			// Clicking the "Update" button before this happens will not update the image.
			await expect(page.locator('#set-post-thumbnail img[src$="image-01.jpeg"]')).toBeVisible();

			await page.getByRole('button', {name: 'Update'}).click();
		});

		await test.step('Verify product image was set', async () => {
			// Verify product was updated
			await expect(page.getByText('Product updated.')).toBeVisible()

			// Verify image in store frontend
			await page.goto(product.permalink);
			await expect(page.getByTitle(`image-01`)).toBeVisible();
		});
	});

	test('can update the product image', async ({page, product}) => {
		await test.step('Update product image', async () => {
			await page.locator('#set-post-thumbnail').click();

			const imageLocator = page.getByLabel('image-02').nth(0)
			await imageLocator.click();
			await expect(imageLocator).toBeChecked()
			await page.getByRole('button', {name: 'Set product image'}).click();

			// Wait for the product image thumbnail to be updated.
			// Clicking the "Update" button before this happens will not update the image.
			await expect(page.locator('#set-post-thumbnail img[src$="image-02.png"]')).toBeVisible();

			await page.getByRole('button', {name: 'Update'}).click();
		});

		await test.step('Verify product image was set', async () => {
			// Verify product was updated
			await expect(page.getByText('Product updated.')).toBeVisible()

			// Verify image in store frontend
			await page.goto(product.permalink);
			await expect(page.getByTitle(`image-02`)).toBeVisible();
		});
	});

	test('can delete the product image', async ({page, product}) => {
		await test.step('Remove product image', async () => {
			await page.getByRole('link', {name: 'Remove product image'}).click();
			await expect(page.getByRole('link', {name: 'Set product image'})).toBeVisible();

			await page.getByRole('button', {name: 'Update'}).click();
		});

		await test.step('Verify product image was removed', async () => {
			// Verify product was updated
			await expect(page.getByText('Product updated.')).toBeVisible()

			// Verify image in store frontend
			await page.goto(product.permalink);
			await expect(page.getByAltText("Awaiting product image")).toBeVisible();
		});
	});

	test.only('can create a product gallery', async ({page, product}) => {
		await test.step('Add product gallery images', async () => {
		});

		await test.step('Verify product gallery was set', async () => {
			// Verify image in admin area

			// Verify images in store frontend
		});
	});

});
