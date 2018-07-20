/**
 * External dependencies
 */
import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { CustomerFlow, MyAccountPage, PageMap } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );
const assert = chai.assert;

let manager;
let driver;

test.describe( 'My account page', function() {
	const loginAsCustomer = () => {
		return new CustomerFlow( driver, {
			baseUrl: config.get( 'url' ),
			username: config.get( 'users.customer.username' ),
			password: config.get( 'users.customer.password' )
		} );
	};
	const getMyAccountSubPageUrl = path => {
		return PageMap.getPageUrl( config.get( 'url' ), {
			path: '/my-account/%s'
		}, path );
	};
	const untrailingslashit = url => {
		return url.endsWith( '/' ) ? url.substring( 0, url.length - 1 ) : url;
	};

	// open browser
	test.before( function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.it( 'allows customer to login', () => {
		loginAsCustomer();
		const myAccount = new MyAccountPage( driver, {
			baseUrl: config.get( 'url' ),
			visit: false
		} );

		assert.eventually.ok( myAccount.hasText( 'Hello Customer' ), 'see "Hello Customer" text' );
		assert.eventually.ok( myAccount.hasMenu( 'Dashboard' ), 'see Dashboard menu.' );
		assert.eventually.ok( myAccount.hasMenu( 'Orders' ), 'see Orders menu' );
	} );

	test.it( 'allows customer to see orders', () => {
		loginAsCustomer();
		const myAccount = new MyAccountPage( driver, {
			baseUrl: config.get( 'url' ),
			visit: false
		} );
		myAccount.clickMenu( 'Orders' );

		assert.eventually.equal(
			driver.getCurrentUrl().then( untrailingslashit ),
			untrailingslashit( getMyAccountSubPageUrl( 'orders' ) )
		);
		assert.eventually.ok( myAccount.hasText( 'Orders' ), 'see "Orders" text' );
	} );

	test.it( 'allows customer to see downloads', () => {
		loginAsCustomer();
		const myAccount = new MyAccountPage( driver, {
			baseUrl: config.get( 'url' ),
			visit: false
		} );
		myAccount.clickMenu( 'Downloads' );

		assert.eventually.equal(
			driver.getCurrentUrl().then( untrailingslashit ),
			untrailingslashit( getMyAccountSubPageUrl( 'downloads' ) )
		);
		assert.eventually.ok( myAccount.hasText( 'Downloads' ), 'see "Downloads" text' );
	} );

	test.it( 'allows customer to edit addresses', () => {
		loginAsCustomer();
		const myAccount = new MyAccountPage( driver, {
			baseUrl: config.get( 'url' ),
			visit: false
		} );
		myAccount.clickMenu( 'Addresses' );

		assert.eventually.equal(
			driver.getCurrentUrl().then( untrailingslashit ),
			untrailingslashit( getMyAccountSubPageUrl( 'edit-address' ) )
		);
		assert.eventually.ok( myAccount.hasText( 'Addresses' ), 'see "Addresses" text' );
	} );

	test.it( 'allows customer to edit account details', () => {
		loginAsCustomer();
		const myAccount = new MyAccountPage( driver, {
			baseUrl: config.get( 'url' ),
			visit: false
		} );
		myAccount.clickMenu( 'Account details' );

		assert.eventually.equal(
			driver.getCurrentUrl().then( untrailingslashit ),
			untrailingslashit( getMyAccountSubPageUrl( 'edit-account' ) )
		);
		assert.eventually.ok( myAccount.hasText( 'Account details' ), 'see "Account details" text' );
	} );

	// take screenshot
	test.afterEach( function() {
		if ( this.currentTest.state === 'failed' ) {
			helper.takeScreenshot( manager, this.currentTest );
		}
	} );

	// quit browser
	test.after( () => {
		manager.quitBrowser();
	} );
} );
