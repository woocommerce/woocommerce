const { chromium } = require( '@playwright/test' );
const { admin } = require( './test-data/data' );

module.exports = async ( config ) => {
	const { baseURL, userAgent } = config.projects[ 0 ].use;

	// Specify user agent when running against an external test site to avoid getting HTTP 406 NOT ACCEPTABLE errors.
	const contextOptions = { baseURL, userAgent };

	const browser = await chromium.launch();
	const context = await browser.newContext( contextOptions );
	const adminPage = await context.newPage();

	let consumerTokenCleared = false;

	// Clean up the consumer keys
	const keysRetries = 5;
	for ( let i = 0; i < keysRetries; i++ ) {
		try {
			console.log( 'Trying to clear consumer token... Try:' + i );
			await adminPage.goto( `/wp-admin`, { waitUntil: 'networkidle' } );
			await adminPage
				.locator( 'input[name="log"]' )
				.fill( admin.username );
			await adminPage
				.locator( 'input[name="pwd"]' )
				.fill( admin.password );
			await adminPage.locator( 'text=Log In' ).click();
			await adminPage.waitForLoadState( 'networkidle' );
			await adminPage.goto(
				`/wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys`
			);
			await adminPage
				.getByRole( 'link', { name: 'Revoke', includeHidden: true } )
				.first()
				.dispatchEvent( 'click' );
			console.log( 'Cleared up consumer token successfully.' );
			consumerTokenCleared = true;
			break;
		} catch ( e ) {
			console.log( 'Failed to clear consumer token. Retrying...' );
		}
	}

	if ( ! consumerTokenCleared ) {
		console.error( 'Could not clear consumer token.' );
		process.exit( 1 );
	}
};
