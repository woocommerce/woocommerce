const { chromium, expect } = require( '@playwright/test' );

module.exports = async ( config ) => {
	const { baseURL, userAgent } = config.projects[ 0 ].use;
	const contextOptions = { baseURL, userAgent };

	const browser = await chromium.launch();
	const setupContext = await browser.newContext( contextOptions );
	const setupPage = await setupContext.newPage();

	// If API_BASE_URL is configured and doesn't include localhost, running on daily
	if (
		process.env.API_BASE_URL &&
		! process.env.API_BASE_URL.includes( 'localhost' )
	) {
		let adminLoggedIn = false;

		console.log( '--------------------------------------' );
		console.log( 'Running daily tests, resetting site...' );
		console.log( '--------------------------------------' );

		const adminRetries = 5;
		for ( let i = 0; i < adminRetries; i++ ) {
			try {
				console.log( 'Trying to log-in as admin...' );
				await setupPage.goto( '/wp-admin' );
				await setupPage
					.locator( 'input[name="log"]' )
					.fill( process.env.USER_KEY );
				await setupPage
					.locator( 'input[name="pwd"]' )
					.fill( process.env.USER_SECRET );
				await setupPage.locator( 'text=Log In' ).click();

				await expect( setupPage.locator( 'div.wrap > h1' ) ).toHaveText(
					'Dashboard'
				);
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
				'Cannot proceed api test, as admin login failed. Please check if the test site has been setup correctly.'
			);
			process.exit( 1 );
		}

		await setupPage.goto( 'wp-admin/plugins.php' );
		await expect( setupPage.locator( 'div.wrap > h1' ) ).toHaveText(
			'Plugins'
		);

		console.log( 'Deactivating WooCommerce Plugin...' );
		await setupPage.locator( '#deactivate-woocommerce' ).click();
		await expect( setupPage.locator( 'div#message' ) ).toHaveText(
			'Plugin deactivated.Dismiss this notice.'
		);

		console.log( 'Deleting WooCommerce Plugin...' );
		setupPage.on( 'dialog', ( dialog ) => dialog.accept() );
		await setupPage.locator( '#delete-woocommerce' ).click();
		await expect( setupPage.locator( '#woocommerce-deleted' ) ).toHaveText(
			'WooCommerce was successfully deleted.'
		);

		console.log( 'Reinstalling WooCommerce Plugin...' );
		await setupPage.goto( 'wp-admin/plugin-install.php' );
		await setupPage.locator( '#search-plugins' ).type( 'woocommerce' );
		await setupPage
			.getByRole( 'link', {
				name: /Install WooCommerce \d+\.\d+\.\d+ now/g,
			} )
			.click();
		await setupPage.getByRole( 'link', { name: 'Activate' } ).click();

		console.log( 'WooCommerce Re-installed.' );
		await expect(
			setupPage.getByRole( 'heading', { name: 'Welcome to Woo!' } )
		).toBeVisible();

		// await site.reset( process.env.USER_KEY, process.env.USER_SECRET );
	}
};
