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

const runInitiateWccomConnectionTest = () => {
	describe('Merchant > Initiate WCCOM Connection', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it.skip('can initiate WCCOM connection', async () => {
			await merchant.openHelper();

			// Click on Connect button to initiate a WCCOM connection
			await Promise.all([
				expect(page).toClick('.button-helper-connect'),
				page.waitForNavigation({waitUntil: 'networkidle0'}),
			]);

			// Verify that you see a login page for connecting WCCOM account
			await expect(page).toMatchElement('div.login');
			await expect(page).toMatchElement('input#usernameOrEmail');
			await expect(page).toMatchElement('button.button', {text: "Continue"});
		});
	});
}

module.exports = runInitiateWccomConnectionTest;
