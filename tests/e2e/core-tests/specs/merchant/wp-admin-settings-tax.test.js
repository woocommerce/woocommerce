/**
 * Internal dependencies
 */
const {
	merchant,
	clearAndFillInput,
	setCheckbox,
	settingsPageSaveChanges,
	uiUnblocked,
	verifyCheckboxIsSet,
	verifyValueOfInputField,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runTaxSettingsTest = () => {
	describe('WooCommerce Tax Settings', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('can enable tax calculation', async () => {
			// Go to general settings page
			await merchant.openSettings('general');

			// Make sure the general tab is active
			await expect(page).toMatchElement('a.nav-tab-active', {text: 'General'});

			// Enable tax calculation
			await setCheckbox('input[name="woocommerce_calc_taxes"]');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				verifyCheckboxIsSet('#woocommerce_calc_taxes'),
			]);

			// Verify that tax settings are now present
			await expect(page).toMatchElement('a.nav-tab', {text: 'Tax'});
		});

		it('can set tax options', async () => {
			// Go to tax settings page
			await merchant.openSettings('tax');

			// Make sure the tax tab is active
			await expect(page).toMatchElement('a.nav-tab-active', {text: 'Tax'});

			// Prices exclusive of tax
			await expect(page).toClick('input[name="woocommerce_prices_include_tax"][value="no"]');
			// Tax based on customer shipping address
			await expect(page).toSelect('#woocommerce_tax_based_on', 'Customer shipping address');
			// Standard tax class for shipping
			await expect(page).toSelect('#woocommerce_shipping_tax_class', 'Standard');
			// Leave rounding unchecked (no-op)
			// Display prices excluding tax
			await expect(page).toSelect('#woocommerce_tax_display_shop', 'Excluding tax');
			// Display prices including tax in cart and at checkout
			await expect(page).toSelect('#woocommerce_tax_display_cart', 'Including tax');
			// Display a single tax total
			await expect(page).toSelect('#woocommerce_tax_total_display', 'As a single total');

			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				verifyValueOfInputField('input[name="woocommerce_prices_include_tax"][value="no"]', 'no'),
				expect(page).toMatchElement('#woocommerce_tax_based_on', {text: 'Customer shipping address'}),
				expect(page).toMatchElement('#woocommerce_shipping_tax_class', {text: 'Standard'}),
				expect(page).toMatchElement('#woocommerce_tax_display_shop', {text: 'Excluding tax'}),
				expect(page).toMatchElement('#woocommerce_tax_display_cart', {text: 'Including tax'}),
				expect(page).toMatchElement('#woocommerce_tax_total_display', {text: 'As a single total'}),
			]);
		});

		it('can add tax classes', async () => {
			// Go to tax settings page
			await merchant.openSettings('tax');

			// Make sure the tax tab is active
			await expect(page).toMatchElement('a.nav-tab-active', {text: 'Tax'});

			// Remove additional tax classes
			await clearAndFillInput('#woocommerce_tax_classes', '');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				expect(page).toMatchElement('#woocommerce_tax_classes', {text: ''}),
			]);

			// Add a "fancy" tax class
			await clearAndFillInput('#woocommerce_tax_classes', 'Fancy');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				expect(page).toMatchElement('ul.subsubsub > li > a', {text: 'Fancy rates'}),
			]);
		});

		it('can set rate settings', async () => {
			// Go to "fancy" rates tax settings page
			await merchant.openSettings('tax', 'fancy');

			// Make sure the tax tab is active, with the "fancy" subsection
			await expect(page).toMatchElement('a.nav-tab-active', {text: 'Tax'});
			await expect(page).toMatchElement('ul.subsubsub > li > a.current', {text: 'Fancy rates'});

			// Create a state tax
			await expect(page).toClick('.wc_tax_rates a.insert');
			await expect(page).toFill('input[name^="tax_rate_country[new-0"]', 'US');
			await expect(page).toFill('input[name^="tax_rate_state[new-0"]', 'CA');
			await expect(page).toFill('input[name^="tax_rate[new-0"]', '7.5');
			await expect(page).toFill('input[name^="tax_rate_name[new-0"]', 'CA State Tax');

			// Create a federal tax
			await expect(page).toClick('.wc_tax_rates a.insert');
			await expect(page).toFill('input[name^="tax_rate_country[new-1"]', 'US');
			await expect(page).toFill('input[name^="tax_rate[new-1"]', '1.5');
			await expect(page).toFill('input[name^="tax_rate_priority[new-1"]', '2');
			await expect(page).toFill('input[name^="tax_rate_name[new-1"]', 'Federal Tax');
			await expect(page).toClick('input[name^="tax_rate_shipping[new-1"]');

			// Save changes (AJAX here)
			await expect(page).toClick('button.woocommerce-save-button');
			await uiUnblocked();

			// Verify 2 tax rates
			expect(await page.$$('#rates tr')).toHaveLength(2);

			// Delete federal rate
			await expect(page).toClick('#rates tr:nth-child(2) input');
			await expect(page).toClick('.wc_tax_rates a.remove_tax_rates');

			// Save changes (AJAX here)
			await expect(page).toClick('button.woocommerce-save-button');
			await uiUnblocked();

			// Verify 1 rate
			expect(await page.$$('#rates tr')).toHaveLength(1);
			await expect(page).toMatchElement(
				'#rates tr:first-of-type input[name^="tax_rate_state"][value="CA"]'
			);
		});

		it('can remove tax classes', async () => {
			// Go to tax settings page
			await merchant.openSettings('tax');

			// Make sure the tax tab is active
			await expect(page).toMatchElement('a.nav-tab-active', {text: 'Tax'});

			// Remove "fancy" tax class
			await clearAndFillInput('#woocommerce_tax_classes', ' ');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				expect(page).not.toMatchElement('ul.subsubsub > li > a', {text: 'Fancy rates'}),
			]);
		});
	});
};

module.exports = runTaxSettingsTest;
