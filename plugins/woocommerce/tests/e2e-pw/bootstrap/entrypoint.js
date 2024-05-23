const { test, chromium, expect } = require('@playwright/test');
const { admin, customer } = require('../test-data/data');
const fs = require('fs');
const path = require('path');
const { site } = require('../utils');
const qit = require('/qitHelpers');
const { ENABLE_HPOS } = qit.getEnv();

async function setup() {
	// Copy the files from "./test-data/images" to "/var/www/html/wp-content/uploads/woo-entrypoint" using node, and create the dir first.
	const source = path.resolve(__dirname, './../test-data/images');
	const dest = '/var/www/html/wp-content/uploads/woo-entrypoint';
	fs.mkdirSync(dest, { recursive: true });
	fs.readdirSync(source).forEach(file => {
		fs.copyFileSync(path.join(source, file), path.join(dest, file));
	});

	await qit.wp('theme activate twentynineteen');
	await qit.wp("media import '/var/www/html/wp-content/uploads/woo-entrypoint/image-01.png' '/var/www/html/wp-content/uploads/woo-entrypoint/image-02.png' '/var/www/html/wp-content/uploads/woo-entrypoint/image-03.png'");

	console.log('Setup completed.');
}

/**
 * @param {import('@playwright/test').FullConfig} config
 */
test('Entrypoint @entrypoint', async ({page}, testInfo) => {
	await setup();

	const wcApi = require('@woocommerce/woocommerce-rest-api').default;

	const { stateDir, baseURL, userAgent } = testInfo.project.use;

	console.log( `State Dir: ${ stateDir }` );
	console.log( `Base URL: ${ baseURL }` );

	// used throughout tests for authentication
	qit.setEnv('ADMINSTATE', `${ stateDir }/adminState.json`);
	qit.setEnv('CUSTOMERSTATE', `${ stateDir }/customerState.json`);

	console.log( 'Admin state file path: ' + qit.getEnv('ADMINSTATE') );
	console.log( 'Customer state file path: ' + qit.getEnv('CUSTOMERSTATE') );

	// Clear out the previous save states
	try {
		fs.unlinkSync( qit.getEnv('ADMINSTATE') );
		console.log( 'Admin state file deleted successfully.' );
	} catch ( err ) {
		if ( err.code === 'ENOENT' ) {
			console.log( 'Admin state file does not exist.' );
		} else {
			console.log( 'Admin state file could not be deleted: ' + err );
		}
	}
	try {
		fs.unlinkSync( qit.getEnv('CUSTOMERSTATE') );
		console.log( 'Customer state file deleted successfully.' );
	} catch ( err ) {
		if ( err.code === 'ENOENT' ) {
			console.log( 'Customer state file does not exist.' );
		} else {
			console.log( 'Customer state file could not be deleted: ' + err );
		}
	}

	// Pre-requisites
	let adminLoggedIn = false;
	let customerLoggedIn = false;
	let customerKeyConfigured = false;
	let hposConfigured = false;

	// Specify user agent when running against an external test site to avoid getting HTTP 406 NOT ACCEPTABLE errors.
	const contextOptions = { baseURL, userAgent };

	// Create browser, browserContext, and page for customer and admin users
	const browser = await chromium.launch();
	const adminContext = await browser.newContext( contextOptions );
	const customerContext = await browser.newContext( contextOptions );
	const adminPage = await adminContext.newPage();
	const customerPage = await customerContext.newPage();

	// Sign in as admin user and save state
	const adminRetries = 5;
	for ( let i = 0; i < adminRetries; i++ ) {
		try {
			console.log( 'Trying to log-in as admin...' );
			await adminPage.goto( `/wp-admin` );
			await adminPage
				.locator( 'input[name="log"]' )
				.fill( 'admin' );
			await adminPage
				.locator( 'input[name="pwd"]' )
				.fill( 'password' );
			await adminPage.locator( 'text=Log In' ).click();
			// eslint-disable-next-line playwright/no-networkidle
			await adminPage.waitForLoadState( 'networkidle' );
			await adminPage.goto( `/wp-admin` );
			await adminPage.waitForLoadState( 'domcontentloaded' );

			await expect( adminPage.locator( 'div.wrap > h1' ) ).toHaveText(
				'Dashboard'
			);
			await adminPage
				.context()
				.storageState( { path: qit.getEnv('ADMINSTATE') } );
			console.log( 'Logged-in as admin successfully.' );
			adminLoggedIn = true;
			break;
		} catch ( e ) {
			console.log(
				`Admin log-in failed, Retrying... ${ i }/${ adminRetries }`
			);
			console.log( e );
		}
	}

	if ( ! adminLoggedIn ) {
		console.error(
			'Cannot proceed e2e test, as admin login failed. Please check if the test site has been setup correctly.'
		);
		process.exit( 1 );
	}

	// While we're here, let's add a consumer token for API access
	// This step was failing occasionally, and globalsetup doesn't retry, so make it retry
	const nRetries = 5;
	for ( let i = 0; i < nRetries; i++ ) {
		try {
			console.log( 'Trying to add consumer token...' );
			await adminPage.goto(
				`/wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys&create-key=1`
			);
			await adminPage
				.locator( '#key_description' )
				.fill( 'Key for API access' );
			await adminPage
				.locator( '#key_permissions' )
				.selectOption( 'read_write' );
			await adminPage.locator( 'text=Generate API key' ).click();
			qit.setEnv('CONSUMER_KEY', await adminPage.locator( '#key_consumer_key' ).inputValue());
			qit.setEnv('CONSUMER_SECRET', await adminPage.locator( '#key_consumer_secret' ).inputValue());
			console.log( 'Added consumer token successfully.' );
			customerKeyConfigured = true;
			break;
		} catch ( e ) {
			console.log(
				`Failed to add consumer token. Retrying... ${ i }/${ nRetries }`
			);
			console.log( e );
		}
	}

	if ( ! customerKeyConfigured ) {
		console.error(
			'Cannot proceed e2e test, as we could not set the customer key. Please check if the test site has been setup correctly.'
		);
		process.exit( 1 );
	}

	// Sign in as customer user and save state
	const customerRetries = 5;
	for ( let i = 0; i < customerRetries; i++ ) {
		try {
			console.log( 'Trying to log-in as customer...' );
			await customerPage.goto( `/wp-admin` );
			await customerPage
				.locator( 'input[name="log"]' )
				.fill( 'customer' );
			await customerPage
				.locator( 'input[name="pwd"]' )
				.fill( 'password' );
			await customerPage.locator( 'text=Log In' ).click();

			await customerPage.goto( `/my-account` );
			await expect(
				customerPage.locator(
					'.woocommerce-MyAccount-navigation-link--customer-logout'
				)
			).toBeVisible();
			await expect(
				customerPage.locator(
					'div.woocommerce-MyAccount-content > p >> nth=0'
				)
			).toContainText( 'Hello' );

			await customerPage
				.context()
				.storageState( { path: qit.getEnv('CUSTOMERSTATE') } );
			console.log( 'Logged-in as customer successfully.' );
			customerLoggedIn = true;
			break;
		} catch ( e ) {
			console.log(
				`Customer log-in failed. Retrying... ${ i }/${ customerRetries }`
			);
			console.log( e );
		}
	}

	if ( ! customerLoggedIn ) {
		console.error(
			'Cannot proceed e2e test, as customer login failed. Please check if the test site has been setup correctly.'
		);
		process.exit( 1 );
	}

	// While we're here, let's set HPOS according to the passed in ENABLE_HPOS env variable
	// (if a value for ENABLE_HPOS was set)
	// This was always being set to 'yes' after login in wp-env so this step ensures the
	// correct value is set before we begin our tests
	if ( ENABLE_HPOS ) {
		const hposSettingRetries = 5;
		const api = new wcApi( {
			url: baseURL,
			consumerKey: qit.getEnv('CONSUMER_KEY'),
			consumerSecret: qit.getEnv('CONSUMER_SECRET'),
			version: 'wc/v3',
		} );

		const value = ENABLE_HPOS === '0' ? 'no' : 'yes';

		for ( let i = 0; i < hposSettingRetries; i++ ) {
			try {
				console.log(
					`Trying to switch ${
						value === 'yes' ? 'on' : 'off'
					} HPOS...`
				);
				const response = await api.post(
					'settings/advanced/woocommerce_custom_orders_table_enabled',
					{ value }
				);
				if ( response.data.value === value ) {
					console.log(
						`HPOS Switched ${
							value === 'yes' ? 'on' : 'off'
						} successfully`
					);
					hposConfigured = true;
					break;
				}
			} catch ( e ) {
				console.log(
					`HPOS setup failed. Retrying... ${ i }/${ hposSettingRetries }`
				);
				console.log( e );
			}
		}

		if ( ! hposConfigured ) {
			console.error(
				'Cannot proceed e2e test, HPOS configuration failed. Please check if the correct ENABLE_HPOS value was used and the test site has been setup correctly.'
			);
			process.exit( 1 );
		}
	}

	await site.useCartCheckoutShortcodes( baseURL, userAgent, admin );

	await adminContext.close();
	await customerContext.close();
	await browser.close();
});