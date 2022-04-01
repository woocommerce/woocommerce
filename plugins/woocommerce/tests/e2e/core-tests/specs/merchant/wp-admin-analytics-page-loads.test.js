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

 // Analytics pages that we'll test against
const pages = [
	['Overview'],
	['Products'],
	['Revenue'],
	['Orders'],
	['Variations'],
	['Categories'],
	['Coupons'],
	['Taxes'],
	['Downloads'],
	['Stock', '.components-button > span', 'Product / Variation'],
	['Settings', 'h2', 'Analytics Settings']
];

const runAnalyticsPageLoadsTest = () => {
	describe('Analytics > Opening Top Level Pages', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it.each(pages)(
			'can see %s page properly',
			async (pageTitle, element, elementText) => {
				// Go to the desired page and verify it
				await merchant.openAnalyticsPage(pageTitle.toLowerCase());
				await checkHeadingAndElement(pageTitle, element, elementText);
			}
		);
	});
}

module.exports = runAnalyticsPageLoadsTest;
