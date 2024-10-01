const { chromium, expect } = require( '@playwright/test' );
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
			await adminPage.goto( `/wp-admin` );
			await adminPage
				.locator( 'input[name="log"]' )
				.fill( admin.username );
			await adminPage
				.locator( 'input[name="pwd"]' )
				.fill( admin.password );
			await adminPage.locator( 'text=Log In' ).click();
			// eslint-disable-next-line playwright/no-networkidle
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

			console.log( 'Clearing pages...' );
			// clear pages created
			await adminPage.goto(
				'/wp-admin/edit.php?s=page-&post_status=all&post_type=page'
			);
			if ( ! adminPage.getByText( 'No pages found.' ) ) {
				await adminPage.locator( '#cb-select-all-1' ).check();
				await adminPage
					.locator( '#bulk-action-selector-top' )
					.selectOption( 'Move to Trash' );
				await adminPage.locator( '#doaction' ).click();
			}

			// clear mini cart pages
			await adminPage.goto(
				'/wp-admin/edit.php?s=Mini+Cart&post_status=all&post_type=page'
			);
			if ( ! adminPage.getByText( 'No pages found.' ) ) {
				await adminPage.locator( '#cb-select-all-1' ).check();
				await adminPage
					.locator( '#bulk-action-selector-top' )
					.selectOption( 'Move to Trash' );
				await adminPage.locator( '#doaction' ).click();
			}

			// clear product showcase pages
			await adminPage.goto(
				'/wp-admin/edit.php?s=Product+Showcase&post_status=all&post_type=page'
			);
			if ( ! adminPage.getByText( 'No pages found.' ) ) {
				await adminPage.locator( '#cb-select-all-1' ).check();
				await adminPage
					.locator( '#bulk-action-selector-top' )
					.selectOption( 'Move to Trash' );
				await adminPage.locator( '#doaction' ).click();
			}

			console.log( 'Clearing posts...' );
			// clear posts
			await adminPage.goto(
				'/wp-admin/edit.php?s=Post-&post_status=all&post_type=post'
			);
			if ( ! adminPage.getByText( 'No posts found.' ) ) {
				await adminPage.locator( '#cb-select-all-1' ).check();
				await adminPage
					.locator( '#bulk-action-selector-top' )
					.selectOption( 'Move to Trash' );
				await adminPage.locator( '#doaction' ).click();
			}

			break;
		} catch ( e ) {
			console.log( 'Failed to clear consumer token. Retrying...' );
		}
	}

	await expect( consumerTokenCleared ).toBe( true );
};
