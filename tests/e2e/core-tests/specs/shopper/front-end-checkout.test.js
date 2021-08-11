/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	withRestApi,
	createSimpleProduct,
	setCheckbox,
	settingsPageSaveChanges,
	uiUnblocked,
	verifyCheckboxIsSet,
	addShippingZoneAndMethod,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const singleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const twoProductPrice = singleProductPrice * 2;
const threeProductPrice = singleProductPrice * 3;
const fourProductPrice = singleProductPrice * 4;
const fiveProductPrice = singleProductPrice * 5;

let guestOrderId;
let customerOrderId;

const runCheckoutPageTest = () => {
	describe('Checkout page', () => {
		beforeAll(async () => {
			await createSimpleProduct();
			await withRestApi.resetSettingsGroupToDefault('general');
			await withRestApi.resetSettingsGroupToDefault('products');
			await withRestApi.resetSettingsGroupToDefault('tax');

			// Set free shipping within California
			await merchant.login();
			await addShippingZoneAndMethod('Free Shipping CA', 'state:US:CA', ' ', 'free_shipping');
			// Go to general settings page
			await merchant.openSettings('general');

			// Set base location with state CA.
			await expect(page).toSelect('select[name="woocommerce_default_country"]', 'United States (US) — California');
			// Sell to all countries
			await expect(page).toSelect('#woocommerce_allowed_countries', 'Sell to all countries');
			// Set currency to USD
			await expect(page).toSelect('#woocommerce_currency', 'United States (US) dollar ($)');
			// Tax calculation should have been enabled by another test - no-op
			// Save
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await Promise.all([
				expect(page).toMatchElement('#message', {text: 'Your settings have been saved.'}),
				expect(page).toMatchElement('select[name="woocommerce_default_country"]', {text: 'United States (US) — California'}),
				expect(page).toMatchElement('#woocommerce_allowed_countries', {text: 'Sell to all countries'}),
				expect(page).toMatchElement('#woocommerce_currency', {text: 'United States (US) dollar ($)'}),
			]);

			// Enable BACS payment method
			await merchant.openSettings('checkout', 'bacs');
			await setCheckbox('#woocommerce_bacs_enabled');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await verifyCheckboxIsSet('#woocommerce_bacs_enabled');

			// Enable COD payment method
			await merchant.openSettings('checkout', 'cod');
			await setCheckbox('#woocommerce_cod_enabled');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await verifyCheckboxIsSet('#woocommerce_cod_enabled');

			await merchant.logout();
		});

		it('should display cart items in order review', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `1`, singleProductPrice, singleProductPrice);
		});

		it('allows customer to choose available payment methods', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `2`, twoProductPrice, twoProductPrice);

			await expect(page).toClick('.wc_payment_method label', {text: 'Direct bank transfer'});
			await expect(page).toClick('.wc_payment_method label', {text: 'Cash on delivery'});
		});

		it('allows customer to fill billing details', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `3`, threeProductPrice, threeProductPrice);
			await shopper.fillBillingDetails(config.get('addresses.customer.billing'));
		});

		it('allows customer to fill shipping details', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `4`, fourProductPrice, fourProductPrice);

			// Select checkbox to ship to a different address
			await page.evaluate(() => {
				document.querySelector('#ship-to-different-address-checkbox').click();
			});
			await uiUnblocked();

			await shopper.fillShippingDetails(config.get('addresses.customer.shipping'));
		});

		it('allows guest customer to place order', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `5`, fiveProductPrice, fiveProductPrice);
			await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

			await uiUnblocked();

			await expect(page).toClick('.wc_payment_method label', {text: 'Cash on delivery'});
			await expect(page).toMatchElement('.payment_method_cod', {text: 'Pay with cash upon delivery.'});
			await uiUnblocked();
			await shopper.placeOrder();

			await expect(page).toMatch('Order received');

			// Get order ID from the order received html element on the page
			let orderReceivedHtmlElement = await page.$('.woocommerce-order-overview__order.order');
			let orderReceivedText = await page.evaluate(element => element.textContent, orderReceivedHtmlElement);
			guestOrderId = orderReceivedText.split(/(\s+)/)[6].toString();
		});

		it('allows existing customer to place order', async () => {
			await shopper.login();
			await shopper.goToShop();
			await shopper.addToCartFromShopPage(simpleProductName);
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `1`, singleProductPrice, singleProductPrice);
			await shopper.fillBillingDetails(config.get('addresses.customer.billing'));

			await uiUnblocked();

			await expect(page).toClick('.wc_payment_method label', {text: 'Cash on delivery'});
			await expect(page).toMatchElement('.payment_method_cod', {text: 'Pay with cash upon delivery.'});
			await uiUnblocked();
			await shopper.placeOrder();

			await expect(page).toMatch('Order received');

			// Get order ID from the order received html element on the page
			let orderReceivedHtmlElement = await page.$('.woocommerce-order-overview__order.order');
			let orderReceivedText = await page.evaluate(element => element.textContent, orderReceivedHtmlElement);
			customerOrderId = orderReceivedText.split(/(\s+)/)[6].toString();
		});

		it('store owner can confirm the order was received', async () => {
			await merchant.login();
			await merchant.verifyOrder(guestOrderId, simpleProductName, singleProductPrice, 5, fiveProductPrice);
			await merchant.verifyOrder(customerOrderId, simpleProductName, singleProductPrice, 1, singleProductPrice, true);
		});
	});
};

module.exports = runCheckoutPageTest;
