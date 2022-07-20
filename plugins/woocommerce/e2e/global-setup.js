const { chromium } = require( '@playwright/test' );
const fs = require( 'fs' );

module.exports = async ( config ) => {
	const { stateDir } = config.projects[ 0 ].use;
	const { baseURL } = config.projects[ 0 ].use;
	// used throughout tests for authentication
	process.env.ADMINSTATE = `${ stateDir }adminState.json`;
	process.env.CUSTOMERSTATE = `${ stateDir }customerState.json`;

	// Clear out the previous save states
	try {
		fs.unlinkSync( process.env.ADMINSTATE );
		console.log( 'Admin state file deleted successfully.' );
	} catch ( err ) {
		console.log(
			'Admin state file does not exist or could not be deleted: ' + err
		);
	}
	try {
		fs.unlinkSync( process.env.CUSTOMERSTATE );
		console.log( 'Customer state file deleted successfully.' );
	} catch ( err ) {
		console.log(
			'Customer state file does not exist or could not be deleted: ' + err
		);
	}

	// Pre-requisites
	let adminLoggedIn = false;
	const customerLoggedIn = false;
	let customerKeyConfigured = false;

	const browser = await chromium.launch();
	const adminPage = await browser.newPage();

	// Sign in as admin user and save state
	const adminRetries = 5;
	for ( let i = 0; i < adminRetries; i++ ) {
		try {
			console.log( 'Trying to log-in as admin... Try:' + i );
			await adminPage.goto( `${ baseURL }/wp-admin` );
			await adminPage.fill( 'input[name="log"]', 'admin' );
			await adminPage.fill( 'input[name="pwd"]', 'password' );
			await adminPage.click( 'text=Log In' );
			await adminPage
				.context()
				.storageState( { path: process.env.ADMINSTATE } );
			console.log( 'Logged-in as admin successfully.' );
			adminLoggedIn = true;
		} catch ( e ) {
			console.log( 'Admin log-in failed. Retrying...' );
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
			console.log( 'Trying to add consumer token... Try:' + i );
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
			console.log( 'Added consumer token successfully.' );
			customerKeyConfigured = true;
		} catch ( e ) {
			console.log( 'Failed to add consumer token. Retrying...' );
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
			console.log( 'Trying to log-in as customer... Try:' + i );
			const customerPage = await browser.newPage();
			await customerPage.goto( `${ baseURL }/wp-admin` );
			await customerPage.fill( 'input[name="log"]', 'customer' );
			await customerPage.fill( 'input[name="pwd"]', 'password' );
			await customerPage.click( 'text=Log In' );
			await customerPage
				.context()
				.storageState( { path: process.env.CUSTOMERSTATE } );
			await browser.close();
			console.log( 'Logged-in as customer successfully.' );
		} catch ( e ) {
			console.log( 'Customer log-in failed. Retrying...' );
		}
	}

	if ( ! customerLoggedIn ) {
		console.error(
			'Cannot proceed e2e test, as customer login failed. Please check if the test site has been setup correctly.'
		);
		process.exit( 1 );
	}
};
