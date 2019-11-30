/**
 * @format
 */

/**
 * Internal dependencies
 */
import { createSimpleProduct } from '../../utils/components';
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';
import { setCheckbox, settingsPageSaveChanges, uiUnblocked, verifyCheckboxIsSet } from '../../utils';

let orderId;

describe( 'Checkout page', () => {
	beforeAll( async () => {
		await StoreOwnerFlow.login();
		await createSimpleProduct();

		// Go to general settings page
		await StoreOwnerFlow.openSettings( 'general' );

		// Set base location with state CA.
		await expect( page ).toSelect( 'select[name="woocommerce_default_country"]', 'United States (US) — California' );
		// Sell to all countries
		await expect( page ).toSelect( '#woocommerce_allowed_countries', 'Sell to all countries' );
		// Set currency to USD
		await expect( page ).toSelect( '#woocommerce_currency', 'United States (US) dollar ($)' );
		// Tax calculation should have been enabled by another test - no-op
		// Save
		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await Promise.all( [
			expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } ),
			expect( page ).toMatchElement( 'select[name="woocommerce_default_country"]', { text: 'United States (US) — California' } ),
			expect( page ).toMatchElement( '#woocommerce_allowed_countries', { text: 'Sell to all countries' } ),
			expect( page ).toMatchElement( '#woocommerce_currency', { text: 'United States (US) dollar ($)' } ),
		] );

		// Enable BACS payment method
		await StoreOwnerFlow.openSettings( 'checkout', 'bacs' );
		await setCheckbox( '#woocommerce_bacs_enabled' );
		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await verifyCheckboxIsSet( '#woocommerce_bacs_enabled' );

		// Enable COD payment method
		await StoreOwnerFlow.openSettings( 'checkout', 'cod' );
		await setCheckbox( '#woocommerce_cod_enabled' );
		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await verifyCheckboxIsSet( '#woocommerce_cod_enabled' );

		// Enable PayPal payment method
		await StoreOwnerFlow.openSettings( 'checkout', 'paypal' );
		await setCheckbox( '#woocommerce_paypal_enabled' );
		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await verifyCheckboxIsSet( '#woocommerce_paypal_enabled' );

		await StoreOwnerFlow.logout();
	} );

	it( 'Should display cart items in order review', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();
		await CustomerFlow.productIsInCheckout( 'Simple product', 1, 9.99, 9.99 );
	} );

	it( 'Allows customer to choose available payment methods', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();
		await CustomerFlow.productIsInCheckout( 'Simple product', 2, 19.98, 19.98 );

		await expect( page ).toClick( '.wc_payment_method label', { text: 'PayPal' } );
		await expect( page ).toClick( '.wc_payment_method label', { text: 'Direct bank transfer' } );
		await expect( page ).toClick( '.wc_payment_method label', { text: 'Cash on delivery' } );
	} );

	it( 'Allows customer to fill billing details', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();
		await CustomerFlow.productIsInCheckout( 'Simple product', 3, 29.97, 29.97 );
		await CustomerFlow.fillBillingDetails(
			'John',
			'Doe',
			'Automattic',
			'United States (US)',
			'addr 1',
			'addr 2',
			'San Francisco',
			'California',
			'94107',
			'123456789',
			'john.doe@example.com'
		);
	} );

	it( 'Allows customer to fill shipping details', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();
		await CustomerFlow.productIsInCheckout( 'Simple product', 4, 39.96, 39.96 );

		await expect( page ).toClick( '#ship-to-different-address-checkbox' );
		await uiUnblocked();

		await CustomerFlow.fillShippingDetails(
			'John',
			'Doe',
			'Automattic',
			'United States (US)',
			'addr 1',
			'addr 2',
			'San Francisco',
			'California',
			'94107'
		);
	} );

	it( 'Allows guest customer to place order', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();
		await CustomerFlow.productIsInCheckout( 'Simple product', 5, 49.95, 49.95 );
		await CustomerFlow.fillBillingDetails(
			'John',
			'Doe',
			'Automattic',
			'United States (US)',
			'addr 1',
			'addr 2',
			'San Francisco',
			'California',
			'94107',
			'123456789',
			'john.doe@example.com'
		);
		await uiUnblocked();

		await expect( page ).toClick( '.wc_payment_method label', { text: 'Cash on delivery' } );
		await expect( page ).toMatchElement( '.payment_method_cod', { text: 'Pay with cash upon delivery.' } );
		await uiUnblocked();
		await CustomerFlow.placeOrder();

		await expect( page ).toMatch( 'Order received' );

		// Get order ID from the Thank you page URL
		let reg = /.+?\:\/\/.+?(\/.+?)(?:#|\?|$)/;
		orderId = reg.exec( page.url() )[1].split( '/' )[3];
		return orderId;
	} );

	it( 'Store owner can confirm the order was received', async () => {
		await StoreOwnerFlow.login();
		await StoreOwnerFlow.openAllOrdersView();

		// Click on the order which was placed in the previous step
		await Promise.all( [
			page.click( '#post-' + orderId ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );

		// Verify that the order page is indeed of the order that was placed
		await expect( page ).toMatchElement( '.woocommerce-order-data__heading', { text: 'Order #' + orderId + ' details' } );
	} );
} );
