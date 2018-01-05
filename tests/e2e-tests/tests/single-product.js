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
	const visitProductByPath = path => {
		return new SingleProductPage( driver, { url: manager.getPageUrl( path ) } );
	};
	const visitCart = () => {
		return new CartPage( driver, { url: manager.getPageUrl( '/cart' ) } );
	};

	// open browser
	test.before( function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.it( 'should be able to add simple products to the cart', () => {
		const productPage = visitProductByPath( '/product/t-shirt' );
		productPage.setQuantity( 5 );
		productPage.addToCart();

		assert.eventually.equal( visitCart().hasItem( 'T-Shirt', { qty: 5 } ), true );
	} );

	test.it( 'should be able to add variation products to the cart', () => {
		let variableProductPage;

		variableProductPage = visitProductByPath( '/product/hoodie' );
		variableProductPage.selectVariation( 'Color', 'Blue' );
		variableProductPage.selectVariation( 'Logo', 'Yes' );
		driver.sleep( 500 );
		variableProductPage.addToCart();
		assert.eventually.ok( visitCart().hasItem( 'Hoodie - Blue, Yes' ), '"Hoodie - Blue, Yes" in the cart' );

		variableProductPage = visitProductByPath( '/product/hoodie' );
		variableProductPage.selectVariation( 'Color', 'Green' );
		variableProductPage.selectVariation( 'Logo', 'No' );
		driver.sleep( 500 );
		variableProductPage.addToCart();
		assert.eventually.ok( visitCart().hasItem( 'Hoodie - Green, No' ), '"Hoodie - Green, No" in the cart' );
	} );

	// quit browser
	test.after( () => {
		manager.quitBrowser();
	} );
} );
