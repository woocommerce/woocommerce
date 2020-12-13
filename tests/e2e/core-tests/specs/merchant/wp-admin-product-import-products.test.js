/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
} = require( '@jest/globals' );
const path = require('path')

const runImportProductsTest = () => {
	describe('Import Products from a CSV file', () => {
		it('Import example file', async () => {

			// Go to "Products" page
			// await expect(page).toClick('#menu-posts-product a')


			let filePath;
			filePath = path.relative(process.cwd(), __dirname).concat('/sample-data/sample_products.csv');

			// Click start import
			await expect(page).toClick('a.woocommerce-BlankState-cta.button-primary.button ~ a');



			// const fs = require('fs');
			// const readFileAsync = helper.promisify(fs.readFile);
			// debugger;
			// const promises = filePaths.map(filePath => readFileAsync(filePath));
			//

			await page.waitForSelector('#upload');
			await page.click('#upload');
			const input = await page.$('input[type=file]');
			debugger;

			await input.uploadFile(filePath);
			await page.click('#upload');

			// await input.evaluate(upload => upload.dispatchEvent(new Event('change', { bubbles: true })));

			debugger;


		});
	});
};

module.exports = runImportProductsTest;
