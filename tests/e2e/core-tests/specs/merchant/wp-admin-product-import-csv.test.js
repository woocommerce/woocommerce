/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
 const {
	merchant,
	setCheckbox,
	withRestApi,
} = require( '@woocommerce/e2e-utils' );
const getCoreTestsRoot = require( '../../core-tests-root' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const path = require( 'path' );
const coreTestsPath = getCoreTestsRoot();
const filePath = path.resolve( coreTestsPath.appRoot, 'sample-data/sample_products.csv' );
const filePathOverride = path.resolve( coreTestsPath.packageRoot, 'test-data/sample_products_override.csv' );
const productNames = ["V-Neck T-Shirt", "Hoodie", "Hoodie with Logo", "T-Shirt", "Beanie",
	"Belt", "Cap", "Sunglasses", "Hoodie with Pocket", "Hoodie with Zipper", "Long Sleeve Tee", "Polo", "Album",
	"Single", "T-Shirt with Logo", "Beanie with Logo", "Logo Collection", "WordPress Pennant"];
const productNamesOverride = ["V-Neck T-Shirt Override", "Hoodie Override", "Hoodie with Logo Override",
	"T-Shirt Override", "Beanie Override", "Belt Override", "Cap Override", "Sunglasses Override",
	"Hoodie with Pocket Override", "Hoodie with Zipper Override", "Long Sleeve Tee Override",
	"Polo Override", "Album Override", "Single Override", "T-Shirt with Logo Override", "Beanie with Logo Override",
	"Logo Collection Override", "WordPress Pennant Override"];
const productPricesOverride = ["$111.05", "$118.00", "$145.00", "$120.00", "$118.00", "$118.00", "$13.00", "$12.00",
	"$115.00", "$120.00", "$125.00", "$145.00", "$145.00", "$135.00", "$190.00", "$118.00", "$116.00",
	"$165.00", "$155.00", "$120.00", "$118.00", "$118.00", "$145.00", "$142.00", "$145.00", "$115.00", "$120.00"];
const errorMessage = 'Invalid file type. The importer supports CSV and TXT file formats.';

const runImportProductsTest = () => {
	describe('Import Products from a CSV file', () => {
		beforeAll(async () => {
			await merchant.login();
			await merchant.openAllProductsView();
			await merchant.openImportProducts();
		});
		it('should show error message if you go without providing CSV file', async () => {
			// Verify the error message if you go without providing CSV file
			await Promise.all( [
				page.click( 'button[value="Continue"]' ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);
			await page.waitForSelector('div.error');
			await expect(page).toMatchElement('div.error > p', errorMessage);
		});

		it('can upload the CSV file and import products', async () => {
			// Put the CSV products file and proceed further
			const uploader = await page.$("input[type=file]");
			await uploader.uploadFile(filePath);
			await expect(page).toClick('button[value="Continue"]');

			// Click on Run the importer
			await page.waitForSelector('button[value="Run the importer"]');
			await expect(page).toClick('button[value="Run the importer"]');

			// Waiting for importer to finish
			await page.waitForSelector('section.woocommerce-importer-done', {visible:true, timeout: 120000});
			await page.waitForSelector('.woocommerce-importer-done');
			await expect(page).toMatchElement('.woocommerce-importer-done', {text: 'Import complete!'});
		});

		it('can see and verify the uploaded products', async () => {
			// Click on view products
			await page.waitForSelector('div.wc-actions > a.button.button-primary');
			await expect(page).toClick('div.wc-actions > a.button.button-primary');

			// Gathering product names
			await page.waitForSelector('a.row-title');
			const productTitles = await page.$$eval('a.row-title',
			elements => elements.map(item => item.innerHTML));

			// Compare imported product names
			expect(productTitles.sort()).toEqual(productNames.sort());
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
		});

		it('can see and verify the uploaded overrode products', async () => {
			// Click on view products
			await page.waitForSelector('div.wc-actions > a.button.button-primary');
			await expect(page).toClick('div.wc-actions > a.button.button-primary');

			// Gathering product names
			await page.waitForSelector('a.row-title');
			const productTitles = await page.$$eval(
				'a.row-title',
				elements => elements.map(item => item.innerHTML)
			);

			// Compare overridden product names
			expect(productTitles.sort()).toEqual(productNamesOverride.sort());

			// Gathering product prices
			await page.waitForSelector('td.price.column-price');
			const productPrices = await page.$$eval('.amount',
			 elements => elements.map(item => item.innerText));

			// Compare overridden product prices
			expect(productPrices.sort()).toEqual(productPricesOverride.sort());

			// Delete imported products
			await withRestApi.deleteAllProducts();
		});
	});
};

module.exports = runImportProductsTest;
