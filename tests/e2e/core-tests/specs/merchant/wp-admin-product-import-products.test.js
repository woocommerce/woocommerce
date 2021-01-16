/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	moveAllItemsToTrash,
	setCheckbox
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	afterEach
} = require( '@jest/globals' );
const path = require('path')

const runImportProductsTest = () => {
	describe('Import Products from a CSV file', () => {
		it('Import example file', async () => {

			let filePath = path.resolve('../../../sample-data/sample_products.csv');

			const product_names = ["V-Neck T-Shirt", "Hoodie", "Hoodie with Logo", "T-Shirt", "Beanie",
				"Belt", "Cap", "Sunglasses", "Hoodie with Pocket", "Hoodie with Zipper", "Long Sleeve Tee",
				"Polo", "Album", "Single", "T-Shirt with Logo", "Beanie with Logo", "Logo Collection", "WordPress Pennant"].sort();

			// Click start import
			await expect(page).toClick('a.woocommerce-BlankState-cta.button-primary.button ~ a');

			// Upload file
			await page.waitForSelector('#upload');
			const input = await page.$('input[type=file]');
			await input.uploadFile(filePath);

			// Click on Continue
			await expect(page).toClick('button[value="Continue"]');

			// Click on Run the importer
			await page.waitForSelector('button[value="Run the importer"]');
			await expect(page).toClick('button[value="Run the importer"]');

			// Waiting for importer to finish
			await page.waitForSelector('section.woocommerce-importer-done', {visible:true, timeout: 60000});

			// Click on view products
			await page.waitForSelector('div.wc-actions a.button.button-primary');
			await expect(page).toClick('div.wc-actions a.button.button-primary');

			// Getting product names
			await page.waitForSelector('a.row-title');
			let linkTexts = await page.$$eval('a.row-title', elements => elements.map(item => item.innerHTML));

			// Compare imported product names
			await expect(product_names).toEqual(linkTexts.sort());
		});


		it('Import example file and update existing products', async () => {

			let filePath = path.resolve('../../../sample-data/sample_products.csv');

			const product_names = ["V-Neck T-Shirt", "Hoodie", "Hoodie with Logo", "T-Shirt", "Beanie",
				"Belt", "Cap", "Sunglasses", "Hoodie with Pocket", "Hoodie with Zipper", "Long Sleeve Tee",
				"Polo", "Album", "Single", "T-Shirt with Logo", "Beanie with Logo", "Logo Collection", "WordPress Pennant"].sort();

			const product_prices = []

			// Click import
			await expect(page).toClick('a[href*="product_importer"]');

			// Upload file
			await page.waitForSelector('#upload');
			const input = await page.$('input[type=file]');
			await input.uploadFile(filePath);

			// Check 'Update existing products' checkbox
			setCheckbox('#woocommerce-importer-update-existing')

			// Click on Continue
			await expect(page).toClick('button[value="Continue"]');

			// Click on Run the importer
			await page.waitForSelector('button[value="Run the importer"]');
			await expect(page).toClick('button[value="Run the importer"]');

			// Waiting for importer to finish
			await page.waitForSelector('section.woocommerce-importer-done', {visible:true, timeout: 60000});

			// Click on view products
			await page.waitForSelector('div.wc-actions a.button.button-primary');
			await expect(page).toClick('div.wc-actions a.button.button-primary');

			// Getting product names
			await page.waitForSelector('td.price.column-price');
			let prices = await page.$$eval('td.price.column-price', elements => elements.map(item => item.text));

			debugger;
			// Compare imported product names
			await expect(product_names).toEqual(linkTexts.sort());
		});
	});

	afterEach(async () => {
		// Deleting imported products


		// Click on select all
		await page.waitForSelector('#cb-select-all-1', {visible:true});
		moveAllItemsToTrash();

		await page.waitForSelector('ul.subsubsub li.trash a', {visible:true});
		await page.click('ul.subsubsub li.trash a');

		await page.waitForSelector('#delete_all', {visible:true});
		await page.click('#delete_all');


		await page.waitForSelector('a.woocommerce-BlankState-cta.button-primary.button ~ a', {visible:true});
	});
};

module.exports = runImportProductsTest;
