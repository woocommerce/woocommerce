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
	test.before( 'open browser', function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

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
		assert.eventually.equal( shopPage.addProductToCart( 'Flying Ninja' ), true );
		assert.eventually.equal( shopPage.addProductToCart( 'Happy Ninja' ), true );

		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal( cartPage.hasItem( 'Flying Ninja' ), true );
		assert.eventually.equal( cartPage.hasItem( 'Happy Ninja' ), true );
	} );

	test.it( 'should increases item qty when "Add to cart" of the same product is clicked', () => {
		const shopPage = new ShopPage( driver, { url: manager.getPageUrl( '/shop' ) } );
		assert.eventually.equal( shopPage.addProductToCart( 'Flying Ninja' ), true );

		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal( cartPage.hasItem( 'Flying Ninja', { qty: 2 } ), true );
		assert.eventually.equal( cartPage.hasItem( 'Happy Ninja', { qty: 1 } ), true );
	} );

	test.it( 'should updates qty when updated via qty input', () => {
		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		cartPage.getItem( 'Flying Ninja', { qty: 2 } ).setQty( 4 );
		cartPage.update();
		cartPage.getItem( 'Happy Ninja', { qty: 1 } ).setQty( 3 );
		cartPage.update();

		assert.eventually.equal( cartPage.hasItem( 'Flying Ninja', { qty: 4 } ), true );
		assert.eventually.equal( cartPage.hasItem( 'Happy Ninja', { qty: 3 } ), true );
	} );

	test.it( 'should remove the item from the cart when remove is clicked', () => {
		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		cartPage.getItem( 'Flying Ninja', { qty: 4 } ).remove();
		cartPage.getItem( 'Happy Ninja', { qty: 3 } ).remove();

		assert.eventually.equal( cartPage.hasNoItem(), true );
	} );

	test.it( 'should update subtotal in cart totals when adding product to the cart', () => {
		const shopPage = new ShopPage( driver, { url: manager.getPageUrl( '/shop' ) } );
		assert.eventually.equal( shopPage.addProductToCart( 'Flying Ninja' ), true );

		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal(
			cartPage.hasItem( 'Flying Ninja', { qty: 1 } ),
			true,
			'Cart item "Flying Ninja" with qty 1 is not displayed'
		);

		assert.eventually.equal(
			cartPage.hasSubtotal( '12.00' ),
			true,
			'Cart totals does not display subtotal of 12.00'
		);

		cartPage.getItem( 'Flying Ninja', { qty: 1 } ).setQty( 2 );
		cartPage.update();

		assert.eventually.equal(
			cartPage.hasSubtotal( '24.00' ),
			true,
			'Cart totals does not display subtotal of 24.00'
		);
	} );

	test.it( 'should go to the checkout page when "Proceed to Chcekout" is clicked', () => {
		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		const checkoutPage = cartPage.checkout();

		assert.eventually.equal(
			checkoutPage.components.orderReview.displayed(),
			true,
			'Order review in checkout page is not displayed'
		);
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
