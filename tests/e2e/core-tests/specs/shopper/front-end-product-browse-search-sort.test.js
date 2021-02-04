/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createSimpleProductWithCategory,
	uiUnblocked
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

let variablePostIdValue;
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const singleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const singleProductPrice2 = config.has('products.simple.price') ? config.get('products.simple.price') : '19.99';
const singleProductPrice3 = config.has('products.simple.price') ? config.get('products.simple.price') : '29.99';
const clothing = 'Clothing';
const audio = 'Audio';
const hardware = 'Hardware';
const productTitle = 'li.first > a > h2.woocommerce-loop-product__title';

const runProductBrowseSearchSortTest = () => {
	describe('Search, browse by categories and sort items in the shop', () => {
		beforeAll(async () => {
			await merchant.login();

			// Create 1st product with Clothing category 
			variablePostIdValue = await createSimpleProductWithCategory(simpleProductName + ' 1', singleProductPrice, clothing);

			// Create 2nd product with Audio category 
			await createSimpleProductWithCategory(simpleProductName + ' 2', singleProductPrice2, audio);

			// Create 3rd product with Hardware category 
			await createSimpleProductWithCategory(simpleProductName + ' 3', singleProductPrice3, hardware);
			await merchant.logout();
		});

		it('should let user search the store', async () => {
			await shopper.login();
			await shopper.goToShop();

			// Search for the 1st product
			await expect(page).toFill('.search-field', simpleProductName + ' 1');
			await expect(page).toClick('.search-submit');
			await uiUnblocked();

			// Make sure we're on the search results page
			await expect(page.title()).resolves.toMatch('Search Results for “' + simpleProductName + ' 1”');

			// Verify the results
			await expect(page).toMatchElement('h2.entry-title', {text: simpleProductName + ' 1'});
			await expect(page).toClick('h2.entry-title', {text: simpleProductName + ' 1'});
			await uiUnblocked();
			await expect(page.title()).resolves.toMatch(simpleProductName + ' 1');
			await expect(page).toMatchElement('h1.entry-title', simpleProductName + ' 1');
		});

		it('should let user browse products by categories', async () => {
			// Go to 1st product and click category name
			await shopper.goToProduct(variablePostIdValue);
			await expect(page.title()).resolves.toMatch(simpleProductName + ' 1');
			await Promise.all([
				page.waitForNavigation({waitUntil: 'networkidle0'}),
				page.click('span.posted_in > a', {text: clothing}),
			]);
			await uiUnblocked();

			// Verify Clothing category page
			await expect(page.title()).resolves.toMatch(clothing);
			await expect(page).toMatchElement(productTitle, {text: simpleProductName + ' 1'});

			// Verify clicking on the product
			await expect(page).toClick(productTitle, {text: simpleProductName + ' 1'});
			await uiUnblocked();
			await expect(page.title()).resolves.toMatch(simpleProductName + ' 1');
			await expect(page).toMatchElement('h1.entry-title', simpleProductName + ' 1');
		});

		it('should let user sort the products in the shop', async () => {
			await shopper.goToShop();

			// Sort by price high to low
			await page.select('.orderby', 'price-desc');
            // Verify the first product in sort order
			await expect(page).toMatchElement(productTitle, {text: simpleProductName + ' 3'});

			// Sort by price low to high
			await page.select('.orderby', 'price');
            // Verify the first product in sort order
			await expect(page).toMatchElement(productTitle, {text: simpleProductName + ' 1'});

			// Sort by date of creation, latest to oldest
			await page.select('.orderby', 'date');
            // Verify the first product in sort order
			await expect(page).toMatchElement(productTitle, {text: simpleProductName + ' 3'});
		});
	});
};

module.exports = runProductBrowseSearchSortTest;
