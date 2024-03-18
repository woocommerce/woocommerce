const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const now = Date.now();

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	customer: async ( { api, wpApi }, use ) => {
		let user = {
			username: `customer.${ now }`,
			email: `customer.${ now }@example.com`,
		};
		await api.post( 'customers', user ).then( ( response ) => {
			user = response.data;
		} );

		await use( user );

		// Use wp api instead, because the wc api incorrectly returns 400 instead of 404 if the user is already deleted.
		// To avoid test failure in case of 400, an exception needs to be added required in the validateStatus api definition,
		// which is not ideal, because we want 400 errors to be thrown
		await wpApi.delete( `/wp-json/wp/v2/users/${ user.id }`, {
			data: {
				force: true,
				reassign: 1,
			},
		} );
	},
	manager: async ( { wpApi }, use ) => {
		let user = {
			username: `manager.${ now }`,
			email: `manager.${ now }@example.com`,
			password: 'ewdveth345tgrg',
			roles: [ 'shop_manager' ],
		};

		const response = await wpApi.post( '/wp-json/wp/v2/users', {
			data: user,
		} );
		user = await response.json();

		await use( user );

		await wpApi.delete( `/wp-json/wp/v2/users/${ user.id }`, {
			data: {
				force: true,
				reassign: 1,
			},
		} );
	},
} );

async function userDeletionTest( page, username ) {
	await page.goto( `wp-admin/users.php?s=${ username }` );

	await test.step( 'hover the the username and delete', async () => {
		await page.getByRole( 'link', { name: username, exact: true } ).hover();
		await page.getByRole( 'link', { name: 'Delete' } ).click();
	} );

	await test.step( 'confirm deletion', async () => {
		await expect( page.getByText( `${ username }` ) ).toBeVisible();
		await page.getByRole( 'button', { name: 'Confirm Deletion' } ).click();
	} );

	await test.step( 'verify the user was deleted', async () => {
		await expect( page.getByText( 'User deleted' ) ).toBeVisible();
		await page.goto( `wp-admin/users.php?s=${ username }` );
		await expect( page.locator( '#the-list tr' ) ).toHaveCount( 1 );
		await expect( page.getByText( 'No users found' ) ).toBeVisible();
	} );
}

test( `can edit a customer`, async ( { page, customer } ) => {
	await page.goto( `wp-admin/user-edit.php?user_id=${ customer.id }` );

	await test.step( 'update user data', async () => {
		await expect( page ).toHaveTitle( /Edit User/ );
	} );
} );

test( `can edit a shop manager`, async ( { page, manager } ) => {
	await page.goto( `wp-admin/user-edit.php?user_id=${ manager.id }` );

	await test.step( 'update user data', async () => {
		await expect( page ).toHaveTitle( /Edit User/ );
	} );
} );

test( `can delete a customer`, async ( { page, customer } ) => {
	await userDeletionTest( page, customer.username );
} );

test( `can delete a shop manager`, async ( { page, manager } ) => {
	await userDeletionTest( page, manager.username );
} );
