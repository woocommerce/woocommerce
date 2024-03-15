const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	customer: async ( { api }, use ) => {
		let customer;

		await api
			.post( 'customers', {
				email: 'john.doe@example.com',
				first_name: 'John',
				last_name: 'Doe',
				username: 'john.doe',
				billing: {
					first_name: 'John',
					last_name: 'Doe',
					company: '',
					address_1: '969 Market',
					address_2: '',
					city: 'San Francisco',
					state: 'CA',
					postcode: '94103',
					country: 'US',
					email: 'john.doe@example.com',
					phone: '(555) 555-5555',
				},
				shipping: {
					first_name: 'John',
					last_name: 'Doe',
					company: '',
					address_1: '969 Market',
					address_2: '',
					city: 'San Francisco',
					state: 'CA',
					postcode: '94103',
					country: 'US',
				},
			} )
			.then( ( response ) => {
				customer = response.data;
			} );

		await use( customer );

		await api.delete( `customers/${ customer.id }`, { force: true } );
	},
} );

test( 'can edit a customer', async ( { page, customer } ) => {
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
