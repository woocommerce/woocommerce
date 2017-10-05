import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { WPLogin } from 'wp-e2e-page-objects';
import { WPAdminWCSettingsTax, WPAdminWCSettingsTaxRates } from 'wc-e2e-page-objects';

chai.use( chaiAsPromised );
const assert = chai.assert;

let manager;
let driver;

test.describe( 'WooCommerce Tax Settings', function() {
	test.before( 'open browser', function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();
		driver.manage().window().maximize();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	test.before( 'login', () => {
		const wpLogin = new WPLogin( driver, { url: manager.getPageUrl( '/wp-login.php' ) } );
		wpLogin.login( config.get( 'users.admin.username' ), config.get( 'users.admin.password' ) );
	} );

	test.it( 'can set tax options', () => {
		const settingsArgs = { url: manager.getPageUrl( '/wp-admin/admin.php?page=wc-settings&tab=tax' ) };
		const settings = new WPAdminWCSettingsTax( driver, settingsArgs );

		assert.eventually.ok( settings.hasActiveTab( 'Tax' ) );

		settings.selectPricesEnteredWithNoTax();
		settings.selectCalculateTaxBasedOn( 'Customer shipping address' );
		settings.selectShippingTaxClass( 'Standard' );
		settings.uncheckRounding();
		settings.selectDisplayPricesInTheShop( 'Excluding tax' );
		settings.selectDisplayPricesDuringCartAndCheckout( 'Including tax' );
		settings.selectDisplayTaxTotals( 'As a single total' );

		settings.saveChanges();
		assert.eventually.ok( settings.hasNotice( 'Your settings have been saved.' ) );
	} );

	test.it( 'can add tax classes', () => {
		const settingsArgs = { url: manager.getPageUrl( '/wp-admin/admin.php?page=wc-settings&tab=tax' ) };
		const settings = new WPAdminWCSettingsTax( driver, settingsArgs );

		settings.removeAdditionalTaxClasses();
		settings.saveChanges();

		settings.addAdditionalTaxClass( 'Fancy' );
		settings.saveChanges();

		assert.eventually.ok( settings.hasSubTab( 'Fancy rates' ) );
	} );

	test.it( 'can set rate settings', () => {
		const settingsArgs = { url: manager.getPageUrl( '/wp-admin/admin.php?page=wc-settings&tab=tax&section=fancy' ) };
		const settings = new WPAdminWCSettingsTaxRates( driver, settingsArgs );

		settings.insertRow();
		settings.setCountryCode( 1, 'US' );
		settings.setStateCode( 1, 'CA' );
		settings.setRate( 1, '7.5' );
		settings.setTaxName( 1, 'CA State Tax' );

		settings.insertRow();
		settings.setCountryCode( 2, 'US' );
		settings.setRate( 2, '1.5' );
		settings.setPriority( 2, '2' );
		settings.setTaxName( 2, 'Federal Tax' );
		settings.uncheckShipping( 2 );
		settings.saveChanges();

		settings.removeRow( 2 );
		settings.saveChanges();
		assert.eventually.ifError( helper.isEventuallyPresentAndDisplayed( driver, settings.getSelector( 2 ), 1000 ) );
	} );

	test.it( 'can remove tax classes', () => {
		const settingsArgs = { url: manager.getPageUrl( '/wp-admin/admin.php?page=wc-settings&tab=tax' ) };
		const settings = new WPAdminWCSettingsTax( driver, settingsArgs );

		settings.removeAdditionalTaxClass( 'Fancy' );
		settings.saveChanges();

		assert.eventually.ifError( settings.hasSubTab( 'Fancy rates' ) );
	} );

	test.after( 'quit browser', () => {
		manager.quitBrowser();
	} );
} );
