/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	withRestApi,
	createSimpleProduct,
	uiUnblocked,
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
	let productId;

	describe('Checkout page', () => {
		beforeAll(async () => {
			productId = await createSimpleProduct();
			await withRestApi.resetSettingsGroupToDefault('general');
			await withRestApi.resetSettingsGroupToDefault('products');
			await withRestApi.resetSettingsGroupToDefault('tax');

			// Set free shipping within California
			await withRestApi.addShippingZoneAndMethod('Free Shipping CA', 'state:US:CA', '', 'free_shipping');

			// Set base location with state CA.
			await withRestApi.updateSettingOption( 'general', 'woocommerce_default_country', { value: 'US:CA' } );

			// Sell to all countries
			await withRestApi.updateSettingOption( 'general', 'woocommerce_allowed_countries', { value: 'all' } );

			// Set currency to USD
			await withRestApi.updateSettingOption( 'general', 'woocommerce_currency', { value: 'USD' } );
			// Tax calculation should have been enabled by another test - no-op

			// Enable BACS payment method
			await withRestApi.updatePaymentGateway( 'bacs', { enabled: true } );

			// Enable COD payment method
			await withRestApi.updatePaymentGateway( 'cod', { enabled: true } );
		});

		afterAll(async () => {
			await withRestApi.deleteAllShippingZones();
		});

		it('should display cart items in order review', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `1`, singleProductPrice, singleProductPrice);
		});

		it('allows customer to choose available payment methods', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `2`, twoProductPrice, twoProductPrice);

			await expect(page).toClick('.wc_payment_method label', {text: 'Direct bank transfer'});
			await expect(page).toClick('.wc_payment_method label', {text: 'Cash on delivery'});
		});

		it('allows customer to fill billing details', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
			await shopper.goToCheckout();
			await shopper.productIsInCheckout(simpleProductName, `3`, threeProductPrice, threeProductPrice);
			await shopper.fillBillingDetails(config.get('addresses.customer.billing'));
		});

		it('allows customer to fill shipping details', async () => {
			await shopper.goToShop();
			await shopper.addToCartFromShopPage( productId );
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
			await shopper.addToCartFromShopPage( productId );
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
			await shopper.addToCartFromShopPage( productId );
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

		it('merchant can confirm the order was received', async () => {
			await merchant.login();
			await merchant.verifyOrder(guestOrderId, simpleProductName, singleProductPrice, 5, fiveProductPrice);
			await merchant.verifyOrder(customerOrderId, simpleProductName, singleProductPrice, 1, singleProductPrice, true);
		});
	});
};

module.exports = runCheckoutPageTest;
