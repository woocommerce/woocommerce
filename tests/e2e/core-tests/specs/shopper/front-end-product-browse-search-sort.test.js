/**
 * Internal dependencies
 */
const {
	shopper,
	createSimpleProductWithCategory,
	utils,
	getEnvironmentContext,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const singleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const singleProductPrice2 = config.has('products.simple.price') ? '1' + singleProductPrice : '19.99';
const singleProductPrice3 = config.has('products.simple.price') ? '2' + singleProductPrice : '29.99';
const clothing = 'Clothing';
const audio = 'Audio';
const hardware = 'Hardware';
const productTitle = 'li.first > a > h2.woocommerce-loop-product__title';

const getWordPressVersion = async () => {
	const context = await getEnvironmentContext();
	return context.wpVersion;
}

const runProductBrowseSearchSortTest = () => {
	utils.describeIf( getWordPressVersion() >= 5.8 )( 'Search, browse by categories and sort items in the shop', () => {
		beforeAll(async () => {
			// Create 1st product with Clothing category
			await createSimpleProductWithCategory(simpleProductName + ' 1', singleProductPrice, clothing);
			// Create 2nd product with Audio category
			await createSimpleProductWithCategory(simpleProductName + ' 2', singleProductPrice2, audio);
			// Create 3rd product with Hardware category
			await createSimpleProductWithCategory(simpleProductName + ' 3', singleProductPrice3, hardware);
		});

		it('should let user search the store', async () => {
			await shopper.goToShop();
			await shopper.searchForProduct(simpleProductName + ' 1');
			page.waitForNavigation({waitUntil: 'networkidle0'});
		});

		it('should let user browse products by categories', async () => {
			// Browse through Clothing category link
			await Promise.all([
				page.click('span.posted_in > a', {text: clothing}),
				page.waitForNavigation({waitUntil: 'networkidle0'}),
			]);

			// Verify Clothing category page
			await page.waitForSelector(productTitle);
			await expect(page).toMatchElement(productTitle, {text: simpleProductName + ' 1'});
			await expect(page).toClick(productTitle, {text: simpleProductName + ' 1'});
			page.waitForNavigation({waitUntil: 'networkidle0'});
			await page.waitForSelector('h1.entry-title');
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
