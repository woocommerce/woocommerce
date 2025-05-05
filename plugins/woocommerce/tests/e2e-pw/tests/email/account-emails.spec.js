/* eslint-disable playwright/expect-expect */
/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { getFakeCustomer } from '../../utils/data';
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expectEmail, expectEmailContent } from '../../utils/email';
import { setOption } from '../../utils/options';
import { WC_API_PATH } from '../../utils/api-client';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	user: async ( { restApi }, use ) => {
		let user;
		await restApi
			.post( `${ WC_API_PATH }/customers`, getFakeCustomer() )
			.then( ( response ) => {
				user = response.data;
			} );
		await use( user );
		await restApi.delete( `${ WC_API_PATH }/customers/${ user.id }`, {
			force: true,
		} );
	},
} );

test.beforeEach( async ( { baseURL } ) => {
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_email_improvements_enabled',
		'no'
	);
} );

test.skip(
	process.env.IS_MULTISITE,
	'Test not working on a multisite setup, see https://github.com/woocommerce/woocommerce/issues/55082'
);

test( 'New customer should receive an email with login details', async ( {
	page,
	user,
} ) => {
	let emailRow;
	await test.step( 'check the email exists', async () => {
		emailRow = await expectEmail(
			page,
			user.email,
			/Your .* account has been created/
		);
	} );

	await test.step( 'check the email content', async () => {
		await emailRow.getByRole( 'button', { name: 'View log' } ).click();

		await expectEmailContent(
			page,
			user.email,
			/Your .* account has been created/,
			/Welcome to .*/
		);
	} );
} );

test( 'Customer should receive an email when initiating a password reset', async ( {
	page,
	user,
	browser,
} ) => {
	await test.step( 'initiate password reset from my account', async () => {
		const loggedOutContext = await browser.newContext( {
			storageState: { cookies: [], origins: [] },
		} );
		const loggedOutPage = await loggedOutContext.newPage();
		await loggedOutPage.goto( 'my-account/lost-password/' );
		await loggedOutPage
			.getByLabel( 'Username or email' )
			.fill( user.email );
		await loggedOutPage
			.getByRole( 'button', { name: 'Reset password' } )
			.click();

		await expect(
			loggedOutPage
				.getByRole( 'alert' )
				.getByText( 'Password reset email has been sent.' )
		).toBeVisible();
	} );

	let emailRow;
	await test.step( 'check the email exists', async () => {
		emailRow = await expectEmail(
			page,
			user.email,
			/Password Reset Request for .*/
		);
	} );

	await test.step( 'check the email content', async () => {
		await emailRow.getByRole( 'button', { name: 'View log' } ).click();

		await expectEmailContent(
			page,
			user.email,
			/Password Reset Request for .*/,
			/Password Reset Request/
		);
	} );
} );

test( 'Customer should receive an email when password reset initiated from admin', async ( {
	page,
	user,
} ) => {
	await test.step( 'admin sends password reset link', async () => {
		await page.goto( 'wp-admin/users.php' );
		await page.getByText( user.email ).hover();
		await page
			.locator( `#user-${ user.id }` )
			.getByRole( 'link', { name: 'Send password reset' } )
			.click();
	} );

	let emailRow;
	await test.step( 'check the email exists', async () => {
		emailRow = await expectEmail( page, user.email, /Password Reset/ );
	} );

	await test.step( 'check the email content', async () => {
		await emailRow.getByRole( 'button', { name: 'View log' } ).click();

		await expectEmailContent(
			page,
			user.email,
			/Password Reset/,
			/Someone has requested a password reset for the following account/
		);
	} );
} );
