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
const merchant = require('../../../utils/src/flows/merchant');
const filePath = '../../../sample-data/sample_products.csv';
const productNames = ["V-Neck T-Shirt", "Hoodie", "Hoodie with Logo", "T-Shirt", "Beanie",
			"Belt", "Cap", "Sunglasses", "Hoodie with Pocket", "Hoodie with Zipper", "Long Sleeve Tee",
			"Polo", "Album", "Single", "T-Shirt with Logo", "Beanie with Logo", "Logo Collection", "WordPress Pennant"].sort();
const errorMessage = 'Invalid file type. The importer supports CSV and TXT file formats.';

const runImportProductsTest = () => {
	describe('Import Products from a CSV file', () => {
		beforeAll(async () => {
            await merchant.login();
            await merchant.openImportProducts();
        });
		it('can upload the CSV file and import products', async () => {
            // Verify error message if you go withot provided CSV file
            await expect(page).toClick('button[value="Continue"]');
            await page.waitForSelector('div.error inline');
            await expect(page).toMatchElement('div.error inline > p', errorMessage);

			// Put the CSV products file and proceed further
			const uploader = await page.$("input[type=file]");
			await uploader.uploadFile(filePath);
			await setCheckbox('#woocommerce-importer-update-existing');
            await page.waitFor(2000);
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
			await expect(productNames).toEqual(linkTexts.sort());
		});
	});
};

module.exports = runImportProductsTest;
