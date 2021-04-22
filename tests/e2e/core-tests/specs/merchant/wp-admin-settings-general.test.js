/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	settingsPageSaveChanges,
	verifyValueOfInputField
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runUpdateGeneralSettingsTest = () => {
	describe('WooCommerce General Settings', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can update settings', async () => {
			// Go to general settings page
			await merchant.openSettings('general');

			// Make sure the general tab is active
			await expect(page).toMatchElement('a.nav-tab-active', {text: 'General'});

			// Set selling location to all countries first,
			// so we can choose california as base location.
			await expect(page).toSelect('#woocommerce_allowed_countries', 'Sell to all countries');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				expect(page).toMatchElement('#woocommerce_allowed_countries', {text: 'Sell to all countries'}),
			]);

			// Set base location with state CA.
			await expect(page).toSelect('select[name="woocommerce_default_country"]', 'United States (US) — California');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				expect(page).toMatchElement('select[name="woocommerce_default_country"]', {text: 'United States (US) — California'}),
			]);

			// Set selling location to specific countries first, so we can choose U.S as base location (without state).
			// This will makes specific countries option appears.
			await expect(page).toSelect('#woocommerce_allowed_countries', 'Sell to specific countries');
			await expect(page).toSelect('select[name="woocommerce_specific_allowed_countries[]"]', 'United States (US)');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				expect(page).toMatchElement('#woocommerce_allowed_countries', {text: 'Sell to specific countries'}),
				expect(page).toMatchElement('select[name="woocommerce_specific_allowed_countries[]"]', {text: 'United States (US)'}),
			]);

			// Set currency options.
			await expect(page).toFill('#woocommerce_price_thousand_sep', ',');
			await expect(page).toFill('#woocommerce_price_decimal_sep', '.');
			await expect(page).toFill('#woocommerce_price_num_decimals', '2');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				verifyValueOfInputField('#woocommerce_price_thousand_sep', ','),
				verifyValueOfInputField('#woocommerce_price_decimal_sep', '.'),
				verifyValueOfInputField('#woocommerce_price_num_decimals', '2'),
			]);
		});
	});
};

module.exports = runUpdateGeneralSettingsTest;
