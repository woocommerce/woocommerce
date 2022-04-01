/**
 * Internal dependencies
 */
const {
	shopper,
	createSimpleProduct,
	uiUnblocked,
	selectOptionInSelect2,
	withRestApi,
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
		let firstProductId;
		let secondProductId;

		beforeAll(async () => {
			firstProductId = await createSimpleProduct(firstProductName);
			secondProductId = await createSimpleProduct(secondProductName, secondProductPrice);

			await withRestApi.resetSettingsGroupToDefault( 'general' );

			// Add a new shipping zone Germany with Free shipping
			await withRestApi.addShippingZoneAndMethod(shippingZoneNameDE, shippingCountryDE, ' ', 'free_shipping');

			// Add a new shipping zone for France with Flat rate & Local pickup
			await withRestApi.addShippingZoneAndMethod(shippingZoneNameFR, shippingCountryFR, ' ', 'flat_rate', '5', ['local_pickup']);

			await shopper.emptyCart();
		});

		afterAll(async () => {
			await withRestApi.deleteAllShippingZones();
		});

		it('allows customer to calculate Free Shipping if in Germany', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( firstProductId );
			await shopper.goToCart();

			// Set shipping country to Germany
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_country-container');
			await selectOptionInSelect2('Germany');
			await expect(page).toClick('button[name="calc_shipping"]');
			await uiUnblocked();

			// Verify shipping costs
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping ul#shipping_method > li', {text: 'Free shipping'});
			await expect(page).toMatchElement('.order-total .amount', {text: `$${firstProductPrice}`});
		});

		it('allows customer to calculate Flat rate and Local pickup if in France', async () => {
			await page.reload( { waitUntil: ['networkidle0', 'domcontentloaded'] } );

			// Set shipping country to France
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_country-container');
			await selectOptionInSelect2('France');
			await expect(page).toClick('button[name="calc_shipping"]');
			await uiUnblocked();

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
			await shopper.addToCartFromShopPage( secondProductId );
			await shopper.goToCart();

			await shopper.setCartQuantity(firstProductName, 1);
			await expect(page).toClick('button', {text: 'Update cart'});
			await uiUnblocked();
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping .amount', {text: '$5.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: `$${twoProductsPriceWithFlatRate}`});
		});

		it('should show correct total cart price with 2 products without flat rate', async () => {
			await page.reload( { waitUntil: ['networkidle0', 'domcontentloaded'] } );

			// Set shipping country to Spain
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_country-container');
			await selectOptionInSelect2('Spain');
			await expect(page).toClick('button[name="calc_shipping"]');
			await uiUnblocked();

			// Verify shipping costs
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.order-total .amount', {text: `$${twoProductsPrice}`});
		});
	});
};

module.exports = runCartCalculateShippingTest;
