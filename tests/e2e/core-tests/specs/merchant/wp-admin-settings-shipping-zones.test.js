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
const shippingZoneNameFL = 'CA with Free shipping';
const shippingZoneNameSF = 'SF with Local pickup';

const runAddNewShippingZoneTest = () => {
	describe('WooCommerce Shipping Settings - Add new shipping zone', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();
			await merchant.openSettings('shipping');

			// Check if you can go via blank shipping zones, otherwise remove first two zones
			// This is a workaround to avoid flakyness and to give this test more confidence
			try {
				await page.click('.wc-shipping-zones-blank-state > a.wc-shipping-zone-add');

			} catch (error) {
				await page.evaluate(() => {
					document.querySelector('.wc-shipping-zone-delete').click();
				  });
				await page.keyboard.press('Enter');
				await page.evaluate(() => {
					document.querySelector('.wc-shipping-zone-delete').click();
				  });
				await page.keyboard.press('Enter');
			}
		});

		it('add shipping zone for San Francisco with free Local pickup', async () => {
			// Add a new shipping zone for San Francisco 94107, CA, US with Local pickup
			await addShippingZoneAndMethod(shippingZoneNameSF, california, sanFranciscoZIP, 'local_pickup');
		});

		it('add shipping zone for California with Free shipping', async () => {
			// Add a new shipping zone for CA, US with Free shipping
			await addShippingZoneAndMethod(shippingZoneNameFL, california, ' ', 'free_shipping');
		});

		it('add shipping zone for the US with Flat rate', async () => {
			// Add a new shipping zone for the US with Flat rate
			await addShippingZoneAndMethod(shippingZoneNameUS);

			// Set Flat rate cost
			await expect(page).toClick('a.wc-shipping-zone-method-settings', {text: 'Edit'});
			await clearAndFillInput('#woocommerce_flat_rate_cost', '10');
			await expect(page).toClick('button#btn-ok');
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
			await expect(page).toClick('#select2-calc_shipping_country-container');
			await selectOptionInSelect2('United States (US)');

			// Set shipping state to New York
			await expect(page).toClick('#select2-calc_shipping_state-container');
			await selectOptionInSelect2('New York');
			await expect(page).toClick('button[name="calc_shipping"]');

			// Set shipping postcode to 10010
			await clearAndFillInput('#calc_shipping_postcode', '10010');
			await expect(page).toClick('button[name="calc_shipping"]');

			// Verify shipping costs
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping .amount', {text: '$10.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$19.99'});

			await shopper.removeFromCart(simpleProductName);
		})

		it('allows customer to benefit from a Free shipping if in CA', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCart();

			// Set shipping state to California
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_state-container');
			await selectOptionInSelect2('California');

			// Set shipping postcode to 94000
			await clearAndFillInput('#calc_shipping_postcode', '94000');
			await expect(page).toClick('button[name="calc_shipping"]');

			// Verify shipping method and cost
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping ul#shipping_method > li', {text: 'Free shipping'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$9.99'});
			await shopper.removeFromCart(simpleProductName);
		})

		it('allows customer to benefit from a free Local pickup if in SF', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCart();

			// Set shipping postcode to 94107
			await expect(page).toClick('a.shipping-calculator-button');
			await clearAndFillInput('#calc_shipping_postcode', '94107');
			await expect(page).toClick('button[name="calc_shipping"]');

			// Verify shipping method and cost
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping ul#shipping_method > li', {text: 'Local pickup'});
			await expect(page).toMatchElement('.order-total .amount', {text: '$9.99'});

			await shopper.removeFromCart(simpleProductName);
		})
	});
};

module.exports = runAddNewShippingZoneTest;
