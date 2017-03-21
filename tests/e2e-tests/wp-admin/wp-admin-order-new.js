import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { StoreOwnerFlow } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );
const assert = chai.assert;

let manager;
let driver;

test.describe( 'Add New Order Page', function() {
	test.before( 'open browser', function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.it( 'can create new order', () => {
		const flowArgs = {
			baseUrl: config.get( 'url' ),
			username: config.get( 'users.admin.username' ),
			password: config.get( 'users.admin.password' )
		};
		const storeOwner = new StoreOwnerFlow( driver, flowArgs );

		const orderPage = storeOwner.openNewOrder();
		const orderData = orderPage.components.metaBoxOrderData;
		orderData.selectOrderStatus( 'Processing' );
		orderData.setOrderDate( '2016-12-13' );
		orderData.setOrderDateHour( '18' );
		orderData.setOrderDateMinute( '55' );

		orderPage.components.metaBoxOrderActions.saveOrder();

		assert.eventually.ok( orderData.hasOrderStatus( 'Processing' ) );

		const orderNotes = orderPage.components.metaBoxOrderNotes;
		assert.eventually.ok( orderNotes.hasNote( 'Order status changed from Pending payment to Processing.' ) );
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
