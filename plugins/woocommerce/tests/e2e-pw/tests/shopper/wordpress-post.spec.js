const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const test = baseTest.extend( {
	storageState: process.env.CUSTOMERSTATE,
} );

test( 'logged-in customer can comment on a post', async ( { page } ) => {
	await page.goto( 'hello-world/' );
	await expect(
		page.getByRole( 'heading', { name: 'Hello world!', exact: true } )
	).toBeVisible();

	await expect( page.getByText( `Logged in as` ) ).toBeVisible();

	const comment = `This is a test comment ${ Date.now() }`;
	await page.getByRole( 'textbox', { name: 'comment' } ).fill( comment );

	await expect(
		page.getByRole( 'textbox', { name: 'comment' } )
	).toHaveValue( comment );

	await page.getByRole( 'button', { name: 'Post Comment' } ).click();
	await expect( page.getByText( comment ) ).toBeVisible();
} );
