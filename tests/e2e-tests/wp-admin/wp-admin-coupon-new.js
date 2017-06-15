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

test.describe( 'Add New Coupon Page', function() {
	test.before( 'open browser', function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.it( 'can create new coupon', () => {
		const flowArgs = {
			baseUrl: config.get( 'url' ),
			username: config.get( 'users.admin.username' ),
			password: config.get( 'users.admin.password' )
		};
		const storeOwner = new StoreOwnerFlow( driver, flowArgs );

		const couponPage = storeOwner.openNewCoupon();
		couponPage.setTitle( 'code-' + new Date().getTime().toString() );
		couponPage.setDescription( 'test coupon' );

		const couponData = couponPage.components.metaBoxCouponData;
		const generalPanel = couponData.clickTab( 'General' );
		generalPanel.selectDiscountType( 'Cart Discount' );
		generalPanel.setCouponAmount( '100' );

		couponPage.publish();

		assert.eventually.ok( couponPage.hasNotice( 'Coupon updated.' ) );
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
