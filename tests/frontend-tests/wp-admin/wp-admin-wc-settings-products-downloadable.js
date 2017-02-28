import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { WPLogin } from 'wp-e2e-page-objects';
import { WPAdminWCSettingsProductsDownloadable } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );
const assert = chai.assert;

let manager;
let driver;

test.describe( 'WooCommerce Products > Downloadable Products Settings', function() {
	test.before( 'open browser', function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.before( 'login', () => {
		const wpLogin = new WPLogin( driver, { url: manager.getPageUrl( '/wp-login.php' ) } );
		wpLogin.login( config.get( 'users.admin.username' ), config.get( 'users.admin.password' ) );
	} );

	test.it( 'can update settings', () => {
		const settingsArgs = { url: manager.getPageUrl( '/wp-admin/admin.php?page=wc-settings&tab=products&section=downloadable' ) };
		const settings = new WPAdminWCSettingsProductsDownloadable( driver, settingsArgs );

		assert.eventually.ok( settings.hasActiveTab( 'Products' ) );
		assert.eventually.ok( settings.hasActiveSubTab( 'Downloadable products' ) );

		settings.selectFileDownloadMethod( 'Redirect only' );
		settings.checkDownloadsRequireLogin();
		settings.checkGrantAccessAfterPayment();
		settings.saveChanges();
		assert.eventually.ok( settings.hasNotice( 'Your settings have been saved.' ) );

		settings.selectFileDownloadMethod( 'Force downloads' );
		settings.uncheckDownloadsRequireLogin();
		settings.uncheckGrantAccessAfterPayment();
		settings.saveChanges();
		assert.eventually.ok( settings.hasNotice( 'Your settings have been saved.' ) );
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
