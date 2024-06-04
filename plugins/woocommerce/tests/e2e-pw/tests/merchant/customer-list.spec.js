const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const customerData = {
	walterWhite: {
		first_name: 'Walter',
		last_name: 'White',
		username: 'heisenberg',
		email: 'heisenberg@example.com',
		billing: {
			first_name: 'Walter',
			last_name: 'White',
			company: 'Los Pollos Hermanos',
			country: 'US',
			address_1: '308 Negra Arroyo Lane',
			address_2: 'Suite 6',
			city: 'Albuquerque',
			state: 'NM',
			postcode: '87104',
			phone: '505-842-5662',
			email: 'heisenberg@example.com',
		},
	},
	jessePinkman: {
		first_name: 'Jesse',
		last_name: 'Pinkman',
		username: 'jesse',
		email: 'jesse@example.com',
		billing: {
			first_name: 'Jesse',
			last_name: 'Pinkman',
			company: 'Los Pollos Hermanos',
			country: 'US',
			address_1: '9809 Margo St',
			city: 'Albuquerque',
			state: 'NM',
			postcode: '87104',
			phone: '505-842-5663',
			email: 'jesse@example.com',
		},
	},
	saulGoodman: {
		first_name: 'Saul',
		last_name: 'Goodman',
		username: 'saul',
		email: 'saul@example.com',
		billing: {
			first_name: 'Saul',
			last_name: 'Goodman',
			company: 'Goodman & McGill',
			country: 'US',
			address_1: '160 Juan Tabo Blvd NE',
			city: 'Albuquerque',
			state: 'NM',
			postcode: '87123',
			phone: '505-842-5664',
			email: 'saul@example.com',
		},
	},
};

baseTest.describe( 'Merchant > Customer List', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		customers: async ( { api }, use ) => {
			const customers = [];

			for ( const customer of Object.values( customerData ) ) {
				await api.post( 'customers', customer ).then( ( response ) => {
					customers.push( response.data );
				} );
			}

			await use( customers );

			await api.post( `customers/batch`, {
				delete: customers.map( ( customer ) => customer.id ),
			} );
		},
	} );

	test.beforeEach( async ( { context } ) => {
		// prevents the column picker from saving state between tests
		await context.route( '**/users/**', ( route ) => route.abort() );
	} );

	test( 'Merchant can view a list of all customers, filter and download', async ( {
		page,
		customers,
	} ) => {
		await page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomers'
		);

		// may have more than 3 customers due to guest orders
		// await test.step( 'Check that 3 customers are displayed', async () => {
		// 	await expect(
		// 		page.getByText( '3customers0Average orders$0.' )
		// 	).toBeVisible();
		// } );

		await test.step( 'Check that the customers are displayed in the list', async () => {
			for ( const customer of customers ) {
				await expect(
					page.getByRole( 'link', { name: customer.email } )
				).toBeVisible();
			}
		} );

		await test.step( 'Check that the customer list can be filtered by first name', async () => {
			let x = 1;
			for ( const customer of customers ) {
				await page
					.locator( '#woocommerce-select-control-0__control-input' )
					.fill( customer.first_name );
				await page
					.getByRole( 'option', {
						name: 'All customers with names that',
					} )
					.click();
				await expect(
					page.getByRole( 'link', { name: customer.email } )
				).toBeVisible();
				await expect(
					page.getByText( `${ x }customer` )
				).toBeVisible();
				x++;
			}
			await page.getByRole( 'button', { name: 'Clear all' } ).click();
		} );

		await test.step( 'Hide and display columns', async () => {
			await page
				.getByRole( 'button', {
					name: 'Choose which values to display',
				} )
				.click();
			// hide a few columns
			await page.getByRole( 'menu' ).getByText( 'Username' ).click();
			await page.getByRole( 'menu' ).getByText( 'Last active' ).click();
			await page.getByRole( 'menu' ).getByText( 'Total spend' ).click();

			// click to close the menu
			await page.getByText( 'Show:' ).click();

			await expect(
				page.getByRole( 'columnheader', { name: 'Username' } )
			).toBeHidden();
			await expect(
				page.getByRole( 'columnheader', { name: 'Last active' } )
			).toBeHidden();
			await expect(
				page.getByRole( 'columnheader', { name: 'Total spend' } )
			).toBeHidden();

			// show the columns again
			await page
				.getByRole( 'button', {
					name: 'Choose which values to display',
				} )
				.click();
			await page.getByRole( 'menu' ).getByText( 'Username' ).click();
			await page.getByRole( 'menu' ).getByText( 'Last active' ).click();
			await page.getByRole( 'menu' ).getByText( 'Total spend' ).click();

			// click to close the menu
			await page.getByText( 'Show:' ).click();

			await expect(
				page.getByRole( 'columnheader', { name: 'Username' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Last active' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'columnheader', { name: 'Total spend' } )
			).toBeVisible();
		} );

		await test.step( 'Download the customer list', async () => {
			const downloadPromise = page.waitForEvent( 'download' );
			await page.getByRole( 'button', { name: 'Download' } ).click();
			const download = await downloadPromise;

			const today = new Date();
			const year = today.getFullYear();
			const month = String( today.getMonth() + 1 ).padStart( 2, '0' );
			const day = String( today.getDate() ).padStart( 2, '0' );

			const filename = `customers_${ year }-${ month }-${ day }_orderby-date-last-active_order-desc_page-wc-admin_path--customers.csv`;

			await expect( download.suggestedFilename() ).toBe( filename );
		} );
	} );

	test( 'Merchant can view a single customer', async ( {
		page,
		customers,
	} ) => {
		await page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomers'
		);

		await test.step( 'Switch to single customer view', async () => {
			await page.getByRole( 'button', { name: 'All Customers' } ).click();
			await page
				.locator( 'li' )
				.filter( { hasText: 'Single Customer' } )
				.getByRole( 'button' )
				.click();
			await page
				.getByPlaceholder( 'Type to search for a customer' )
				.pressSequentially( customers[ 0 ].first_name );

			await page
				.getByRole( 'option', {
					name: `${ customers[ 0 ].first_name } ${ customers[ 0 ].last_name }`,
				} )
				.click();
		} );

		await test.step( 'Check that the customer is displayed', async () => {
			await expect(
				page.getByRole( 'cell', { name: customers[ 0 ].email } )
			).toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: customers[ 1 ].email } )
			).toBeHidden();
			await expect(
				page.getByRole( 'button', {
					name: `${ customers[ 0 ].first_name } ${ customers[ 0 ].last_name } Single Customer`,
				} )
			).toBeVisible();
		} );
	} );

	test( 'Merchant can use advanced filters', async ( {
		page,
		customers,
	} ) => {
		await page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomers'
		);

		await test.step( 'Switch to advanced filters', async () => {
			await page.getByRole( 'button', { name: 'All Customers' } ).click();
			await page
				.locator( 'li' )
				.filter( { hasText: 'Advanced Filters' } )
				.getByRole( 'button' )
				.click();
		} );

		await test.step( 'Add a filter for email', async () => {
			await page.getByRole( 'button', { name: 'Add a filter' } ).click();
			await page
				.locator( 'li' )
				.filter( { hasText: 'Email' } )
				.getByRole( 'button' )
				.click();
			await page
				.locator( '#woocommerce-select-control-1__control-input' )
				.fill( customers[ 1 ].email );
			await page
				.getByRole( 'option', {
					name: `${ customers[ 1 ].email }`,
				} )
				.click();
		} );

		await test.step( 'Add a filter for country', async () => {
			await page.getByRole( 'button', { name: 'Add a filter' } ).click();
			await page
				.locator( 'li' )
				.filter( { hasText: 'Country / Region' } )
				.getByRole( 'button' )
				.click();
			await page
				.locator( '#woocommerce-select-control-2__control-input' )
				.fill( 'US' );
			await page
				.getByRole( 'option', { name: 'United States (US)' } )
				.click();
		} );

		await test.step( 'Apply the filters', async () => {
			await page
				.getByRole( 'link', { name: 'Filter', exact: true } )
				.click();
		} );

		await test.step( 'Check that the filter is applied', async () => {
			await expect(
				page.getByRole( 'cell', { name: customers[ 1 ].email } )
			).toBeVisible();
			await expect(
				page.getByRole( 'cell', { name: customers[ 0 ].email } )
			).toBeHidden();
		} );
	} );
} );
