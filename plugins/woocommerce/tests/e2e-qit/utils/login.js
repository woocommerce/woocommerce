const { expect } = require( '../fixtures/fixtures' );
const logIn = async ( page, username, password, assertSuccess = true ) => {
	await page
		.getByLabel( 'Username or Email Address' )
		.click( { delay: 100 } );
	await page.getByLabel( 'Username or Email Address' ).fill( username );
	await page
		.getByRole( 'textbox', { name: 'Password' } )
		.click( { delay: 100 } );
	await page.getByRole( 'textbox', { name: 'Password' } ).fill( password );
	await page.getByRole( 'button', { name: 'Log In' } ).click();

	if ( assertSuccess ) {
		await expect( page ).toHaveTitle( /Dashboard/ );
	}
};

const logInFromMyAccount = async (
	page,
	username,
	password,
	assertSuccess = true
) => {
	await page.locator( '#username' ).fill( username );
	await page.locator( '#password' ).fill( password );
	const loginButton = page.locator( 'button[name="login"]' );
	await loginButton.click();

	await expect( loginButton ).toBeHidden();

	if ( assertSuccess ) {
		await expect(
			page
				.getByLabel( 'Account pages' )
				.getByRole( 'link', { name: 'Log out' } )
		).toBeVisible();
	}
};

module.exports = { logIn, logInFromMyAccount };
