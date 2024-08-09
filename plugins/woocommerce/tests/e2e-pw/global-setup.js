const { chromium, expect } = require( '@playwright/test' );
const { admin, customer } = require( './test-data/data' );
const fs = require( 'fs' );
const { site } = require( './utils' );
const { logIn } = require( './utils/login' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { DISABLE_HPOS } = process.env;

/**
 * @param {import('@playwright/test').FullConfig} config
 */
module.exports = async ( config ) => {
	const { stateDir, baseURL, userAgent } = config.projects[ 0 ].use;

	console.log( `State Dir: ${ stateDir }` );
	console.log( `Base URL: ${ baseURL }` );

	// used throughout tests for authentication
	process.env.ADMINSTATE = `${ stateDir }adminState.json`;
	process.env.CUSTOMERSTATE = `${ stateDir }customerState.json`;

	// Clear out the previous save states
	try {
		fs.unlinkSync( process.env.ADMINSTATE );
		console.log( 'Admin state file deleted successfully.' );
	} catch ( err ) {
		if ( err.code === 'ENOENT' ) {
			console.log( 'Admin state file does not exist' );
		} else {
			console.log( 'Admin state file could not be deleted: ' + err );
		}
	}
	try {
		fs.unlinkSync( process.env.CUSTOMERSTATE );
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
	const adminPage = await adminContext.newPage();

	// Sign in as admin user and save state
	const adminRetries = 2;
	for ( let i = 0; i < adminRetries; i++ ) {
		try {
			console.log( 'Trying to log-in as admin...' );
			await adminPage.goto( `/wp-admin` );
			await logIn( adminPage, admin.username, admin.password, false );
			// eslint-disable-next-line playwright/no-networkidle
			await adminPage.waitForLoadState( 'networkidle' );
			await adminPage.goto( `/wp-admin` );
			await expect(
				adminPage.getByRole( 'heading', { name: 'Dashboard' } )
			).toBeVisible();
			await adminPage
				.context()
				.storageState( { path: process.env.ADMINSTATE } );
			console.log( 'Logged-in as admin successfully.' );
			adminLoggedIn = true;
			break;
		} catch ( e ) {
			console.log(
				`Admin log-in failed, Retrying... ${ i }/${ adminRetries }`
			);
			console.log( e );
			await adminPage.screenshot( {
				fullPage: true,
				path: `tests/e2e-pw/test-results/admin-login-fail-${ i }.png`,
			} );
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
	const nRetries = 3;
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
			process.env.CONSUMER_KEY = await adminPage
				.locator( '#key_consumer_key' )
				.inputValue();
			process.env.CONSUMER_SECRET = await adminPage
				.locator( '#key_consumer_secret' )
				.inputValue();
			console.log( 'Added consumer token successfully.' );
			customerKeyConfigured = true;
			break;
		} catch ( e ) {
			console.log(
				`Failed to add consumer token. Retrying... ${ i }/${ nRetries }`
			);
			await adminPage.screenshot( {
				fullPage: true,
				path: `tests/e2e-pw/test-results/generate-key-fail-${ i }.png`,
			} );
			console.log( e );
		}
	}

	if ( ! customerKeyConfigured ) {
		console.error(
			'Cannot proceed e2e test, as we could not set the customer key. Please check if the test site has been setup correctly.'
		);
		process.exit( 1 );
	}

	await adminContext.close();

	// Sign in as customer user and save state
	const customerRetries = 2;
	const customerContext = await browser.newContext( contextOptions );
	const customerPage = await customerContext.newPage();
	for ( let i = 0; i < customerRetries; i++ ) {
		try {
			console.log( 'Trying to log-in as customer...' );
			await customerPage.goto( `/my-account` );
			await logIn(
				customerPage,
				customer.username,
				customer.password,
				false
			);
			await expect( customerPage ).toHaveTitle( /My Account.*/i );
			await expect(
				customerPage.getByRole( 'link', { name: 'Log out' } ).first()
			).toBeVisible();

			await customerPage
				.context()
				.storageState( { path: process.env.CUSTOMERSTATE } );
			console.log( 'Logged-in as customer successfully.' );
			customerLoggedIn = true;
			break;
		} catch ( e ) {
			console.log(
				`Customer log-in failed. Retrying... ${ i }/${ customerRetries }`
			);
			console.log( e );
			await customerPage.screenshot( {
				fullPage: true,
				path: `tests/e2e-pw/test-results/customer-login-fail-${ i }.png`,
			} );
		}
	}

	await customerContext.close();

	if ( ! customerLoggedIn ) {
		console.error(
			'Cannot proceed e2e test, as customer login failed. Please check if the test site has been setup correctly.'
		);
		process.exit( 1 );
	}

	// While we're here, let's set HPOS according to the passed in DISABLE_HPOS env variable
	// (if a value for DISABLE_HPOS was set)
	// This was always being set to 'yes' after login in wp-env so this step ensures the
	// correct value is set before we begin our tests
	console.log( `DISABLE_HPOS: ${ DISABLE_HPOS }` );

	const api = new wcApi( {
		url: baseURL,
		consumerKey: process.env.CONSUMER_KEY,
		consumerSecret: process.env.CONSUMER_SECRET,
		version: 'wc/v3',
	} );

	if ( DISABLE_HPOS ) {
		const hposSettingRetries = 5;

		const value = DISABLE_HPOS === '1' ? 'no' : 'yes';

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
				'Cannot proceed e2e test, HPOS configuration failed. Please check if the correct DISABLE_HPOS value was used and the test site has been setup correctly.'
			);
			process.exit( 1 );
		}
	}

	const response = await api.get(
		'settings/advanced/woocommerce_custom_orders_table_enabled'
	);
	const dataValue = response.data.value;
	const enabledOption = response.data.options[ dataValue ];
	console.log(
		`HPOS configuration (woocommerce_custom_orders_table_enabled): ${ dataValue } - ${ enabledOption }`
	);

	await site.useCartCheckoutShortcodes( baseURL, userAgent, admin );

	await browser.close();

	if ( process.env.RESET_SITE === 'true' ) {
		await site.reset(
			process.env.CONSUMER_KEY,
			process.env.CONSUMER_SECRET
		);
	}
};
