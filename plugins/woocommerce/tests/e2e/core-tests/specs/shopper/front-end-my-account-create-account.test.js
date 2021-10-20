/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	setCheckbox,
	settingsPageSaveChanges,
	withRestApi,
} = require( '@woocommerce/e2e-utils' );

const customerEmailAddress = 'john.doe.test@example.com';

const runMyAccountCreateAccountTest = () => {
	describe('Shopper My Account Create Account', () => {
		beforeAll(async () => {
			await withRestApi.deleteCustomerByEmail( customerEmailAddress );
			await merchant.login();

			// Set checkbox in the settings to enable registration in my account
			await merchant.openSettings('account');
			await setCheckbox('#woocommerce_enable_myaccount_registration');
			await settingsPageSaveChanges();

			await merchant.logout();
		});

		it('can create a new account via my account', async () => {
			await shopper.gotoMyAccount();
			await page.waitForSelector('.woocommerce-form-register');
			await expect(page).toFill('input#reg_email', customerEmailAddress);
			await expect(page).toClick('button[name="register"]');
			await page.waitForNavigation({waitUntil: 'networkidle0'});
			await expect(page).toMatchElement('h1', 'My account');

			// Verify user has been created successfully
			await merchant.login();
			await merchant.openAllUsersView();
			await expect(page).toMatchElement('td.email.column-email > a', {text: customerEmailAddress});
		});
	});
};

module.exports = runMyAccountCreateAccountTest;
