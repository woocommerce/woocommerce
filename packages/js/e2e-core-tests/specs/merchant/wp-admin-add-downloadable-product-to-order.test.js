/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */

const {
	merchant,
	clickTab,
	verifyAndPublish,
	backboneUnblocked,
	utils,
	withRestApi,
} = require('@woocommerce/e2e-utils');
const {
	getTestConfig,
} = require('@woocommerce/e2e-environment');


const config = require('config');
const virtualProductName1 = 'Virtual Product Name 1';
const virtualProductName2 = 'Virtual Product Name 2';
const virtualProductName3 = 'Virtual Product Name 3';
const customerBillingAddress = 'customer1@gmail.com'
const virtualProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const testConfig = getTestConfig();
const downloadableFileName = 'Downloadable File 1'
const downloadableFileUrl = testConfig.url + 'wp-content/uploads/2021/11/sample_products_override.csv'

let orderId;
let newOrderId;

const openNewProductAndVerify = async () => {
	// Go to "add product" page
	await merchant.openNewProduct();

	// Make sure we're on the add product page
	await expect(page.title()).resolves.toMatch('Add new product');
}

const createNewProductWithoutDownloadableFile = async (productName, virtualProductPrice) => {
	await openNewProductAndVerify();

	// Set product data and publish the product
	await expect(page).toFill('#title', productName);
	await expect(page).toClick('#_virtual');
	await clickTab('General');
	await expect(page).toFill('#_regular_price', virtualProductPrice);
	await expect(page).toClick('#_downloadable');
	await verifyAndPublish();
}

const createNewProductWithDownloadableFile = async (productName, virtualProductPrice, downloadableFileName, downloadableFileUrl) => {
	await openNewProductAndVerify();

	// Set product data and publish the product
	await expect(page).toFill('#title', productName);
	await expect(page).toClick('#_virtual');
	await clickTab('General');
	await expect(page).toFill('#_regular_price', virtualProductPrice);
	await expect(page).toClick('#_downloadable');
	await expect(page).toClick('.downloadable_files>table>tfoot>tr>th>a')
	await page.waitForSelector('.downloadable_files>table>tfoot>tr>th>a');
	await expect(page).toFill('.file_name>input', downloadableFileName);
	await expect(page).toFill('.file_url>input', downloadableFileUrl);
	await verifyAndPublish();
}

const createNewOrderWithExistingProduct = async (productName, customerBillingAddress) => {

	// Go to "add order" page
	await merchant.openNewOrder();
	// Make sure we're on the add order page
	await expect(page.title()).resolves.toMatch('Add new order');

	// Set product data and publish the product
	await expect(page).toSelect('#order_status', 'Processing');
	await utils.waitForTimeout(2000)
	await expect(page).toClick('.order_data_column_container > div:nth-child(2) > h3 > a');
	await page.waitForSelector('#_billing_email');
	await expect(page).toFill('#_billing_email', customerBillingAddress);
	await expect(page).toClick('button.add-line-item');
	await expect(page).toClick('button.add-order-item');
	await page.waitForSelector('.wc-backbone-modal-header');
	await expect(page).toClick('.wc-backbone-modal-content .wc-product-search');
	await expect(page).toFill('#wc-backbone-modal-dialog + .select2-container .select2-search__field', productName);
	await page.waitForSelector('li[aria-selected="true"]', { timeout: 10000 });
	await expect(page).toClick('li[aria-selected="true"]');
	await page.click('.wc-backbone-modal-content #btn-ok');
	await backboneUnblocked();
	await expect(page).toClick('button.save_order');
	await page.waitForSelector('#message');
	//Verify
	await expect(page).toMatchElement('#message', { text: 'Order updated.' });
	const variablePostId = await page.$('#post_ID');
	let variablePostIdValue = (await (await variablePostId.getProperty('value')).jsonValue());
	return variablePostIdValue;
}

const merchantCanAddDownloadableFileToExistingOrder = async (orderId, productName) => {
	await merchant.openAllOrdersView();

	// Make sure we're on the orders page
	await expect(page.title()).resolves.toMatch('Orders');

	//Open order we created
	await merchant.goToOrder(orderId);

	// Make sure we're on the order details page
	await expect(page.title()).resolves.toMatch('Edit order');
	await expect(page).toClick('.select2-container .select2-search__field');
	await expect(page).toFill('.select2-container .select2-search__field', productName);
	// Wait for options to load
	await utils.waitForTimeout(2000)
	await page.waitForSelector('.select2-results__option');
	await expect(page).toClick('.select2-results__option');
	await backboneUnblocked();
	await expect(page).toClick('.button.grant_access');
	// Wait for auto save
	await utils.waitForTimeout(2000)
	// Save the order changes
	await expect(page).toClick('button.save_order');
	await page.waitForSelector('#message');

	// Verify
	await expect(page).toMatchElement('#message', { text: 'Order updated.' });
}

const merchantCanDeleteDownloadableFileFromExistingOrder = async (orderId) => {
	await merchant.openAllOrdersView();

	// Make sure we're on the orders page
	await expect(page.title()).resolves.toMatch('Orders');

	//Open order we created
	await merchant.goToOrder(orderId);
	await expect(page).toClick('.button.revoke_access');
	await page.keyboard.press('Enter');
	// Wait for auto save
	await utils.waitForTimeout(2000)
	// Save the order changes
	await expect(page).toClick('button.save_order');
	await page.waitForSelector('#message');
	// Verify
	await expect(page).toMatchElement('#message', { text: 'Order updated.' });
}


const runAddDownloadableProductToOrderTest = () => {

	describe('WooCommerce Products > Merchant can add downloadable product to order', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		afterAll(async () => {
			// Delete created products and orders
			await withRestApi.deleteAllProducts();
			await withRestApi.deleteAllOrders();
		});

		it('Merchant create a product without downloadable file', async () => {
			await createNewProductWithoutDownloadableFile(virtualProductName1, virtualProductPrice);
		});

		it('Merchant creates an order', async () => {
			orderId = await createNewOrderWithExistingProduct(virtualProductName1, customerBillingAddress);
		});

		it('Merchant creates a new downloadable product', async () => {
			await createNewProductWithDownloadableFile(virtualProductName2, virtualProductPrice, downloadableFileName, downloadableFileUrl);
		});

		it('Merchant can add downloadable product in existing order', async () => {
			await merchantCanAddDownloadableFileToExistingOrder(orderId, virtualProductName2);
			newOrderId = parseInt(orderId) + 1;
			//Verify that user is able to add downloadable file in existing order without any error
			await expect(page).toMatchElement(
				'#woocommerce-order-downloads strong',
				{
					text: '#' + newOrderId + ' — Virtual Product Name 2 — Downloadable File 1: sample_products_override.csv — Downloaded 0 times',
				}
			);

		});

	});

	describe('WooCommerce Products > Merchant can add multiple downloadable product to order', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		afterAll(async () => {
			// Delete created products and orders
			await withRestApi.deleteAllProducts();
			await withRestApi.deleteAllOrders();
		});
		it('Merchant create a product without downloadable file', async () => {
			await createNewProductWithoutDownloadableFile(virtualProductName1, virtualProductPrice);
		});

		it('Merchant creates an order', async () => {
			orderId = await createNewOrderWithExistingProduct(virtualProductName1, customerBillingAddress);
		});

		it('Merchant creates multiple new downloadable product', async () => {
			await createNewProductWithDownloadableFile(virtualProductName2, virtualProductPrice, downloadableFileName, downloadableFileUrl);
			await createNewProductWithDownloadableFile(virtualProductName3, virtualProductPrice, downloadableFileName, downloadableFileUrl);

		});

		it('Merchant can add multiple downloadable product in existing order', async () => {

			await merchantCanAddDownloadableFileToExistingOrder(orderId, virtualProductName2);
			newOrderId = parseInt(orderId) + 1;
			//Verify that user is able to add downloadable file in existing order without any error
			await expect(page).toMatchElement(
				'#woocommerce-order-downloads strong',
				{
					text: '#' + newOrderId + ' — Virtual Product Name 2 — Downloadable File 1: sample_products_override.csv — Downloaded 0 times',
				}
			);

			await merchantCanAddDownloadableFileToExistingOrder(orderId, virtualProductName3);
			newOrderId = parseInt(newOrderId) + 1;
			//Verify that user is able to add downloadable file in existing order without any error
			await expect(page).toMatchElement(
				'#woocommerce-order-downloads strong',
				{
					text: '#' + newOrderId + ' — Virtual Product Name 3 — Downloadable File 1: sample_products_override.csv — Downloaded 0 times',
				}
			);
		});


	});


	describe('WooCommerce Products > Merchant can delete downloadable product from order', () => {
		beforeAll(async () => {
			await merchant.login();
		});
		afterAll(async () => {
			// Delete created products and orders
			await withRestApi.deleteAllProducts();
			await withRestApi.deleteAllOrders();
		});

		it('Merchant create a product without downloadable file', async () => {
			await createNewProductWithoutDownloadableFile(virtualProductName1, virtualProductPrice);
		});

		it('Merchant creates an order', async () => {
			orderId = await createNewOrderWithExistingProduct(virtualProductName1, customerBillingAddress);
		});

		it('Merchant creates a new downloadable product', async () => {
			await createNewProductWithDownloadableFile(virtualProductName2, virtualProductPrice, downloadableFileName, downloadableFileUrl);
		});

		it('Merchant can add downloadable product in existing order', async () => {
			await merchantCanAddDownloadableFileToExistingOrder(orderId, virtualProductName2);
			newOrderId = parseInt(orderId) + 1;
			//Verify that user is able to add the downloadable product and it is listed under Downloadable product permissions section
			await expect(page).toMatchElement(
				'#woocommerce-order-downloads strong',
				{
					text: '#' + newOrderId + ' — Virtual Product Name 2 — Downloadable File 1: sample_products_override.csv — Downloaded 0 times',
				}
			);

		});

		it('Merchant can delete downloadable product from existing order', async () => {
			await merchantCanDeleteDownloadableFileFromExistingOrder(orderId, virtualProductName2);
			//Verify that user is able to revoke/delete downloadable product listed under Downloadable product permissions section
			await page.waitForSelector('#woocommerce-order-downloads strong', { hidden: true });
		});


	});


};


module.exports = runAddDownloadableProductToOrderTest;
