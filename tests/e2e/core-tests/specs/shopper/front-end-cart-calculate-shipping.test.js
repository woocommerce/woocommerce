/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */

/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createSimpleProduct,
	addShippingZoneAndMethod,
	clearAndFillInput,
	uiUnblocked,
	selectOptionInSelect2,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const config = require( 'config' );
const firstProductPrice = config.has( 'products.simple.price' ) ? config.get( 'products.simple.price' ) : '9.99';
const secondProductPrice = '4.99';
const fourProductPrice = firstProductPrice * 4;
var twoProductsPrice = (+firstProductPrice) + (+secondProductPrice);
var firstProductPriceWithFlatRate = (+firstProductPrice) + (+5);
var fourProductPriceWithFlatRate = (+fourProductPrice) + (+5);
var twoProductsPriceWithFlatRate = (+twoProductsPrice) + (+5);
const firstProductName = 'First Product';
const secondProductName = 'Second Product';
const shippingZoneNameDE = 'Germany Free Shipping';
const shippingCountryDE = 'country:DE';
const shippingZoneNameFR = 'France Flat Local';
const shippingCountryFR = 'country:FR';

const runCartCalculateShippingTest = () => {
	describe('Cart Calculate Shipping', () => {
		beforeAll(async () => {
			await createSimpleProduct(firstProductName);
			await createSimpleProduct(secondProductName, secondProductPrice);

			await merchant.login();
			await merchant.openNewShipping();

			// Add a new shipping zone Germany with Free shipping
			await addShippingZoneAndMethod(shippingZoneNameDE, shippingCountryDE, ' ', 'free_shipping');

			// Add a new shipping zone for France with Flat rate & Local pickup
			await addShippingZoneAndMethod(shippingZoneNameFR, shippingCountryFR, ' ', 'flat_rate');
			await page.waitFor(1000); // to avoid flakiness in headless
			await page.click('a.wc-shipping-zone-method-settings', {text: 'Flat rate'});
			await clearAndFillInput('#woocommerce_flat_rate_cost', '5');
			await page.click('.wc-backbone-modal-main button#btn-ok');
			// Add additional method Local pickup for the same location
			await page.waitFor(1000); // to avoid flakiness in headless
			await page.click('button.wc-shipping-zone-add-method', {text:'Add shipping method'});
			await page.waitForSelector('.wc-shipping-zone-method-selector');
			await page.select('select[name="add_method_id"]', 'local_pickup');
			await page.click('button#btn-ok');
			await page.waitForSelector('#zone_locations');
			await merchant.logout();
			await shopper.emptyCart();
		});

		it('allows customer to calculate Free Shipping if in Germany', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(firstProductName);
			await shopper.goToCart();

			// Set shipping country to Germany
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_country-container');
			await selectOptionInSelect2('Germany');
			await expect(page).toClick('button[name="calc_shipping"]');

			// Verify shipping costs
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping ul#shipping_method > li', {text: 'Free shipping'});
			await expect(page).toMatchElement('.order-total .amount', {text: `$${firstProductPrice}`});
		});

		it('allows customer to calculate Flat rate and Local pickup if in France', async () => {
			await page.reload();

			// Set shipping country to France
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_country-container');
			await selectOptionInSelect2('France');
			await expect(page).toClick('button[name="calc_shipping"]');

			// Verify shipping costs
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: `$${firstProductPriceWithFlatRate}`});
		});

		it('should show correct total cart price after updating quantity', async () => {
			await shopper.setCartQuantity(firstProductName, 4);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();
			await expect(page).toMatchElement('.order-total .amount', {text: `$${fourProductPriceWithFlatRate}`});
		});

		it('should show correct total cart price with 2 products and flat rate', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(secondProductName);
			await shopper.goToCart();

			await shopper.setCartQuantity(firstProductName, 1);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: `$${twoProductsPriceWithFlatRate}`});
		});

		it('should show correct total cart price with 2 products without flat rate', async () => {
			await page.reload();

			// Set shipping country to Spain
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_country-container');
			await selectOptionInSelect2('Spain');
			await expect(page).toClick('button[name="calc_shipping"]');

			// Verify shipping costs
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.order-total .amount', {text: `$${twoProductsPrice}`});
		});
	});
};

module.exports = runCartCalculateShippingTest;
