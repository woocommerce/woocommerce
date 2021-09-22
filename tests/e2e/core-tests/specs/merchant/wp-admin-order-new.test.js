/**
 * Internal dependencies
 */
const {
	merchant,
	uiUnblocked,
	AdminEdit,
} = require('@woocommerce/e2e-utils');
const config = require('config');
const {
	HTTPClientFactory,
	VariableProduct,
	GroupedProduct,
	SimpleProduct,
	ProductVariation,
	ExternalProduct
} = require('@woocommerce/api');

const taxRates = [
	{
		name: 'Tax Rate Simple',
		rate: '10',
		class: 'tax-class-simple'
	},
	{
		name: 'Tax Rate Variable',
		rate: '20',
		class: 'tax-class-variable'
	},
	{
		name: 'Tax Rate External',
		rate: '30',
		class: 'tax-class-external'
	}
];

const taxTotals = ['$10.00', '$40.00', '$240.00'];

const initProducts = async () => {
	const apiUrl = config.get('url');
	const adminUsername = config.get('users.admin.username');
	const adminPassword = config.get('users.admin.password');
	const httpClient = HTTPClientFactory.build(apiUrl)
		.withBasicAuth(adminUsername, adminPassword)
		.create();
	const taxClassesPath = '/wc/v3/taxes/classes';
	const taxClasses = [
		{
			name: 'Tax Class Simple',
			slug: 'tax-class-simple-698962'
		},
		{
			name: 'Tax Class Variable',
			slug: 'tax-class-variable-790238'
		},
		{
			name: 'Tax Class External',
			slug: 'tax-class-external-991321'
		}
	];

	// Enable taxes in settings
	const enableTaxes = async () => {
		const path = '/wc/v3/settings/general/woocommerce_calc_taxes';
		const data = {
			value: 'yes'
		};
		await httpClient.put(path, data);
	};
	await enableTaxes();

	// Initialize tax classes
	const initTaxClasses = async () => {
		for (const classToBeAdded of taxClasses) {
			await httpClient.post(taxClassesPath, classToBeAdded);
		}
	};
	await initTaxClasses();

	// Initialize tax rates
	const initTaxRates = async () => {
		const path = '/wc/v3/taxes';

		for (const rateToBeAdded of taxRates) {
			await httpClient.post(path, rateToBeAdded);
		}
	};
	await initTaxRates();

	// Initialization functions per product type
	const initSimpleProduct = async () => {
		const repo = SimpleProduct.restRepository(httpClient);
		const simpleProduct = {
			name: 'Simple Product 273722',
			regularPrice: '100',
			taxClass: 'Tax Class Simple'
		};
		return await repo.create(simpleProduct);
	};
	const initVariableProduct = async () => {
		const variations = [
			{
				regularPrice: '200',
				attributes: [
					{
						name: 'Size',
						option: 'Small'
					},
					{
						name: 'Colour',
						option: 'Yellow'
					}
				],
				taxClass: 'Tax Class Variable'
			},
			{
				regularPrice: '300',
				attributes: [
					{
						name: 'Size',
						option: 'Medium'
					},
					{
						name: 'Colour',
						option: 'Magenta'
					}
				],
				taxClass: 'Tax Class Variable'
			}
		];
		const variableProductData = {
			name: 'Variable Product 024611',
			type: 'variable',
			taxClass: 'Tax Class Variable'
		};

		const variationRepo = ProductVariation.restRepository(httpClient);
		const productRepo = VariableProduct.restRepository(httpClient);
		const variableProduct = await productRepo.create(variableProductData);
		for (const v of variations) {
			await variationRepo.create(variableProduct.id, v);
		}

		return variableProduct;
	};
	const initGroupedProduct = async () => {
		const groupedRepo = GroupedProduct.restRepository(httpClient);
		const defaultGroupedData = config.get('products.grouped');
		const groupedProductData = {
			...defaultGroupedData,
			name: 'Grouped Product 858012'
		};

		return await groupedRepo.create(groupedProductData);
	};
	const initExternalProduct = async () => {
		const repo = ExternalProduct.restRepository(httpClient);
		const defaultProps = config.get('products.external');
		const props = {
			...defaultProps,
			name: 'External product 786794',
			regularPrice: '800',
			taxClass: 'Tax Class External'
		};
		return await repo.create(props);
	};

	// Create a product for each product type
	const simpleProduct = await initSimpleProduct();
	const variableProduct = await initVariableProduct();
	const groupedProduct = await initGroupedProduct();
	const externalProduct = await initExternalProduct();

	return [simpleProduct, variableProduct, groupedProduct, externalProduct];
};

let products;

const runCreateOrderTest = () => {
	describe('WooCommerce Orders > Add new order', () => {
		beforeAll(async () => {
			// Initialize products for each product type
			products = await initProducts();

			// Login
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
			const orderEdit = new AdminEdit();
			await orderEdit.verifyPublish(
				'.order_actions li .save_order',
				'#message',
				'Order updated.',
			);

			await expect( page ).toMatchElement( '#select2-order_status-container', { text: 'Processing' } );
			await expect( page ).toMatchElement(
				'#woocommerce-order-notes .note_content',
				{
					text: 'Order status changed from Pending payment to Processing.',
				}
			);
		});

		it('can create new complex order with multiple product types & tax classes', async () => {
			// Go to "add order" page
			await merchant.openNewOrder();

			// Open modal window for adding line items
			await expect(page).toClick('button.add-line-item');
			await expect(page).toClick('button.add-order-item');
			await page.waitForSelector('.wc-backbone-modal-header');

			// Search for each product to add, then verify that they are saved
			for (const { name } of products) {
				await expect(page).toClick(
					'.wc-backbone-modal-content tr:last-child .select2-selection__arrow'
				);
				await expect(page).toFill(
					'#wc-backbone-modal-dialog + .select2-container .select2-search__field',
					name
				);
				const firstResult = await page.waitForSelector(
					'li[data-selected]'
				);
				await firstResult.click();
				await expect(page).toMatchElement(
					'.wc-backbone-modal-content tr:nth-last-child(2) .wc-product-search option',
					name
				);
			}

			// Save the line items
			await expect(page).toClick('.wc-backbone-modal-content #btn-ok');
			await uiUnblocked();

			// Recalculate taxes
			await expect(page).toDisplayDialog(async () => {
				await expect(page).toClick('.calculate-action');
			});
			await page.waitForSelector('th.line_tax');

			// Save the order and verify line items
			await expect(page).toClick('button.save_order');
			await page.waitForNavigation();
			for (const { name } of products) {
				await expect(page).toMatchElement('.wc-order-item-name', {
					text: name
				});
			}

			// Verify that the names of each tax class were shown
			for (const { name } of taxRates) {
				await expect(page).toMatchElement('th.line_tax', {
					text: name
				});
				await expect(page).toMatchElement('.wc-order-totals td.label', {
					text: name
				});
			}

			// Verify tax amounts
			for (const amount of taxTotals) {
				await expect(page).toMatchElement('td.line_tax', {
					text: amount
				});
				await expect(page).toMatchElement('.wc-order-totals td.total', {
					text: amount
				});
			}
		});
	});
};

module.exports = runCreateOrderTest;
