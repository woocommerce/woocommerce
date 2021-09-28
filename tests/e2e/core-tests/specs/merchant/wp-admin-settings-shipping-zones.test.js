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
	withRestApi,
	uiUnblocked,
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
const simpleProductPrice = config.has( 'products.simple.price' ) ? config.get( 'products.simple.price' ) : '9.99';
const california = 'state:US:CA';
const sanFranciscoZIP = '94107';
const shippingZoneNameUS = 'US with Flat rate';
const shippingZoneNameFL = 'CA with Free shipping';
const shippingZoneNameSF = 'SF with Local pickup';

const runAddNewShippingZoneTest = () => {
	describe('WooCommerce Shipping Settings - Add new shipping zone', () => {
		let productId;

		beforeAll(async () => {
			productId = await createSimpleProduct();
			await withRestApi.deleteAllShippingZones();
			await merchant.login();
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
			await expect(page).toClick('a.wc-shipping-zone-method-settings', {text: 'Flat rate'});
			await clearAndFillInput('#woocommerce_flat_rate_cost', '10');
			await expect(page).toClick('.wc-backbone-modal-main button#btn-ok');
			await merchant.logout();
		});

		it('allows customer to pay for a Flat rate shipping method', async() => {
			await shopper.login();

			// Add product to cart as a shopper
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await shopper.goToCart();

			// Set shipping country to United States (US)
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_country-container');
			await selectOptionInSelect2('United States (US)');

			// Set shipping state to New York
			await expect(page).toClick('#select2-calc_shipping_state-container');
			await selectOptionInSelect2('New York');
			await expect(page).toClick('button[name="calc_shipping"]');

			await uiUnblocked();

			// Verify shipping costs
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping .amount', {text: '$10.00'});
			await expect(page).toMatchElement('.order-total .amount', {text: `$1${simpleProductPrice}`});
		});

		it('allows customer to benefit from a Free shipping if in CA', async () => {
			await page.reload();

			// Set shipping state to California
			await expect(page).toClick('a.shipping-calculator-button');
			await expect(page).toClick('#select2-calc_shipping_state-container');
			await selectOptionInSelect2('California');

			// Set shipping postcode to 94000
			await clearAndFillInput('#calc_shipping_postcode', '94000');
			await expect(page).toClick('button[name="calc_shipping"]');

			await uiUnblocked();

			// Verify shipping method and cost
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping ul#shipping_method > li', {text: 'Free shipping'});
			await expect(page).toMatchElement('.order-total .amount', {text: `$${simpleProductPrice}`});
		});

		it('allows customer to benefit from a free Local pickup if in SF', async () => {
			await page.reload();

			// Set shipping postcode to 94107
			await expect(page).toClick('a.shipping-calculator-button');
			await clearAndFillInput('#calc_shipping_postcode', '94107');
			await expect(page).toClick('button[name="calc_shipping"]');

			await uiUnblocked();

			// Verify shipping method and cost
			await page.waitForSelector('.order-total');
			await expect(page).toMatchElement('.shipping ul#shipping_method > li', {text: 'Local pickup'});
			await expect(page).toMatchElement('.order-total .amount', {text: `$${simpleProductPrice}`});

			await shopper.removeFromCart( productId );
		});
	});
};

module.exports = runAddNewShippingZoneTest;
