/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
 const {
	merchant,
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
	await expect(page).toMatchElement(element, elementText);
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
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('products');
			await checkHeadingAndElement('Products');
		});

		it('can see Revenue page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('revenue');
			await checkHeadingAndElement('Revenue');
		});

		it('can see Orders page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('orders');
			await checkHeadingAndElement('Orders');
		});

		it('can see Variations page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('variations');
			await checkHeadingAndElement('Variations');
		});

		it('can see Categories page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('categories');
			await checkHeadingAndElement('Categories');
		});

		it('can see Coupons page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('coupons');
			await checkHeadingAndElement('Coupons');
		});

		it('can see Taxes page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('taxes');
			await checkHeadingAndElement('Taxes');
		});

		it('can see Downloads page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('downloads');
			await checkHeadingAndElement('Downloads');
		});

		it('can see Stock page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('stock');
			await checkHeadingAndElement('Stock', '.woocommerce-table__empty-item', 'No data to display');
		});

		it('can see Settings page properly', async () => {
			// Go to "overview" page and verify it
			await merchant.openAnalyticsPage('settings');
			await checkHeadingAndElement('Settings', 'h2', 'Analytics Settings');
		});
	});
}

module.exports = runAnalyticsPageLoadsTest;
