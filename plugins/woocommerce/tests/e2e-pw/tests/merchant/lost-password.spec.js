const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );

test.describe( 'Can go to lost password page and submit the form', () => {
	test( 'can visit the lost password page from the login page', async ( {
		page,
	} ) => {
		await page.goto( '/wp-login.php' );
		await page.getByRole( 'link', { name: 'Lost your password?' } ).click();
		expect( page.url() ).toContain( '/wp-login.php?action=lostpassword' );
		await expect( page.getByText( 'Get New Password' ) ).toBeVisible();
	} );

	test( 'can submit the lost password form', async ( { page } ) => {
		await page.goto( '/wp-login.php?action=lostpassword' );
		await page.getByLabel( 'Username or Email Address' ).click();
		await page
			.getByLabel( 'Username or Email Address' )
			.fill( admin.username );
		await page.getByRole( 'button', { name: 'Get New Password' } ).click();

		try {
			// For local testing, the email might not be sent, so we can ignore this error.
			await expect(
				page.getByText(
					/The email could not be sent. Your site may not be correctly configured to send emails/i
				)
			).toBeVisible();
		} catch ( e ) {
			// eslint-disable-next-line jest/no-try-expect
			await page.waitForURL( '**/wp-login.php?checkemail=confirm' );
			// eslint-disable-next-line jest/no-try-expect
			await expect(
				page.getByText( /Check your email for the confirmation link/i )
			).toBeVisible();
		}
	} );
} );
