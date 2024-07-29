const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const { customer } = require( '../../test-data/data' );

const emailContent = '#wp-mail-logging-modal-content-body-content';
const emailContentHtml = '#wp-mail-logging-modal-format-html';

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	user: async ( { api }, use ) => {
		const now = Date.now();
		const user = {
			username: `${ now }`,
			email: `${ now }@example.com`,
			firstName: `Customer`,
			lastName: `the ${ now }th`,
			role: 'Customer',
		};
		await use( user );
		console.log( `Deleting user ${ user.id }` );
		await api.delete( `customers/${ user.id }`, { force: true } );
	},
} );

test.describe(
	'Shopper Account Email Receiving',
	{ tag: [ '@payments', '@services' ] },
	() => {
		test.beforeEach( async ( { page, user } ) => {
			await page.goto(
				`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
					user.email
				) }`
			);
			// clear out the email logs before each test
			while (
				await page.locator( '#bulk-action-selector-top' ).isVisible()
			) {
				// In WP 6.3, label intercepts check action. Need to force.
				await page
					.getByLabel( 'Select All' )
					.first()
					.check( { force: true } );
				await page
					.locator( '#bulk-action-selector-top' )
					.selectOption( 'delete' );
				await page.locator( '#doaction' ).click();
			}
		} );

		test( 'should receive an email when creating an account', async ( {
			page,
			user,
		} ) => {
			// create a new customer
			await page.goto( 'wp-admin/user-new.php' );
			await expect( page ).toHaveTitle( /Add New User/ );

			await test.step( 'create a new user', async () => {
				// Wait for the password to be generated otherwise it will steal the focus from other fields
				await expect( page.locator( '#pass1' ) ).not.toHaveValue( '' );

				user.password = await page.locator( '#pass1' ).inputValue();

				await page.getByLabel( 'Username' ).fill( user.username );
				await page.getByLabel( 'Email (required)' ).fill( user.email );
				await page.getByLabel( 'First Name' ).fill( user.firstName );
				await page.getByLabel( 'Last Name' ).fill( user.lastName );
				await page.getByText( 'Send the new user an email' ).check();
				await page.getByLabel( 'Role' ).selectOption( user.role );
				await page
					.getByRole( 'button', { name: 'Add New User' } )
					.click();

				await expect( page ).toHaveTitle( /Users/ );

				// We need the newly created user id to delete it during cleanup
				user.id = new URLSearchParams(
					new URL( page.url() ).search
				).get( 'id' );
			} );

			await test.step( 'verify that the email was sent', async () => {
				await page.goto(
					`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
						user.email
					) }`
				);

				await expect(
					page.locator( 'td.column-receiver' ).first()
				).toHaveText( user.email );
				await expect(
					page.getByRole( 'cell', {
						name: '[WooCommerce Core E2E Test Suite] Login Details',
					} )
				).toBeVisible();

				await page
					.getByRole( 'button', { name: 'View log' } )
					.first()
					.click();

				await page.locator( emailContentHtml ).click();
				await page
					.locator( '.wp-mail-logging-modal-row-html-container' )
					.waitFor( { state: 'visible' } );
				await expect( page.locator( emailContent ) ).toContainText(
					user.email
				);
			} );
		} );

		test( 'should receive an email when password reset initiated from admin', async ( {
			page,
			user,
		} ) => {
			// create a new customer
			await page.goto( 'wp-admin/user-new.php' );
			await expect( page ).toHaveTitle( /Add New User/ );

			await test.step( 'create a new user', async () => {
				// Wait for the password to be generated otherwise it will steal the focus from other fields
				await expect( page.locator( '#pass1' ) ).not.toHaveValue( '' );

				user.password = await page.locator( '#pass1' ).inputValue();

				await page.getByLabel( 'Username' ).fill( user.username );
				await page.getByLabel( 'Email (required)' ).fill( user.email );
				await page.getByLabel( 'First Name' ).fill( user.firstName );
				await page.getByLabel( 'Last Name' ).fill( user.lastName );
				await page.getByText( 'Send the new user an email' ).uncheck();
				await page.getByLabel( 'Role' ).selectOption( user.role );
				await page
					.getByRole( 'button', { name: 'Add New User' } )
					.click();

				await expect( page ).toHaveTitle( /Users/ );

				// We need the newly created user id to delete it during cleanup
				user.id = new URLSearchParams(
					new URL( page.url() ).search
				).get( 'id' );
			} );

			await test.step( 'verify that no email was sent on account creation', async () => {
				await page.goto(
					`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
						user.email
					) }`
				);
				await expect(
					page.locator( 'td.column-receiver' ).first()
				).not.toHaveText( user.email );
			} );

			await test.step( 'initiate password reset from admin', async () => {
				await page.goto( 'wp-admin/users.php' );

				await page.getByText( user.email ).hover();
				await page
					.locator( `#user-${ user.id }` )
					.getByRole( 'link', { name: 'Send password reset' } )
					.click();
			} );

			await test.step( 'verify that the email was sent', async () => {
				await page.goto(
					`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
						user.email
					) }`
				);

				await expect(
					page.locator( 'td.column-receiver' ).first()
				).toHaveText( user.email );
				await expect(
					page.getByRole( 'cell', {
						name: '[WooCommerce Core E2E Test Suite] Password Reset',
					} )
				).toBeVisible();

				await page
					.getByRole( 'button', { name: 'View log' } )
					.first()
					.click();
				await page.locator( emailContentHtml ).click();
				await page
					.locator( '.wp-mail-logging-modal-row-html-container' )
					.waitFor( { state: 'visible' } );
				await expect( page.locator( emailContent ) ).toContainText(
					user.email
				);
			} );
		} );
	}
);

test.describe(
	'Shopper Password Reset Email Receiving',
	{ tag: [ '@payments', '@services' ] },
	() => {
		test.beforeEach( async ( { page } ) => {
			await page.goto(
				`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
					customer.email
				) }`
			);
			// clear out the email logs before each test
			while (
				await page.locator( '#bulk-action-selector-top' ).isVisible()
			) {
				// In WP 6.3, label intercepts check action. Need to force.
				await page
					.getByLabel( 'Select All' )
					.first()
					.check( { force: true } );
				await page
					.locator( '#bulk-action-selector-top' )
					.selectOption( 'delete' );
				await page.locator( '#doaction' ).click();
			}
		} );

		test.use( { cookies: [], origins: [] } );

		test( 'should receive an email when initiating a password reset', async ( {
			page,
		} ) => {
			// Effect a log out/simulate a new browsing session by dropping all cookies.
			await page.context().clearCookies();
			await page.reload();
			await page.goto( 'my-account/lost-password/' );

			await test.step( 'initiate password reset from my account', async () => {
				await page
					.getByLabel( 'Username or email' )
					.fill( customer.email );
				await page
					.getByRole( 'button', { name: 'Reset password' } )
					.click();

				await expect(
					await page
						.getByText( 'Password reset email has been sent' )
						.count()
				).toBeGreaterThan( 0 );
			} );

			await test.step( 'verify that the email was sent', async () => {
				await page.goto( 'wp-login.php' );
				await page
					.getByLabel( 'Username or Email Address' )
					.fill( 'admin' );
				await page
					.getByLabel( 'Password', { exact: true } )
					.fill( 'password' );
				await page.getByRole( 'button', { name: 'Log In' } ).click();
				await page.goto(
					`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
						customer.email
					) }`
				);

				await expect(
					page.locator( 'td.column-receiver' ).first()
				).toHaveText( customer.email );
				await expect(
					page
						.getByRole( 'cell', {
							name: 'Password Reset Request for',
						} )
						.first()
				).toBeVisible();

				await page
					.getByRole( 'button', { name: 'View log' } )
					.first()
					.click();
				await page.locator( emailContentHtml ).click();
				await page
					.locator( '.wp-mail-logging-modal-row-html-container' )
					.waitFor( { state: 'visible' } );
				await expect( page.locator( emailContent ) ).toContainText(
					customer.email
				);
			} );
		} );
	}
);
