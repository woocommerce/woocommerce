/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
 const {
	merchant,
	waitForSelectorWithoutThrow,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

/**
 * Quick check for page title and no data message.
 *
 * @param pageTitle Page title in H1.
 * @param element Defaults to '.d3-chart__empty-message'
 * @param elementText Defaults to 'No data for the selected date range'
 */
const checkHeadingAndElement = async (
	pageTitle, element = '.d3-chart__empty-message', elementText = 'No data for the selected date range') => {
	await expect(page).toMatchElement('h1', {text: pageTitle});

	// Depending on order of tests the chart may not be empty.
	const found = await waitForSelectorWithoutThrow( element );
	if ( found ) {
		await expect(page).toMatchElement(element, {text: elementText});
	} else {
		await expect(page).toMatchElement( '.woocommerce-chart' );
	}
 };

const runAnalyticsPageLoadsTest = () => {
	describe('Analytics > Opening Top Level Pages', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can see Overview page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('overview');
			await checkHeadingAndElement('Overview');
		});

		it('can see Products page properly', async () => {
			// Go to "products" page and verify it
			await merchant.openAnalyticsPage('products');
			await checkHeadingAndElement('Products');
		});

		it('can see Revenue page properly', async () => {
			// Go to "revenue" page and verify it
			await merchant.openAnalyticsPage('revenue');
			await checkHeadingAndElement('Revenue');
		});

		it('can see Orders page properly', async () => {
			// Go to "orders" page and verify it
			await merchant.openAnalyticsPage('orders');
			await checkHeadingAndElement('Orders');
		});

		it('can see Variations page properly', async () => {
			// Go to "variations" page and verify it
			await merchant.openAnalyticsPage('variations');
			await checkHeadingAndElement('Variations');
		});

		it('can see Categories page properly', async () => {
			// Go to "categories" page and verify it
			await merchant.openAnalyticsPage('categories');
			await checkHeadingAndElement('Categories');
		});

		it('can see Coupons page properly', async () => {
			// Go to "coupons" page and verify it
			await merchant.openAnalyticsPage('coupons');
			await checkHeadingAndElement('Coupons');
		});

		it('can see Taxes page properly', async () => {
			// Go to "taxes" page and verify it
			await merchant.openAnalyticsPage('taxes');
			await checkHeadingAndElement('Taxes');
		});

		it('can see Downloads page properly', async () => {
			// Go to "downloads" page and verify it
			await merchant.openAnalyticsPage('downloads');
			await checkHeadingAndElement('Downloads');
		});

		it('can see Stock page properly', async () => {
			// Go to "stock" page and verify it
			await merchant.openAnalyticsPage('stock');
			await checkHeadingAndElement('Stock', '.components-button > span', 'Product / Variation');
		});

		it('can see Settings page properly', async () => {
			// Go to "settings" page and verify it
			await merchant.openAnalyticsPage('settings');
			await checkHeadingAndElement('Settings', 'h2', 'Analytics Settings');
		});
	});
}

module.exports = runAnalyticsPageLoadsTest;
