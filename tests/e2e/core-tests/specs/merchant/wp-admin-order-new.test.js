/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	verifyPublishAndTrash,
	createSimpleProduct,
	createVariableProduct,
	createGroupedProduct,
	uiUnblocked
} = require('@woocommerce/e2e-utils');
const faker = require('faker');
const config = require('config');

/**
 * Create different product types
 */
const setupProducts = async () => {
	const simpleProduct = {
		name: faker.commerce.productName(),
		price: faker.commerce.price(1, 999),
		id: null
	}
	const variableProduct = {
		name: 'Variable Product with Three Variations',
		prices: [9.99, 11.99, 20.00],
		id: null
	}
	const groupedProduct = {
		name: config.get('products.simple.name'),
		simpleProductPrice: config.has('products.simple.price') ? config.get('products.simple.price') : '9.99',
		id: null
	}

	simpleProduct.id = await createSimpleProduct(simpleProduct.name, simpleProduct.price)
	variableProduct.id = await createVariableProduct();
	groupedProduct.id = await createGroupedProduct()

	return [
		simpleProduct,
		variableProduct,
		groupedProduct
	]
}

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
			// todo setup products and tax classes
			const products = await setupProducts();

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
		})
	});
}

module.exports = runCreateOrderTest;
