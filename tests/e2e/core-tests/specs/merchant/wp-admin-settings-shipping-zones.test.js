/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	addShippingZoneAndMethod,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

// Shipping Zone Names
const nameUSFlatRate = 'US with Flat rate';
const nameCAFreeShipping = 'CA with Free shipping';
const nameSFLocalPickup = 'SF with Local pickup';
// Shipping Zone Locations
const californiaUS = 'California, United States (US)';
const sanFranciscoCA = 'San Francisco';

const runAddNewShippingZoneTest = () => {
	describe('WooCommerce Shipping Settings - Add new shipping zone', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('add new shipping zone for the US with Flat rate', async () => {
			// Add a new shipping zone for the US with Flat rate
			await addShippingZoneAndMethod(shippingZoneNameUSFlatRate);

			// Verify that settings have been saved
			await Promise.all([
				verifyValueOfInputField('input#zone_name', nameUSFlatRate),
				expect(page).toMatchElement('li.select2-selection__choice', {text: shippingZoneNameUSFlatRate}),
				expect(page).toMatchElement('a.wc-shipping-zone-method-settings', {text: 'Flat rate'})
			]);
		});

		it('add new shipping zone for California with Free shipping', async () => {
			// Add a new shipping zone for California with Free shipping
			await addShippingZoneAndMethod(nameCAFreeShipping, californiaUS, 'Free shipping');

		});

		it('add new shipping zone for San Francisco with Local pickup for free', async () => {
			// Add a new shipping zone for the US with Flat Rate
			await addShippingZoneAndMethod(nameSFLocalPickup, sanFranciscoCA, 'Local pickup');

		});
	});
};

module.exports = runAddNewShippingZoneTest;
