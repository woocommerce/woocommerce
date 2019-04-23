/**
 * @format
 */

describe( 'Check for functional WordPress installation', () => {
	it( 'Can log in to the dashboard', async () => {
		await page.goto( 'http://local.wordpress.test/wp-login.php' );
		await expect( page.title() ).resolves.toMatch( 'Log In ' );
	} );
} );
