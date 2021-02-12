/* eslint-disable jest/no-export, jest/no-disabled-tests */

/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createSimpleProduct,
	addShippingZoneAndMethod,
	clearAndFillInput,
	selectOptionInSelect2,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const california = 'California, United States (US)';
const sanFranciscoZIP = '94107';
const shippingZoneNameUS = 'US with Flat rate';
const shippingZoneNameCA = 'CA with Free shipping';
const shippingZoneNameSF = 'SF with Local pickup';

const runAddNewShippingZoneTest = () => {
	describe('WooCommerce Shipping Settings - Add new shipping zone', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
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
			await merchant.logout();
		});

		it('allows customer to pay for a Flat rate shipping method', async() => {
			await shopper.login();

			// Add product to cart as a shopper
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCart();

			// Set shipping country to United States (US)
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_state-container');
			await selectOptionInSelect2('United States (US)');

			// Set shipping state to New York
			await expect(page).toClick('#select2-calc_shipping_state-container');
			await selectOptionInSelect2('New York');
			await expect(page).toClick('button[name="calc_shipping"]');
		})

		it('allows customer to benefit from a Free shipping if in CA', async () => {
			//test
		})

		it('allows customer to benefit from a Free shipping if in SF', async () => {
			//test
		})
	});
};

module.exports = runAddNewShippingZoneTest;
