/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	verifyPublishAndTrash,
	uiUnblocked
} = require('@woocommerce/e2e-utils');
const faker = require('faker');
const config = require('config');
const { HTTPClientFactory,
	VariableProduct,
	GroupedProduct,
	SimpleProduct,
	ProductAttribute,
	ProductVariation
} = require('@woocommerce/api');

const runCreateOrderTest = () => {
	describe('WooCommerce Orders > Add new order', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can create new order', async () => {
			// Go to "add order" page
			await merchant.openNewOrder();

			// Make sure we're on the add order page
			await expect(page.title()).resolves.toMatch('Add new order');

			// Set order data
			await expect(page).toSelect('#order_status', 'Processing');
			await expect(page).toFill('input[name=order_date]', '2018-12-13');
			await expect(page).toFill('input[name=order_date_hour]', '18');
			await expect(page).toFill('input[name=order_date_minute]', '55');

			// Create order, verify that it was created. Trash order, verify that it was trashed.
			await verifyPublishAndTrash(
				'.order_actions li .save_order',
				'#message',
				'Order updated.',
				'1 order moved to the Trash.'
			);
		});

		// todo remove .only
		it('can create new complex order with multiple product types & tax classes', async () => {
			// Initialize different product types
			const initProducts = async () => {
				const apiUrl = config.get('url');
				const adminUsername = config.get('users.admin.username');
				const adminPassword = config.get('users.admin.password');
				const httpClient = HTTPClientFactory.build(apiUrl)
					.withBasicAuth(adminUsername, adminPassword)
					.create();
				const productName = () => faker.commerce.productName();
				const price = () => faker.commerce.price(1, 999);

				// Initialize repositories
				const simpleRepo = SimpleProduct.restRepository(httpClient);
				const variableRepo = VariableProduct.restRepository(httpClient);
				const groupedRepo = GroupedProduct.restRepository(httpClient);


				// Initialize products
				const simpleProduct = await simpleRepo.create({ name: productName(), price: price() });
				const variableProduct = await variableRepo.create({ name: productName() });
				const groupMemberProduct1 = await simpleRepo.create({ name: productName(), price: price() });
				const groupMemberProduct2 = await simpleRepo.create({ name: productName(), price: price() });
				const groupedProduct = await groupedRepo.create({
					name: productName(),
					groupedProducts: [groupMemberProduct1.id, groupMemberProduct2.id]
				});

				return [
					simpleProduct,
					variableProduct,
					groupedProduct
				];
			}
			const products = await initProducts();

			// Go to "add order" page
			await merchant.openNewOrder();

			// Add products (line items) to the order
			await expect(page).toClick('button.add-line-item');
			await expect(page).toClick('button.add-order-item');
			await page.waitForSelector('.wc-backbone-modal-header');
			for (const { name, id } of products) {
				await expect(page).toClick('.wc-backbone-modal-content tr:last-child .wc-product-search');
				await expect(page).toFill('#wc-backbone-modal-dialog + .select2-container .select2-search__field', name);
				await expect(page).toClick('li[aria-selected="true"]');
				await expect(page).toMatchElement(
					'.wc-backbone-modal-content tr:nth-last-child(2) .wc-product-search option',
					`${name} (#${id})`
				);
			}

			await page.click('.wc-backbone-modal-content #btn-ok');

			await uiUnblocked();

			// Verify the products we added show as line items now
			for (const { name } of products) {
				await expect(page).toMatchElement('.wc-order-item-name', { text: name });
			}

			// Verify variation details in variable product line item
			await expect(page).toMatchElement('.wc-order-item-variation', { text: products.filter(p => p.variations).id })
		})
	});
}

module.exports = runCreateOrderTest;
