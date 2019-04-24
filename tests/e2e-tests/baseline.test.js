/**
 * @format
 */

/** 
 * External dependencies
 */
const config = require( 'config' );

const baseUrl = config.get( 'url' );

describe( 'Check for functional WordPress installation', () => {
	it( 'Can log in to the dashboard', async () => {
		await page.goto( baseUrl + 'wp-login.php', {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Log In' );

		await page.type( '#user_login', config.get( 'users.admin.username' ) );
		await page.type( '#user_pass', config.get( 'users.admin.password' ) );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );

		await expect( page.title() ).resolves.toMatch( 'Dashboard' );
	} );

	it( 'Updates WordPress database', async () => {
		await page.goto( baseUrl + 'wp-admin/upgrade.php?step=upgrade_db', {
			waitUntil: 'networkidle0',
		} );

		await expect( page.content() ).resolves.toMatch( '0' );
	} );

	it( 'Has a working theme', async() => {
		await page.goto( baseUrl + 'wp-admin/themes.php', {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Manage Themes' );
		await expect( page ).not.toMatchElement( 'div.notice, #message', { text: 'The active theme is broken.' } );
		await expect( page ).not.toMatchElement( 'div.notice, #message', { text: 'No themes found.' } );
		await expect( page ).not.toMatchElement( 'div.notice, #message', { text: 'ERROR:' } );
	} );
} );
