const { chromium } = require( '@playwright/test' );
const { ADMIN_USER, ADMIN_PASSWORD } = process.env;
const adminUsername = ADMIN_USER ?? 'admin';
const adminPassword = ADMIN_PASSWORD ?? 'password';

module.exports = async ( config ) => {
	const { baseURL } = config.projects[ 0 ].use;

	const browser = await chromium.launch();
	const context = await browser.newContext( {
		baseURL,
		userAgent:
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/102.0.5005.40 Safari/537.36',
	} );
	const adminPage = await context.newPage();

	let consumerTokenCleared = false;

	// Clean up the consumer keys
	const keysRetries = 5;
	for ( let i = 0; i < keysRetries; i++ ) {
		try {
			console.log( 'Trying to clear consumer token... Try:' + i );
			await adminPage.goto( `/wp-admin` );
			await adminPage.fill( 'input[name="log"]', adminUsername );
			await adminPage.fill( 'input[name="pwd"]', adminPassword );
			await adminPage.click( 'text=Log In' );
			await adminPage.goto(
				`/wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys`
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
		process.exit( 1 );
	}
};
