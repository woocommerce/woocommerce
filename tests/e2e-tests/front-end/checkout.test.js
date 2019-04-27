/**
 * @format
 */

/** 
 * Internal dependencies
 */
const {
    CustomerFlow,
    settingsPageSaveChanges,
    StoreOwnerFlow,
    uiUnblocked,
} = require( '../utils' );

describe( 'Checkout page', () => {
    beforeAll( async () => {
        await jestPuppeteer.resetContext();

        // Go to general settings page
        await StoreOwnerFlow.login();
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

        // Enable BACS payment method
        await StoreOwnerFlow.openSettings( 'checkout', 'bacs' );
        await expect( page ).toClick( '#woocommerce_bacs_enabled' );
        await settingsPageSaveChanges();

        // Enable COD payment method
        await StoreOwnerFlow.openSettings( 'checkout', 'cod' );
        await expect( page ).toClick( '#woocommerce_cod_enabled' );
        await settingsPageSaveChanges();

        // Enable PayPal payment method
        await StoreOwnerFlow.openSettings( 'checkout', 'paypal' );
        await expect( page ).toClick( '#woocommerce_paypal_enabled' );
        await settingsPageSaveChanges();

        await StoreOwnerFlow.logout();
    } );

    beforeEach( async () => {
        await jestPuppeteer.resetContext();
    } );

    it( 'should displays cart items in order review', async () => {
        await CustomerFlow.goToShop();
        await CustomerFlow.addToCartFromShopPage( 'Beanie' );
        await CustomerFlow.addToCartFromShopPage( 'Long Sleeve Tee' );

        await CustomerFlow.goToCheckout();
        await CustomerFlow.productIsInCheckout( 'Beanie', 1, 18.00 );
        await CustomerFlow.productIsInCheckout( 'Long Sleeve Tee', 1, 25.00 );
        await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: '$43.00' } );
    } );
    
    it( 'allows customer to choose available payment methods', async () => {
        await CustomerFlow.goToShop();
        await CustomerFlow.addToCartFromShopPage( 'Beanie' );
        await CustomerFlow.goToCheckout();

        // await expect( page ).toClick( '.wc_payment_method label', { text: 'PayPal' } );
        await expect( page ).toClick( '.wc_payment_method label', { text: 'Direct bank transfer' } );
        await expect( page ).toClick( '.wc_payment_method label', { text: 'Cash on delivery' } );
    } );
    
    it( 'allows customer to fill billing details', async () => {
        await CustomerFlow.goToShop();
        await CustomerFlow.addToCartFromShopPage( 'Long Sleeve Tee' );
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
        await CustomerFlow.addToCartFromShopPage( 'Beanie' );
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
        await CustomerFlow.addToCartFromShopPage( 'Beanie' );
        await CustomerFlow.addToCartFromShopPage( 'Long Sleeve Tee' );
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
        await CustomerFlow.placeOrder();

        await expect( page ).toMatch( 'Order received' );
    } );
} );
