const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	customer: async ( { api, wpApi }, use ) => {
		const now = Date.now();
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
		const now = Date.now();
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

test.only( `can edit a customer`, async ( { page, customer } ) => {
	await page.goto( `wp-admin/user-edit.php?user_id=${ customer.id }` );
	await expect( page ).toHaveTitle( /Edit User/ );

	const now = Date.now();
	const updatedCustomer = {
		first_name: `First ${ now }`,
		last_name: `Last ${ now }`,
		nick_name: `nickname${ now }`,
		email: `customer.${ now }@example.com`,
		billing: {
			first_name: `First ${ now }`,
			last_name: `Last ${ now }`,
			company: 'Los Pollos Hermanos',
			country: 'United States (US)',
			address_1: '308 Negra Arroyo Lane',
			address_2: 'Suite 6',
			city: 'Albuquerque',
			state: 'New Mexico',
			postcode: '87104',
			phone: '505-842-5662',
			email: `customer.${ now }@example.com`,
		},
	};

	await test.step( 'update user data', async () => {
		await page
			.getByLabel( 'First Name', { exact: true } )
			.fill( updatedCustomer.first_name );
		await page
			.getByLabel( 'Last Name', { exact: true } )
			.fill( updatedCustomer.last_name );
		await page
			.getByLabel( 'Nickname (required)', { exact: true } )
			.fill( updatedCustomer.nick_name );
		await page
			.getByLabel( 'Email (required)', { exact: true } )
			.fill( updatedCustomer.email );
	} );

	await test.step( 'update billing address', async () => {
		await page
			.locator( '#billing_first_name' )
			.fill( updatedCustomer.billing.first_name );
		await page
			.locator( '#billing_last_name' )
			.fill( updatedCustomer.billing.last_name );
		await page
			.locator( '#billing_company' )
			.fill( updatedCustomer.billing.company );
		await page
			.locator( '#billing_address_1' )
			.fill( updatedCustomer.billing.address_1 );
		await page
			.locator( '#billing_address_2' )
			.fill( updatedCustomer.billing.address_2 );
		await page
			.locator( '#billing_city' )
			.fill( updatedCustomer.billing.city );
		await page
			.locator( '#billing_postcode' )
			.fill( updatedCustomer.billing.postcode );
		await page
			.locator( '#billing_country' )
			.selectText( updatedCustomer.billing.country );
		await page
			.locator( '#billing_state' )
			.selectText( updatedCustomer.billing.state );
		await page
			.locator( '#billing_phone' )
			.fill( updatedCustomer.billing.phone );
		await page
			.locator( '#billing_email' )
			.fill( updatedCustomer.billing.email );
	} );

	await test.step( 'copy shipping address from billing address', async () => {
		await page.getByLabel( 'Copy from billing address' ).click();
	} );

	await test.step( 'save the changes', async () => {
		await page.getByRole( 'button', { name: 'Update User' } ).click();
	} );

	await test.step( 'verify the updates', async () => {
		await page.getByText( 'User updated' ).isVisible();
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
