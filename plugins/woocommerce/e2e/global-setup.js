const { chromium } = require( '@playwright/test' );
const fs = require( 'fs' );

module.exports = async ( config ) => {
	// Clear out the previous save states
	const adminState = 'e2e/storage/adminState.json';
	const customerState = 'e2e/storage/customerState.json';
	fs.unlink( adminState, function ( err ) {
		if ( err ) {
			// File doesn't exist yet, so will just create it.
		} else {
			// File exists. Delete it so it can be re-created.
		}
	} );
	fs.unlink( customerState, function ( err ) {
		if ( err ) {
			// File doesn't exist yet, so will just create it.
		} else {
			// File exists. Delete it so it can be re-created.
		}
	} );

	const { baseURL } = config.projects[ 0 ].use;
	// Sign in as admin user and save state
	const browser = await chromium.launch();
	const adminPage = await browser.newPage();
	await adminPage.goto( `${ baseURL }/wp-admin` );
	await adminPage.fill( 'input[name="log"]', 'admin' );
	await adminPage.fill( 'input[name="pwd"]', 'password' );
	await adminPage.click( 'text=Log In' );
	await adminPage
		.context()
		.storageState( { path: 'e2e/storage/adminState.json' } );
	// While we're here, let's add a consumer token for API access
	await adminPage.goto(
		`${ baseURL }/wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys&create-key=1`
	);
	await adminPage.fill( '#key_description', 'Key for API access' );
	await adminPage.selectOption( '#key_permissions', 'read_write' );
	await adminPage.click( 'text=Generate API key' );
	process.env.CONSUMER_KEY = await adminPage.inputValue(
		'#key_consumer_key'
	);
	process.env.CONSUMER_SECRET = await adminPage.inputValue(
		'#key_consumer_secret'
	);

	// Sign in as customer user and save state
	const customerPage = await browser.newPage();
	await customerPage.goto( `${ baseURL }/wp-admin` );
	await customerPage.fill( 'input[name="log"]', 'customer' );
	await customerPage.fill( 'input[name="pwd"]', 'password' );
	await customerPage.click( 'text=Log In' );
	await customerPage
		.context()
		.storageState( { path: 'e2e/storage/customerState.json' } );
	await browser.close();
};
