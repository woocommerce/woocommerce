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

		const emailSentMessage = page.getByText(
			'check your email for the confirmation link'
		);
		const emailNotSentMessage = page.getByText(
			'the email could not be sent'
		);

		// We don't have to care if the email was sent, we just want to know the button click attempts a reset.
		await expect(
			emailSentMessage.or( emailNotSentMessage )
		).toBeVisible();
	} );
} );
