import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { Helper, PageMap, CheckoutOrderReceivedPage, StoreOwnerFlow, GuestCustomerFlow } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );

const assert = chai.assert;
const PAGE = PageMap.PAGE;
const storeOwnerFlowArgs = {
	baseUrl: config.get( 'url' ),
	username: config.get( 'users.admin.username' ),
	password: config.get( 'users.admin.password' )
};

const assertOrderItem = ( orderReview, itemName, attrs ) => {
	assert.eventually.ok(
		orderReview.hasItem( itemName, attrs ),
		`Could not find order item "${ itemName }" with qty ${ attrs.qty } and total ${ attrs.total }`
	);
};

let manager;
let driver;

test.describe( 'Checkout Page', function() {
	test.before( 'open browser', function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );

		const storeOwner = new StoreOwnerFlow( driver, storeOwnerFlowArgs );

		// General settings for this test.
		storeOwner.setGeneralSettings( {
			baseLocation: [ 'United States', 'United States (US) — California' ],
			sellingLocation: 'Sell to all countries',
			enableTaxes: true,
			currency: [ 'United States', 'United States dollar ($)' ],
		} );

		// Make sure payment method is set in setting.
		storeOwner.enableBACS();
		storeOwner.enableCOD();
		storeOwner.enablePayPal();

		storeOwner.logout();
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.it( 'should displays cart items in order review', () => {
		const guest = new GuestCustomerFlow( driver, { baseUrl: config.get( 'url' ) } );
		guest.fromShopAddProductsToCart( 'Flying Ninja', 'Happy Ninja' );

		const checkoutPage = guest.openCheckout();
		assert.eventually.ok( Helper.waitTillUIBlockNotPresent( driver ) );

		const orderReview = checkoutPage.components.orderReview;
		assertOrderItem( orderReview, 'Flying Ninja', { qty: '1', total: '$12.00' } );
		assertOrderItem( orderReview, 'Happy Ninja', { qty: '1', total: '$18.00' } );
		assert.eventually.ok( orderReview.hasSubtotal( '$30.00' ), 'Could not find subtotal $30.00' );
	} );

	test.it( 'allows customer to choose available payment methods', () => {
		const guest = new GuestCustomerFlow( driver, { baseUrl: config.get( 'url' ) } );
		guest.fromShopAddProductsToCart( 'Flying Ninja', 'Happy Ninja' );

		const checkoutPage = guest.openCheckout();
		assert.eventually.ok( Helper.waitTillUIBlockNotPresent( driver ) );
		assert.eventually.ok( checkoutPage.selectPaymentMethod( 'PayPal' ) );
		assert.eventually.ok( checkoutPage.selectPaymentMethod( 'Direct bank transfer' ) );
		assert.eventually.ok( checkoutPage.selectPaymentMethod( 'Cash on delivery' ) );
	} );

	test.it( 'allows customer to fill billing details', () => {
		const guest = new GuestCustomerFlow( driver, { baseUrl: config.get( 'url' ) } );
		guest.fromShopAddProductsToCart( 'Flying Ninja', 'Happy Ninja' );

		const checkoutPage = guest.open( PAGE.CHECKOUT );
		assert.eventually.ok( Helper.waitTillUIBlockNotPresent( driver ) );

		const billingDetails = checkoutPage.components.billingDetails;
		assert.eventually.ok( billingDetails.setFirstName( 'John' ) );
		assert.eventually.ok( billingDetails.setLastName( 'Doe' ) );
		assert.eventually.ok( billingDetails.setCompany( 'Automattic' ) );
		assert.eventually.ok( billingDetails.setEmail( 'john.doe@example.com' ) );
		assert.eventually.ok( billingDetails.setPhone( '123456789' ) );
		assert.eventually.ok( billingDetails.selectCountry( 'united states', 'United States (US)' ) );
		assert.eventually.ok( billingDetails.setAddress1( 'addr 1' ) );
		assert.eventually.ok( billingDetails.setAddress2( 'addr 2' ) );
		assert.eventually.ok( billingDetails.setCity( 'San Francisco' ) );
		assert.eventually.ok( billingDetails.selectState( 'cali', 'California' ) );
		assert.eventually.ok( billingDetails.setZip( '94107' ) );
	} );

	test.it( 'allows customer to fill shipping details', () => {
		const guest = new GuestCustomerFlow( driver, { baseUrl: config.get( 'url' ) } );
		guest.fromShopAddProductsToCart( 'Flying Ninja', 'Happy Ninja' );

		const checkoutPage = guest.open( PAGE.CHECKOUT );
		assert.eventually.ok( Helper.waitTillUIBlockNotPresent( driver ) );
		assert.eventually.ok( checkoutPage.checkShipToDifferentAddress() );

		const shippingDetails = checkoutPage.components.shippingDetails;
		assert.eventually.ok( shippingDetails.setFirstName( 'John' ) );
		assert.eventually.ok( shippingDetails.setLastName( 'Doe' ) );
		assert.eventually.ok( shippingDetails.setCompany( 'Automattic' ) );
		assert.eventually.ok( shippingDetails.selectCountry( 'united states', 'United States (US)' ) );
		assert.eventually.ok( shippingDetails.setAddress1( 'addr 1' ) );
		assert.eventually.ok( shippingDetails.setAddress2( 'addr 2' ) );
		assert.eventually.ok( shippingDetails.setCity( 'San Francisco' ) );
		assert.eventually.ok( shippingDetails.selectState( 'cali', 'California' ) );
		assert.eventually.ok( shippingDetails.setZip( '94107' ) );
	} );

	test.it( 'allows guest customer to place order', () => {
		const guest = new GuestCustomerFlow( driver, { baseUrl: config.get( 'url' ) } );
		guest.fromShopAddProductsToCart( 'Flying Ninja', 'Happy Ninja' );

		const checkoutPage = guest.open( PAGE.CHECKOUT );
		const billingDetails = checkoutPage.components.billingDetails;
		Helper.waitTillUIBlockNotPresent( driver );
		billingDetails.setFirstName( 'John' );
		billingDetails.setLastName( 'Doe' );
		billingDetails.setCompany( 'Automattic' );
		billingDetails.setEmail( 'john.doe@example.com' );
		billingDetails.setPhone( '123456789' );
		billingDetails.selectCountry( 'united states', 'United States (US)' );
		billingDetails.setAddress1( 'addr 1' );
		billingDetails.setAddress2( 'addr 2' );
		billingDetails.setCity( 'San Francisco' );
		billingDetails.selectState( 'cali', 'California' );
		billingDetails.setZip( '94107' );
		checkoutPage.selectPaymentMethod( 'Cash on delivery' );
		checkoutPage.placeOrder();
		Helper.waitTillUIBlockNotPresent( driver );

		const orderReceivedPage = new CheckoutOrderReceivedPage( driver, { visit: false } );

		assert.eventually.ok(
			orderReceivedPage.hasText( 'Order received' )
		);
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
