/**
 * External dependencies
 */
import type { Browser } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	expect,
	request,
	tags,
	test as baseTest,
} from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	BIS_OPTIONS,
	createOutOfStockProduct,
	getLinkFromEmailBody,
	setBISOptions,
	signUpOnProductPage,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { expectEmail, expectEmailContent } from '../../utils/email';
import { deleteOption } from '../../utils/options';

const test = baseTest.extend( {
	product: async ( { restApi }, use ) => {
		const product = await createOutOfStockProduct( restApi );
		await use( product );
		await product.cleanup();
	},
} );

/**
 * Submit the PDP signup form as a logged-out guest, regardless of the test's storageState.
 *
 * @param {Browser} browser   The test's browser fixture.
 * @param {string}  permalink The product permalink.
 * @param {string}  email     The guest's email address.
 */
async function signUpAsGuest(
	browser: Browser,
	permalink: string,
	email: string
): Promise< void > {
	const guestContext = await browser.newContext( {
		storageState: { cookies: [], origins: [] },
	} );
	const guestPage = await guestContext.newPage();
	await guestPage.goto( permalink );
	await signUpOnProductPage( guestPage, { email } );
	await guestContext.close();
}

test.describe(
	'Back in Stock Notifications — receiving confirmations',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeEach( async ( { baseURL } ) => {
			await setBISOptions( request, baseURL!, {
				allowSignups: true,
				doubleOptIn: true,
				requireAccount: false,
			} );
		} );

		test.afterEach( async ( { baseURL } ) => {
			for ( const option of Object.values( BIS_OPTIONS ) ) {
				await deleteOption( request, baseURL!, option );
			}
		} );

		test( 'double-opt-in signup dispatches verify email with UTM params', async ( {
			page,
			product,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-confirm-verify' );

			await signUpAsGuest( browser, product.permalink, email );

			const emailRow = await expectEmail(
				page,
				email,
				/Join the "[^"]+" waitlist\./
			);
			await emailRow.getByRole( 'button', { name: 'View log' } ).click();

			await expectEmailContent(
				page,
				email,
				/Join the "[^"]+" waitlist\./,
				// Confirm button text is in the rendered email body.
				/Confirm/
			);

			// Asserting the verify link shape + UTM params via body inspection.
			const verifyLink = await getLinkFromEmailBody(
				page,
				email,
				/Join the "[^"]+" waitlist\./,
				/email_link_action=verify/
			);

			expect( verifyLink ).toMatch( /email_link_action=verify/ );
			expect( verifyLink ).toMatch( /email_link_action_key=/ );
			expect( verifyLink ).toMatch(
				/utm_source=back-in-stock-notifications/
			);
			expect( verifyLink ).toMatch( /utm_medium=email/ );
		} );

		test( 'clicking the verify link dispatches the confirmation email with UTM on unsubscribe link', async ( {
			page,
			product,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-confirm-verified' );

			await signUpAsGuest( browser, product.permalink, email );

			const verifyLink = await getLinkFromEmailBody(
				page,
				email,
				/Join the "[^"]+" waitlist\./,
				/email_link_action=verify/
			);

			await page.goto( verifyLink );

			// Confirmation email should be dispatched after successful verification (RSM-438).
			await expectEmail(
				page,
				email,
				/You have joined the "[^"]+" waitlist\./
			);

			const unsubscribeLink = await getLinkFromEmailBody(
				page,
				email,
				/You have joined the "[^"]+" waitlist\./,
				/email_link_action=unsubscribe/
			);

			expect( unsubscribeLink ).toMatch(
				/email_link_action=unsubscribe/
			);
			expect( unsubscribeLink ).toMatch( /email_link_action_key=/ );
			expect( unsubscribeLink ).toMatch(
				/utm_source=back-in-stock-notifications/
			);
			expect( unsubscribeLink ).toMatch( /utm_medium=email/ );
		} );

		test( 'following the unsubscribe link cancels the notification', async ( {
			page,
			product,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-confirm-unsub' );

			await signUpAsGuest( browser, product.permalink, email );

			const verifyLink = await getLinkFromEmailBody(
				page,
				email,
				/Join the "[^"]+" waitlist\./,
				/email_link_action=verify/
			);
			await page.goto( verifyLink );

			const unsubscribeLink = await getLinkFromEmailBody(
				page,
				email,
				/You have joined the "[^"]+" waitlist\./,
				/email_link_action=unsubscribe/
			);
			await page.goto( unsubscribeLink );

			// Verify via the admin notifications list: the row should now show Cancelled.
			await page.goto(
				`/wp-admin/admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=${ product.id }`
			);
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );

			await expect( row.getByText( /Cancelled/i ) ).toBeVisible();
		} );
	}
);
