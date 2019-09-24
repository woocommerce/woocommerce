/**
 * @format
 */

/**
 * External dependencies
 */
import { activatePlugin } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { createSimpleProduct } from '../../utils/components';
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';
import { setCheckbox, settingsPageSaveChanges, uiUnblocked, verifyCheckboxIsSet } from "../../utils";

describe( 'Checkout page', () => {
	beforeAll( async () => {
		await activatePlugin( 'woocommerce' );
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

	it( 'should displays cart items in order review', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );

		await CustomerFlow.goToCheckout();
		await CustomerFlow.productIsInCheckout( 'Simple product', 1, 9.99 );
		await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: '$9.99' } );
	} );

	it( 'allows customer to choose available payment methods', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();

		await expect( page ).toClick( '.wc_payment_method label', { text: 'PayPal' } );
		await expect( page ).toClick( '.wc_payment_method label', { text: 'Direct bank transfer' } );
		await expect( page ).toClick( '.wc_payment_method label', { text: 'Cash on delivery' } );
	} );

	it( 'allows customer to fill billing details', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();

		await expect( page ).toFill( '#billing_first_name', 'John' );
		await expect( page ).toFill( '#billing_last_name', 'Doe' );
		await expect( page ).toFill( '#billing_company', 'Automattic' );
		await expect( page ).toFill( '#billing_email', 'john.doe@example.com' );
		await expect( page ).toFill( '#billing_phone', '123456789' );
		await expect( page ).toSelect( '#billing_country', 'United States (US)' );
		await expect( page ).toFill( '#billing_address_1', 'addr 1' );
		await expect( page ).toFill( '#billing_address_2', 'addr 2' );
		await expect( page ).toFill( '#billing_city', 'San Francisco' );
		await expect( page ).toSelect( '#billing_state', 'California' );
		await expect( page ).toFill( '#billing_postcode', '94107' );
	} );

	it( 'allows customer to fill shipping details', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();

		await expect( page ).toClick( '#ship-to-different-address-checkbox' );
		await uiUnblocked();

		await expect( page ).toFill( '#shipping_first_name', 'John' );
		await expect( page ).toFill( '#shipping_last_name', 'Doe' );
		await expect( page ).toFill( '#shipping_company', 'Automattic' );
		await expect( page ).toSelect( '#shipping_country', 'United States (US)' );
		await expect( page ).toFill( '#shipping_address_1', 'addr 1' );
		await expect( page ).toFill( '#shipping_address_2', 'addr 2' );
		await expect( page ).toFill( '#shipping_city', 'San Francisco' );
		await expect( page ).toSelect( '#shipping_state', 'California' );
		await expect( page ).toFill( '#shipping_postcode', '94107' );
	} );

	it( 'allows guest customer to place order', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );
		await CustomerFlow.goToCheckout();

		await expect( page ).toFill( '#billing_first_name', 'John' );
		await expect( page ).toFill( '#billing_last_name', 'Doe' );
		await expect( page ).toFill( '#billing_company', 'Automattic' );
		await expect( page ).toFill( '#billing_email', 'john.doe@example.com' );
		await expect( page ).toFill( '#billing_phone', '123456789' );
		await expect( page ).toSelect( '#billing_country', 'United States (US)' );
		await expect( page ).toFill( '#billing_address_1', 'addr 1' );
		await expect( page ).toFill( '#billing_address_2', 'addr 2' );
		await expect( page ).toFill( '#billing_city', 'San Francisco' );
		await expect( page ).toSelect( '#billing_state', 'California' );
		await expect( page ).toFill( '#billing_postcode', '94107' );
		await uiUnblocked();

		await expect( page ).toClick( '.wc_payment_method label', { text: 'Cash on delivery' } );
		await expect( page ).toMatchElement( '.payment_method_cod', { text: 'Pay with cash upon delivery.' } );
		await uiUnblocked();
		await CustomerFlow.placeOrder();

		await expect( page ).toMatch( 'Order received' );
	} );
} );
