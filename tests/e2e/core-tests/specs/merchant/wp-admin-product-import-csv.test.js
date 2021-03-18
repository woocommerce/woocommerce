/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
 const {
	merchant,
	setCheckbox
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe
} = require( '@jest/globals' );

const filePath = '../../../sample-data/sample_products.csv';
const filePathOverride = '../../../sample-data/sample_products_override.csv';
const productNames = ["V-Neck T-Shirt", "Hoodie", "Hoodie with Logo", "T-Shirt", "Beanie",
	"Belt", "Cap", "Sunglasses", "Hoodie with Pocket", "Hoodie with Zipper", "Long Sleeve Tee", "Polo", "Album",
	"Single", "T-Shirt with Logo", "Beanie with Logo", "Logo Collection", "WordPress Pennant"].sort();
const productNamesOverride = ["V-Neck T-Shirt Override", "Hoodie Override", "Hoodie with Logo Override",
	"T-Shirt Override", "Beanie Override", "Belt Override", "Cap Override", "Sunglasses Override",
	"Hoodie with Pocket Override", "Hoodie with Zipper Override", "Long Sleeve Tee Override",
	"Polo Override", "Album Override", "Single Override", "T-Shirt with Logo Override", "Beanie with Logo Override",
	"Logo Collection Override", "WordPress Pennant Override"].sort();
const productPricesOverride = ["145", "118", "120", "118", "165", "155", "118", "116", "190", "145",
	"135", "145", "125", "120", "115", "13", "12", "120", "120", "115", "145", "142",
	"145", "145", "118","120", "118", "111.05", "145"].sort();
const errorMessage = 'Invalid file type. The importer supports CSV and TXT file formats.';

const runImportProductsTest = () => {
	describe('Import Products from a CSV file', () => {
		beforeAll(async () => {
			await merchant.login();
		});
		it('can upload the CSV file and import products', async () => {
			await merchant.openImportProducts();

			// Verify error message if you go withot provided CSV file
			await expect(page).toClick('button[value="Continue"]');
			await page.waitForSelector('div.error');
			await expect(page).toMatchElement('div.error > p', errorMessage);

			// Put the CSV products file and proceed further
			const uploader = await page.$("input[type=file]");
			await uploader.uploadFile(filePath);
			await expect(page).toClick('button[value="Continue"]');

			// Click on Run the importer
			await page.waitForSelector('button[value="Run the importer"]');
			await expect(page).toClick('button[value="Run the importer"]');

			// Waiting for importer to finish
			await page.waitForSelector('section.woocommerce-importer-done', {visible:true, timeout: 60000});
			await page.waitForSelector('.woocommerce-importer-done');
			await expect(page).toMatchElement('.woocommerce-importer-done', {text: 'Import complete!'});

			// Click on view products
			await page.waitForSelector('div.wc-actions > a.button.button-primary');
			await expect(page).toClick('div.wc-actions > a.button.button-primary');

			// Gathering product names
			await page.waitForSelector('a.row-title');
			let productTitles = await page.$$eval('a.row-title',
			 elements => elements.map(item => item.innerHTML));

			// Compare imported product names
			expect(productNames).toContain(productTitles.sort());
		});

		it('can override the existing products via CSV import', async () => {
			await merchant.openImportProducts();

			// Put the CSV Override products file, set checkbox and proceed further
			const uploader = await page.$("input[type=file]");
			await uploader.uploadFile(filePathOverride);
			await setCheckbox('#woocommerce-importer-update-existing');
			await expect(page).toClick('button[value="Continue"]');

			// Click on Run the importer
			await page.waitForSelector('button[value="Run the importer"]');
			await expect(page).toClick('button[value="Run the importer"]');

			// Waiting for importer to finish
			await page.waitForSelector('section.woocommerce-importer-done', {visible:true, timeout: 60000});
			await page.waitForSelector('.woocommerce-importer-done');
			await expect(page).toMatchElement('.woocommerce-importer-done', {text: 'Import complete!'});

			// Click on view products
			await page.waitForSelector('div.wc-actions > a.button.button-primary');
			await expect(page).toClick('div.wc-actions > a.button.button-primary');

			// Gathering product names
			await page.waitForSelector('a.row-title');
			let productTitles = await page.$$eval('a.row-title',
			 elements => elements.map(item => item.innerHTML));

			// Compare overriden product names
			expect(productNamesOverride).toContain(productTitles.sort());

			// Gathering product prices
			await page.waitForSelector('td.price.column-price');
			let productPrices = await page.$$eval('td.price.column-price > .amount',
			 elements => elements.map(item => item.text));

			// Compare overriden product prices
			expect(productPricesOverride).toContain(productPrices.sort());
		});
	});
};

module.exports = runImportProductsTest;
