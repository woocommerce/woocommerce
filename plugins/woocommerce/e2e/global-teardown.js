const { chromium } = require( '@playwright/test' );

module.exports = async ( config ) => {
	const { baseURL } = config.projects[ 0 ].use;

	const browser = await chromium.launch();
	const adminPage = await browser.newPage();

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
		} catch ( e ) {
			console.log( 'Failed to clear consumer token. Retrying...' );
		}
	}
};
