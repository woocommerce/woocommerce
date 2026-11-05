/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	customers: async ( { restApi }, use ) => {
		const now = Date.now();
		const customerData = {
			walterWhite: {
				first_name: 'Walter',
				last_name: 'White',
				username: `heisenberg.${ now }`,
				email: `heisenberg.${ now }@example.com`,
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
					email: `heisenberg.${ now }@example.com`,
				},
			},
			jessePinkman: {
				first_name: 'Jesse',
				last_name: 'Pinkman',
				username: `jesse.${ now }`,
				email: `jesse.${ now }@example.com`,
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
					email: `jesse.${ now }@example.com`,
				},
			},
			saulGoodman: {
				first_name: 'Saul',
				last_name: 'Goodman',
				username: `saul.${ now }`,
				email: `saul.${ now }@example.com`,
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
					email: `saul.${ now }@example.com`,
				},
			},
		};

		interface Customer {
			id: number;
			first_name: string;
			last_name: string;
			email: string;
		}

		const customers: Customer[] = [];

		for ( const customer of Object.values( customerData ) ) {
			await restApi
				.post< Customer >( `${ WC_API_PATH }/customers`, customer )
				.then( ( response ) => {
					customers.push( response.data );
				} );
		}

		await use( customers );

		await restApi.post( `${ WC_API_PATH }/customers/batch`, {
			delete: customers.map( ( customer ) => customer.id ),
		} );
	},
} );

test.describe( 'Merchant > Customer List', () => {
	test.beforeEach( async ( { context } ) => {
		// prevents the column picker from saving state between tests
		await context.route( '**/users/**', ( route ) => route.abort() );
	} );

	test( 'Merchant can view a list of all customers, filter and download', async ( {
		page,
		customers,
	} ) => {
		await test.step( 'Go to the customers reports page', async () => {
			const responsePromise = page.waitForResponse(
				'**/wp-json/wc-analytics/reports/customers?orderby**'
			);
			// Pin the page size to 100 so every customer this test created shows
			// on a single page regardless of how many customers other parallel
			// workers have created. This keeps the list assertions and the CSV
			// download (which only streams in-browser when all rows are loaded,
			// otherwise the report is emailed) deterministic.
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fcustomers&per_page=100'
			);
			await responsePromise;
		} );

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
					.getByRole( 'combobox', {
						expanded: false,
						disabled: false,
					} )
					.click();
				await page
					.getByRole( 'combobox', {
						expanded: false,
						disabled: false,
					} )
					.pressSequentially(
						`${ customer.first_name } ${ customer.last_name }`
					);
				await page
					.getByRole( 'option', {
						name: `All customers with names that include ${ customer.first_name } ${ customer.last_name }`,
						exact: true,
					} )
					.waitFor();
				await page
					.getByRole( 'option', {
						name: `${ customer.first_name } ${ customer.last_name }`,
						exact: true,
					} )
					.waitFor();
				await page
					.getByRole( 'option', {
						name: `All customers with names that include ${ customer.first_name } ${ customer.last_name }`,
						exact: true,
					} )
					.click( { delay: 300 } );
				await expect(
					page.getByRole( 'link', { name: customer.email } )
				).toBeVisible();
				await expect(
					page
						.getByRole( 'complementary' )
						.getByText( `${ x }customer` )
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
			await page.getByText( 'Show', { exact: true } ).click();

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
			await page.getByText( 'Show', { exact: true } ).click();

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

			// The date in the filename is generated server-side (WordPress
			// timezone), which can differ from the test runner's local date
			// around a midnight boundary. Assert the filename structure with a
			// YYYY-MM-DD date rather than pinning an exact local date, which is
			// what this step actually verifies (the orderby/order/path encoding).
			// The page size we pinned on the report URL is encoded too.
			const filenamePattern =
				/^customers_\d{4}-\d{2}-\d{2}_orderby-date-last-active_order-desc_page-wc-admin_path--customers_per-page-100\.csv$/;

			expect( download.suggestedFilename() ).toMatch( filenamePattern );
		} );
	} );

	test( 'Merchant can view a single customer', async ( {
		page,
		customers,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin&path=%2Fcustomers' );

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
		await page.goto( 'wp-admin/admin.php?page=wc-admin&path=%2Fcustomers' );

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
				.getByRole( 'group', { name: 'Email' } )
				.getByRole( 'combobox', { expanded: false } )
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
				.getByRole( 'group', { name: 'Country / Region' } )
				.getByRole( 'combobox', { expanded: false } )
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
