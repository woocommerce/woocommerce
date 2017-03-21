import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { WPLogin } from 'wp-e2e-page-objects';
import { WPAdminWCSettingsGeneral } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );
const assert = chai.assert;

let manager;
let driver;

test.describe( 'WooCommerce General Settings', function() {
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
		const settingsArgs = { url: manager.getPageUrl( '/wp-admin/admin.php?page=wc-settings&tab=general' ) };
		const settings = new WPAdminWCSettingsGeneral( driver, settingsArgs );

		assert.eventually.ok( settings.hasActiveTab( 'General' ) );

		// Set selling location to all countries first, so we can choose california
		// as base location.
		settings.selectSellingLocation( 'Sell to all countries' );
		settings.saveChanges();
		assert.eventually.ok( settings.hasNotice( 'Your settings have been saved.' ) );

		// Set base location with state CA.
		settings.selectBaseLocation( 'california', 'United States (US) — California' );
		settings.saveChanges();
		assert.eventually.ok( settings.hasNotice( 'Your settings have been saved.' ) );

		// Set selling location to specific countries first, so we can choose
		// U.S as base location (without state). This will makes specific
		// countries option appears.
		settings.selectSellingLocation( 'Sell to specific countries' );
		settings.removeChoiceInSellToSpecificCountries( 'United States (US)' );
		settings.setSellToSpecificCountries( 'united states', 'United States (US)' );
		settings.saveChanges();
		assert.eventually.ok( settings.hasNotice( 'Your settings have been saved.' ) );

		// Set currency options.
		settings.setThousandSeparator( ',' );
		settings.setDecimalSeparator( '.' );
		settings.setNumberOfDecimals( '2' );

		settings.saveChanges();
		assert.eventually.ok( settings.hasNotice( 'Your settings have been saved.' ) );
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
