const { test, expect } = require( '@playwright/test' );

const email = `test-${ Math.random() }@example.com`;
const username = `newcustomer-${ Math.random() }`;
const emailContent = '#wp-mail-logging-modal-content-body-content';
const emailContentJson = '#wp-mail-logging-modal-format-json';

test.describe( 'Shopper Account Email Receiving', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				email
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

	test.afterEach( async ( { page } ) => {
		// delete created customer
		await page.goto( 'wp-admin/users.php' );
		await page.getByText( username ).last().hover();
		await page.getByRole( 'link', { name: 'Delete' } ).last().click();
		await page.getByRole( 'button', { name: 'Confirm Deletion' } ).click();
	} );

	test( 'should receive an email when creating an account', async ( {
		page,
	} ) => {
		// create a new customer
		await page.goto( 'wp-admin/user-new.php' );

		await page.getByLabel( ' Username (required) ' ).fill( username );
		await page.getByLabel( ' Email (required) ' ).fill( email );
		await page.getByLabel( ' First Name ' ).fill( 'New' );
		await page.getByLabel( ' Last Name ' ).fill( 'Customer' );

		await page.getByLabel( 'Send the new user an email' ).check();

		await page.getByLabel( 'Role' ).selectOption( 'Customer' );

		await page.getByRole( 'button', { name: 'Add New User' } ).click();

		await page.waitForSelector( '.notice' );

		// verify that the email was sent
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				email
			) }`
		);

		await expect( page.locator( 'td.column-receiver' ).first() ).toHaveText(
			email
		);
		await expect(
			page.getByRole( 'cell', {
				name: '[WooCommerce Core E2E Test Suite] Login Details',
			} )
		).toBeVisible();

		await page.getByRole( 'button', { name: 'View log' } ).first().click();
		await page.locator( emailContentJson ).click();
		await expect( page.locator( emailContent ) ).toContainText(
			`Username: ${ username }`
		);
	} );

	test( 'should receive an email when password reset initiated from admin', async ( {
		page,
	} ) => {
		// create a new customer
		await page.goto( 'wp-admin/user-new.php' );

		await page.getByLabel( ' Username (required) ' ).fill( username );
		await page.getByLabel( ' Email (required) ' ).fill( email );
		await page.getByLabel( ' First Name ' ).fill( 'New' );
		await page.getByLabel( ' Last Name ' ).fill( 'Customer' );

		await page.getByLabel( 'Send the new user an email' ).uncheck();

		await page.getByLabel( 'Role' ).selectOption( 'Customer' );

		await page.getByRole( 'button', { name: 'Add New User' } ).click();

		await page.waitForSelector( '.notice' );

		// verify that no email was sent on account creation
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				email
			) }`
		);
		await expect(
			page.locator( 'td.column-receiver' ).first()
		).not.toHaveText( email );

		// initiate password reset from admin
		await page.goto( 'wp-admin/users.php' );

		await page.getByText( username ).last().hover();
		await page
			.getByRole( 'link', { name: 'Send password reset' } )
			.last()
			.click();

		// verify that the email was sent
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				email
			) }`
		);

		await expect( page.locator( 'td.column-receiver' ).first() ).toHaveText(
			email
		);
		await expect(
			page.getByRole( 'cell', {
				name: '[WooCommerce Core E2E Test Suite] Password Reset',
			} )
		).toBeVisible();

		await page.getByRole( 'button', { name: 'View log' } ).first().click();
		await page.locator( emailContentJson ).click();
		await expect( page.locator( emailContent ) ).toContainText(
			`Username: ${ username }`
		);
	} );
} );

test.describe( 'Shopper Password Reset Email Receiving', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				'customer@woocommercecoree2etestsuite.com'
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
		await page.goto( 'my-account/lost-password/' );

		await page
			.getByLabel( 'Username or email' )
			.fill( 'customer@woocommercecoree2etestsuite.com' );
		await page.getByRole( 'button', { name: 'Reset password' } ).click();

		await expect(
			await page.getByText( 'Password reset email has been sent' ).count()
		).toBeGreaterThan( 0 );

		// verify that the email was sent
		await page.goto( 'wp-login.php' );
		await page.getByLabel( 'Username or Email Address' ).fill( 'admin' );
		await page.getByLabel( 'Password', { exact: true } ).fill( 'password' );
		await page.getByRole( 'button', { name: 'Log In' } ).click();
		await page.goto(
			`wp-admin/tools.php?page=wpml_plugin_log&s=${ encodeURIComponent(
				'customer@woocommercecoree2etestsuite.com'
			) }`
		);

		await expect( page.locator( 'td.column-receiver' ).first() ).toHaveText(
			'customer@woocommercecoree2etestsuite.com'
		);
		await expect(
			page
				.getByRole( 'cell', { name: 'Password Reset Request for' } )
				.first()
		).toBeVisible();

		await page.getByRole( 'button', { name: 'View log' } ).first().click();
		await page.locator( emailContentJson ).click();
		await expect( page.locator( emailContent ) ).toContainText(
			'Username: customer'
		);
	} );
} );
