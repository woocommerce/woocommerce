/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect, jest/no-standalone-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant,
	createSimpleProduct,
	setCheckbox,
	settingsPageSaveChanges,
	uiUnblocked,
	verifyCheckboxIsSet
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const singleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';

let orderId;

const runCheckoutRegisteredPageTest = () => {
	describe('Checkout page', () => {
		beforeAll(async () => {
			await merchant.login();
			await createSimpleProduct();

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

			// Enable PayPal payment method
			await merchant.openSettings('checkout', 'paypal');
			await setCheckbox('#woocommerce_paypal_enabled');
			await settingsPageSaveChanges();

			// Verify that settings have been saved
			await verifyCheckboxIsSet('#woocommerce_paypal_enabled');

			await merchant.logout();
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
			return orderId = orderReceivedText.split(/(\s+)/)[6].toString();
		});

		it('store owner can confirm the order was received', async () => {
			await merchant.login();
			await merchant.openAllOrdersView();

			// Click on the order which was placed in the previous step
			await Promise.all([
				page.click('#post-' + orderId),
				page.waitForNavigation({waitUntil: 'networkidle0'}),
			]);

			// Verify that the order page is indeed of the order that was placed
			// Verify order number
			await expect(page).toMatchElement('.woocommerce-order-data__heading', {text: 'Order #' + orderId + ' details'});

			// Verify product name
			await expect(page).toMatchElement('.wc-order-item-name', {text: simpleProductName});

			// Verify product cost
			await expect(page).toMatchElement('.woocommerce-Price-amount.amount', {text: singleProductPrice});

			// Verify product quantity
			await expect(page).toMatchElement('.quantity', {text: '1'});

			// Verify total order amount without shipping
			await expect(page).toMatchElement('.line_cost', {text: singleProductPrice});

			// Verify customer profile link is present to verify order was placed by a registered customer, not a guest
			await expect( page ).toMatchElement( 'label[for="customer_user"] a[href*=user-edit]', { text: 'Profile' } );
		});
	});
};

module.exports = runCheckoutRegisteredPageTest;
