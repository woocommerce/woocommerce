import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { ShopPage, CartPage } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );
const assert = chai.assert;

let manager;
let driver;

test.describe( 'Cart page', function() {
	// open browser
	test.before( function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );
		console.log(config.get( 'url' ));

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.it( 'should displays no item in the cart', () => {
		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal( cartPage.hasNoItem(), true );
	} );

	test.it( 'should adds the product to the cart when "Add to cart" is clicked', () => {
		const shopPage = new ShopPage( driver, { url: manager.getPageUrl( '/shop' ) } );
		assert.eventually.equal( shopPage.addProductToCart( 'Album' ), true );
		assert.eventually.equal( shopPage.addProductToCart( 'Polo' ), true );

		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal( cartPage.hasItem( 'Album' ), true );
		assert.eventually.equal( cartPage.hasItem( 'Polo' ), true );
	} );

	test.it( 'should increases item qty when "Add to cart" of the same product is clicked', () => {
		const shopPage = new ShopPage( driver, { url: manager.getPageUrl( '/shop' ) } );
		assert.eventually.equal( shopPage.addProductToCart( 'Album' ), true );

		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal( cartPage.hasItem( 'Album', { qty: 2 } ), true );
		assert.eventually.equal( cartPage.hasItem( 'Polo', { qty: 1 } ), true );
	} );

	test.it( 'should updates qty when updated via qty input', () => {
		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		cartPage.getItem( 'Album', { qty: 2 } ).setQty( 4 );
		cartPage.update();
		cartPage.getItem( 'Polo', { qty: 1 } ).setQty( 3 );
		cartPage.update();

		assert.eventually.equal( cartPage.hasItem( 'Album', { qty: 4 } ), true );
		assert.eventually.equal( cartPage.hasItem( 'Polo', { qty: 3 } ), true );
	} );

	test.it( 'should remove the item from the cart when remove is clicked', () => {
		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		cartPage.getItem( 'Album', { qty: 4 } ).remove();
		cartPage.getItem( 'Polo', { qty: 3 } ).remove();

		assert.eventually.equal( cartPage.hasNoItem(), true );
	} );

	test.it( 'should update subtotal in cart totals when adding product to the cart', () => {
		const shopPage = new ShopPage( driver, { url: manager.getPageUrl( '/shop' ) } );
		assert.eventually.equal( shopPage.addProductToCart( 'Album' ), true );

		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal(
			cartPage.hasItem( 'Album', { qty: 1 } ),
			true,
			'Cart item "Album" with qty 1 is not displayed'
		);

		assert.eventually.equal(
			cartPage.hasSubtotal( '15.00' ),
			true,
			'Cart totals does not display subtotal of 15.00'
		);

		cartPage.getItem( 'Album', { qty: 1 } ).setQty( 2 );
		cartPage.update();

		assert.eventually.equal(
			cartPage.hasSubtotal( '30.00' ),
			true,
			'Cart totals does not display subtotal of 30.00'
		);
	} );

	test.it( 'should go to the checkout page when "Proceed to Checkout" is clicked', () => {
		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		const checkoutPage = cartPage.checkout();

		assert.eventually.equal(
			checkoutPage.components.orderReview.displayed(),
			true,
			'Order review in checkout page is not displayed'
		);
	} );

	// quit browser
	test.after( () => {
		manager.quitBrowser();
	} );
} );
