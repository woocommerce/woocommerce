const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const { logIn } = require( '../../utils/login' );

const now = Date.now();
const users = [
	{
		username: `customer.${ now }`,
		email: `customer.${ now }@example.com`,
		first_name: `Customer`,
		last_name: `the ${ now }th`,
		role: 'Customer',
	},
	{
		username: `manager.${ now }`,
		email: `manager.${ now }@example.com`,
		first_name: `Manager`,
		last_name: `the ${ now }th`,
		role: 'Shop manager',
	},
];

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	user: async ( { api }, use ) => {
		const user = {};
		await use( user );
		console.log( `Deleting user ${ user.id }` );
		await api.delete( `customers/${ user.id }`, { force: true } );
	},
} );

for ( const userData of users ) {
	test( `can create a new ${ userData.role }`, async ( { page, user } ) => {
		await page.goto( `wp-admin/user-new.php` );

		await test.step( 'create a new user', async () => {
			// Wait for the password to be generated otherwise it will steal the focus from other fields
			await expect( page.locator( '#pass1' ) ).not.toHaveValue( '' );

			user.password = await page.locator( '#pass1' ).inputValue();

			await page.getByLabel( 'Username' ).fill( userData.username );
			await page.getByLabel( 'Email (required)' ).fill( userData.email );
			await page.getByLabel( 'First Name' ).fill( userData.first_name );
			await page.getByLabel( 'Last Name' ).fill( userData.last_name );
			await page.getByText( 'Send the new user an email' ).check();
			await page.getByLabel( 'Role' ).selectOption( userData.role );
			await page.getByRole( 'button', { name: 'Add New User' } ).click();

			await expect( page ).toHaveTitle( /Users/ );

			// We need the newly created user id to delete it during cleanup
			user.id = new URLSearchParams( new URL( page.url() ).search ).get(
				'id'
			);
		} );

		await test.step( 'verify the new user is displayed in users list', async () => {
			await page.goto( `wp-admin/users.php?s=${ userData.username }` );

			// Check customer data is displayed in the list
			await expect(
				page.locator( '[data-colname="Username"]' )
			).toContainText( userData.username );
			await expect(
				page.locator( '[data-colname="Email"]' )
			).toContainText( userData.email );
			await expect(
				page.locator( '[data-colname="Role"]' )
			).toContainText( userData.role, { ignoreCase: true } );
		} );

		await test.step( 'verify you can access the new user edit page', async () => {
			await page
				.getByRole( 'link', { name: userData.username, exact: true } )
				.click();

			await expect( page ).toHaveTitle( /Edit User/ );
		} );

		await test.step( 'verify the new user can login', async () => {
			await page.context().clearCookies();
			await page.goto( '/wp-admin' );
			await logIn( page, userData.username, user.password, false );

			const expectedTitle =
				// eslint-disable-next-line playwright/no-conditional-in-test
				userData.role === 'Shop manager' ? /Dashboard/ : /My Account/i;
			await expect( page ).toHaveTitle( expectedTitle );
		} );
	} );
}
