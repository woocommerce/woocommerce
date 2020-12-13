/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	StoreOwnerFlow,
	verifyValueOfInputField
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	afterEach,
} = require( '@jest/globals' );

const runAddNewShippingZoneTest = () => {
	describe('WooCommerce Shipping Settings - Add new shipping zone', () => {
		const zone_name = `test zone name`

		it('Add new shipping zone', async () => {

			const zone_name_input_field = '#zone_name'
			const zone_location_dropdown = '#zone_locations'
			const zone_location = '     Belgrade, Serbia'
			const shipping_method = 'Flat rate'

			// Go to general settings page
			await StoreOwnerFlow.openSettings('shipping');

			// Make sure the shipping tab is active
			await expect(page).toMatchElement('a.nav-tab-active', {text: 'Shipping'});

			// Click on 'Add new shipping zone'
			await expect(page).toClick('a.page-title-action', {text:'Add shipping zone'});

			// Wait for form to be opened
			await page.waitForSelector(zone_location_dropdown);

			// Set zone name
			await expect(page).toFill(zone_name_input_field, zone_name);

			// Set zone regions
			await expect(page).toSelect(zone_location_dropdown, zone_location);


			debugger;
			// Open shipping method pop up
			await expect(page).toClick('button.button.wc-shipping-zone-add-method');

			// Select shipping method
			await expect(page).toSelect('#wc-backbone-modal-dialog select[name="add_method_id"]', shipping_method);

			// Confirm shipping method
			await expect(page).toClick('#btn-ok');

			// Wait for form to be opened
			await page.waitForSelector(zone_location_dropdown);

			// Save settings
			await expect(page).toClick( '#submit' );

			// Verify that settings have been saved
			await Promise.all([
				verifyValueOfInputField(zone_name_input_field, zone_name),
				expect(page).toMatchElement('li.select2-selection__choice', {text: 'Belgrade, Serbia'}),
				expect(page).toMatchElement('a.wc-shipping-zone-method-settings', {text: shipping_method})

			]);

			// Navigate to Shipping zones table
			await expect(page).toClick('h2 a', {text:'Shipping zones'});

			// Wait for table to be opened
			await page.waitForSelector('tbody.wc-shipping-zone-rows');

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('tbody.wc-shipping-zone-rows tr td.wc-shipping-zone-name', {text: zone_name}),
				expect(page).toMatchElement('tbody.wc-shipping-zone-rows tr td.wc-shipping-zone-methods', {text: shipping_method}),
				expect(page).toMatchElement('tbody.wc-shipping-zone-rows tr td.wc-shipping-zone-region', {text: 'Belgrade'}),

			]);
		});

		afterEach(async () => {
			// Deleting created shipping zone

			// Go to general settings page
			await StoreOwnerFlow.openSettings('shipping');

			// Hover over shipping zone
			page.hover('tbody.wc-shipping-zone-rows tr td.wc-shipping-zone-name', {text: zone_name});

			// Wait until delete button is displayed
			await page.waitForSelector('a.wc-shipping-zone-delete',{visible:true});

			// Click on delete
			page.click('a.wc-shipping-zone-delete');

			// Wait for conformation dialog to be closed
			await page.waitForSelector('tbody.wc-shipping-zone-rows tr td.wc-shipping-zone-name');
		});
	});
};

module.exports = runAddNewShippingZoneTest;
