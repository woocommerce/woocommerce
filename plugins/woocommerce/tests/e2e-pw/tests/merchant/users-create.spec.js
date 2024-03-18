const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	users: async ( { api }, use ) => {
		const users = [
			{
				email: 'john.doe@example.com',
				first_name: 'John',
				last_name: 'Doe',
				username: 'john.doe',
				role: 'customer',
			},
		];

		await use( users );

		// await api.delete( `customers/${ customer.id }`, { force: true } );
	},
} );

test( 'can edit a customer', async ( { page, user } ) => {
	await page.goto( `wp-admin/user-edit.php?user_id=${ customer.id }` );

	await test.step( 'edit the customer name', async () => {} );

	await test.step( 'verify the changes', async () => {
		// await expect( page.getByLabel( 'Product name' ) ).toHaveValue(
		// 	newProduct.name
		// );
		// await expect( page.locator( '.wp-editor-area' ).first() ).toContainText(
		// 	newProduct.description
		// );
		// await expect( page.getByLabel( 'Regular price ($)' ) ).toHaveValue(
		// 	newProduct.regularPrice
		// );
		// await expect( page.getByLabel( 'Sale price ($)' ) ).toHaveValue(
		// 	newProduct.salePrice
		// );
	} );
} );
