import config from 'config';
import chai from 'chai';
import chaiAsPromised from 'chai-as-promised';
import test from 'selenium-webdriver/testing';
import { WebDriverManager, WebDriverHelper as helper } from 'wp-e2e-webdriver';
import { WPLogin, WPAdmin } from 'wp-e2e-page-objects';

chai.use( chaiAsPromised );
const assert = chai.assert;

let manager;
let driver;

test.describe( 'Check for functional WordPress installation', function() {
	// open browser
	test.before( function() {
		this.timeout( config.get( 'startBrowserTimeoutMs' ) );

		manager = new WebDriverManager( 'chrome', { baseUrl: config.get( 'url' ) } );
		driver = manager.getDriver();

		helper.clearCookiesAndDeleteLocalStorage( driver );
	} );

	this.timeout( config.get( 'mochaTimeoutMs' ) );

	// login
	test.before( () => {
		const wpLogin = new WPLogin( driver, { url: manager.getPageUrl( '/wp-login.php' ) } );
		wpLogin.login( config.get( 'users.admin.username' ), config.get( 'users.admin.password' ) );
	} );

	// Update the database to the current version of WP
	test.it( 'update WordPress database', () => {
		const updateArgs = { url: manager.getPageUrl( '/wp-admin/upgrade.php?step=upgrade_db' ), visit: true };
		const update = new WPAdmin( driver, updateArgs );

	} );

	// Check theme status after conditionally attempting to revert to the default theme
	test.it( 'have working theme', () => {
		const themesArgs = { url: manager.getPageUrl( '/wp-admin/themes.php' ), visit: true };
		const themes = new WPAdmin( driver, themesArgs );

		driver.navigate().refresh();
		assert.eventually.notEqual( themes.hasNotice( 'The active theme is broken.' ) );
		assert.eventually.notEqual( themes.hasNotice( 'No themes found.' ) );
		assert.eventually.notEqual( themes.hasNotice( 'ERROR:' ) );
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
