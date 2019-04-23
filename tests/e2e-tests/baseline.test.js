/**
 * @format
 */

/** 
 * External dependencies
 */
import config from 'config';

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
} );
