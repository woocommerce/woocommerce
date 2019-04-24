/**
 * @format
 */

/** 
 * External dependencies
 */
const config = require( 'config' );

const baseUrl = config.get( 'url' );

const StoreOwnerFlow = {
    login: async () => {
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
    },
};

module.exports = {
    StoreOwnerFlow,
};
