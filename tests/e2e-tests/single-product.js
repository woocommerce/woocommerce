/**
 * External dependencies
 */
import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { SingleProductPage, CartPage } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );

const assert = chai.assert;

let manager;
let driver;

test.describe( 'Single Product Page', function() {
	test.before( 'open browser', function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.it( 'should be able to add simple products to the cart', () => {
		const productPage = new SingleProductPage( driver, { url: manager.getPageUrl( '/product/happy-ninja' ) } );
		productPage.setQuantity( 5 );
		productPage.addToCart();

		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal( cartPage.hasItem( 'Happy Ninja', { qty: 5 } ), true );
	} );

	test.it( 'should be able to add variation products to the cart', () => {
		const variableProductPage = new SingleProductPage( driver, { url: manager.getPageUrl( '/product/ship-your-idea-3/' ) } );
		variableProductPage.selectVariation( 'Color', 'Black' );
		variableProductPage.addToCart();

		// Pause for a half-second. Driver goes to fast and makes wrong selections otherwise.
		driver.sleep( 500 );

		variableProductPage.selectVariation( 'Color', 'Green' );
		variableProductPage.addToCart();

		const cartPage = new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
		assert.eventually.equal( cartPage.hasItem( 'Ship Your Idea - Black' ), true );
		assert.eventually.equal( cartPage.hasItem( 'Ship Your Idea - Green' ), true );
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
