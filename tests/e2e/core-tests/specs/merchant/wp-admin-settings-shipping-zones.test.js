/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	addShippingZoneAndMethod,
	clearAndFillInput,
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
const shippingZoneNameUS = 'US with Flat rate';
const shippingZoneNameCA = 'CA with Free shipping';
const shippingZoneNameSF = 'SF with Local pickup';
// Shipping Zone Locations
const california = 'California, United States (US)';
const sanFranciscoZIP = '94110';

const runAddNewShippingZoneTest = () => {
	describe('WooCommerce Shipping Settings - Add new shipping zone', () => {
		beforeAll(async () => {
			await merchant.login();
		});

		it('add shipping zone for the US with Flat rate', async () => {
			// Add a new shipping zone for the US with Flat rate
			await addShippingZoneAndMethod(shippingZoneNameUS);

			// Set Flat rate cost
			await expect(page).toClick('a.wc-shipping-zone-method-settings', {text: 'Edit'});
			await clearAndFillInput('#woocommerce_flat_rate_cost', '10');
			await expect(page).toClick('button#btn-ok');
		});

		it('add shipping zone for California with Free shipping', async () => {
			// Add a new shipping zone for California with Free shipping
			await addShippingZoneAndMethod(shippingZoneNameCA, california, 'free_shipping');
		});

		it('add shipping zone for San Francisco with free Local pickup', async () => {
			// Add a new shipping zone for the US with Flat Rate
			await addShippingZoneAndMethod(shippingZoneNameSF, california, 'local_pickup');

			// Set San Francisco as a local pickup city
			await expect(page).toClick('a.wc-shipping-zone-postcodes-toggle');
			await expect(page).toFill('#zone_postcodes', sanFranciscoZIP);

			// Save the shipping zone
			await expect(page).toClick('button#submit');
		});
	});
};

module.exports = runAddNewShippingZoneTest;
