const { expect } = require( '../fixtures/fixtures' );
const logIn = async ( page, username, password, assertSuccess = true ) => {
	await page.getByLabel( 'Username or Email Address' ).fill( username );
	await page.getByLabel( 'Password', { exact: true } ).fill( password );
	await page.getByRole( 'button', { name: 'Log In' } ).click();

	if ( assertSuccess ) {
		await expect( page ).toHaveTitle( /Dashboard/ );
	}
};

module.exports = { logIn };
