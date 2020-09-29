/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	StoreOwnerFlow,
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	settingsPageSaveChanges,
	verifyCheckboxIsSet,
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

const runInitialStoreSettingsTest = () => {
	describe('Store owner can finish initial store setup', () => {

		it('can enable tax rates and calculations', async () => {
			// Go to general settings page
			await StoreOwnerFlow.openSettings('general');

			// Make sure the general tab is active
			await expect(page).toMatchElement('a.nav-tab-active', {text: 'General'});

			// Enable tax rates and calculations
			await setCheckbox('#woocommerce_calc_taxes');

			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				verifyCheckboxIsSet('#woocommerce_calc_taxes'),
			]);
		});

		it('can configure permalink settings', async () => {
			// Go to Permalink Settings page
			await StoreOwnerFlow.openPermalinkSettings();

			// Select "Post name" option in common settings section
			await page.click('input[value="/%postname%/"]', {text: ' Post name'});

			// Select "Custom base" in product permalinks section
			await page.click('#woocommerce_custom_selection');

			// Fill custom base slug to use
			await expect(page).toFill('#woocommerce_permalink_structure', '/product/');

			await permalinkSettingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#setting-error-settings_updated', {text: 'Permalink structure updated.'}),
				verifyValueOfInputField('#permalink_structure', '/%postname%/'),
				verifyValueOfInputField('#woocommerce_permalink_structure', '/product/'),
			]);
		});
	});
};

module.exports = runInitialStoreSettingsTest;
