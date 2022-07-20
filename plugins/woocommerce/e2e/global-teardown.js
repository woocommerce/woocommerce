const { chromium } = require( '@playwright/test' );

module.exports = async ( config ) => {
	const { baseURL } = config.projects[ 0 ].use;

	const browser = await chromium.launch();
	const context = await browser.newContext();
	const adminPage = await browser.newPage();

	// Trace teardown
	await context.tracing.start( { screenshots: true, snapshots: true } );

	let consumerTokenCleared = false;

	// Clean up the consumer keys
	const keysRetries = 5;
	for ( let i = 0; i < keysRetries; i++ ) {
		try {
			console.log( 'Trying to clear consumer token... Try:' + i );
			await adminPage.goto( `${ baseURL }/wp-admin` );
			await adminPage.fill( 'input[name="log"]', 'admin' );
			await adminPage.fill( 'input[name="pwd"]', 'password' );
			await adminPage.click( 'text=Log In' );
			await adminPage.goto(
				`${ baseURL }/wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys`
			);
			await adminPage.dispatchEvent( 'a.submitdelete', 'click' );
			console.log( 'Cleared up consumer token successfully.' );
			consumerTokenCleared = true;
			break;
		} catch ( e ) {
			console.log( 'Failed to clear consumer token. Retrying...' );
		}
	}

	if ( ! consumerTokenCleared ) {
		console.error( 'Could not clear consumer token.' );
		await context.tracing.stop( { path: 'teardown.zip' } );
		process.exit( 1 );
	}

	// Stop trace if no errors ocurred.
	await context.tracing.stop( { path: 'teardown.zip' } );
};
