/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
 const {
	merchant,
	setCheckbox,
	moveAllItemsToTrash
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
			await moveAllItemsToTrash();
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

			// Click on view products
			await page.waitForSelector('div.wc-actions > a.button.button-primary');
			await expect(page).toClick('div.wc-actions > a.button.button-primary');

			// Gathering product names
			await page.waitForSelector('a.row-title');
			const productTitles = await page.$$eval('a.row-title',
			 elements => elements.map(item => item.innerHTML));

			// Compare overriden product names
			expect(productTitles.sort()).toEqual(productNamesOverride.sort());

			// Gathering product prices
			await page.waitForSelector('td.price.column-price');
			const productPrices = await page.$$eval('.amount',
			 elements => elements.map(item => item.innerText));

			// Compare overriden product prices
			expect(productPrices.sort()).toEqual(productPricesOverride.sort());
		});

		afterAll(async () => {
			// Remove all the imported products
			await page.waitForSelector('#cb-select-all-1', {visible:true});
			await moveAllItemsToTrash();
			await page.waitForSelector('ul.subsubsub li.trash a', {visible:true});
			await page.click('ul.subsubsub li.trash a');
			await page.waitForSelector('#delete_all', {visible:true});
			await page.click('#delete_all');
			await page.waitForSelector('a.woocommerce-BlankState-cta.button-primary.button ~ a', {visible:true});
		});
	});
};

module.exports = runImportProductsTest;
