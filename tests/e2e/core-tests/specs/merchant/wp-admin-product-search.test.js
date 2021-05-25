/* eslint-disable jest/no-export, jest/no-standalone-expect */

/**
 * Internal dependencies
 */
const {
	merchant,
	createSimpleProduct
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';

const runProductSearchTest = () => {
	describe('Products > Search and View a product', () => {
		beforeAll(async () => {
			await createSimpleProduct();

			// Make sure the simple product name is greater than 1 to do a search
			await expect(simpleProductName.length).toBeGreaterThan(1);

			await merchant.login();
		});

		beforeEach(async () => {
			await merchant.openAllProductsView();
		});

		it('can do a partial search for a product', async () => {
			// Create partial search string
			let searchString = simpleProductName.substring(0, (simpleProductName.length / 2));
			await expect(page).toFill('#post-search-input', searchString);

			// Click search and wait for the page to finish loading
			await Promise.all( [
				page.click( '#search-submit' ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);

			// Verify we are getting the results back in the list
			await expect(page).toMatchElement('.row-title', { text: simpleProductName });
		});

		it('can view a product\'s details after search', async () => {
			await expect(page).toFill('#post-search-input', simpleProductName);

			await Promise.all( [
				page.click( '#search-submit' ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);

			// Click to view the product and wait for the page to finish loading
			await Promise.all( [
				expect(page).toClick('.row-title', simpleProductName),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);

			await expect(page).toMatchElement('#title', simpleProductName);
			await expect(page).toMatchElement('#_regular_price', simpleProductPrice);
		});

		it('returns no results for non-existent product search', async () => {
			await expect(page).toFill('#post-search-input', 'abcd1234');

			// Click search and wait for the page to finish loading
			await Promise.all( [
				page.click( '#search-submit' ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			]);

			// Verify we are getting the results back in the list
			await expect(page).toMatchElement('.no-items', { text: 'No products found' });
		});
	});
}

module.exports = runProductSearchTest;
