const { chromium } = require( '@playwright/test' );

module.exports = async ( config ) => {
	const { baseURL } = config.projects[ 0 ].use;
	// Clean up the consumer keys
	const browser = await chromium.launch();
	const adminPage = await browser.newPage();
	await adminPage.goto( `${ baseURL }/wp-admin` );
	await adminPage.fill( 'input[name="log"]', 'admin' );
	await adminPage.fill( 'input[name="pwd"]', 'password' );
	await adminPage.click( 'text=Log In' );
	await adminPage.goto(
		`${ baseURL }/wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys`
	);
	await adminPage.dispatchEvent( 'a.submitdelete', 'click' );
};
