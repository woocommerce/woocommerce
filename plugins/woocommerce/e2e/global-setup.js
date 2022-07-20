const { chromium } = require( '@playwright/test' );
const fs = require( 'fs' );

module.exports = async ( config ) => {
	const { stateDir } = config.projects[ 0 ].use;
	const { baseURL } = config.projects[ 0 ].use;
	// used throughout tests for authentication
	process.env.ADMINSTATE = `${ stateDir }adminState.json`;
	process.env.CUSTOMERSTATE = `${ stateDir }customerState.json`;

	// Clear out the previous save states
	fs.unlink( process.env.ADMINSTATE, function ( err ) {
		if ( err ) {
			// File doesn't exist yet, so will just create it.
		} else {
			// File exists. Delete it so it can be re-created.
		}
	} );
	fs.unlink( process.env.CUSTOMERSTATE, function ( err ) {
		if ( err ) {
			// File doesn't exist yet, so will just create it.
		} else {
			// File exists. Delete it so it can be re-created.
		}
	} );

	const browser = await chromium.launch();
	const adminPage = await browser.newPage();

	// Sign in as admin user and save state
	const adminRetries = 5;
	for ( let i = 0; i < adminRetries; i++ ) {
		try {
			await adminPage.goto( `${ baseURL }/wp-admin` );
			await adminPage.fill( 'input[name="log"]', 'admin' );
			await adminPage.fill( 'input[name="pwd"]', 'password' );
			await adminPage.click( 'text=Log In' );
			await adminPage
				.context()
				.storageState( { path: process.env.ADMINSTATE } );
		} catch ( e ) {}
	}

	// While we're here, let's add a consumer token for API access
	// This step was failing occasionally, and globalsetup doesn't retry, so make it retry
	const nRetries = 5;
	for ( let i = 0; i < nRetries; i++ ) {
		try {
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
		} catch ( e ) {}
	}
	// Sign in as customer user and save state
	const customerRetries = 5;
	for ( let i = 0; i < customerRetries; i++ ) {
		try {
			const customerPage = await browser.newPage();
			await customerPage.goto( `${ baseURL }/wp-admin` );
			await customerPage.fill( 'input[name="log"]', 'customer' );
			await customerPage.fill( 'input[name="pwd"]', 'password' );
			await customerPage.click( 'text=Log In' );
			await customerPage
				.context()
				.storageState( { path: process.env.CUSTOMERSTATE } );
			await browser.close();
		} catch ( e ) {}
	}
};
