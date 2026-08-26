/**
 * Internal dependencies
 */
import { expect, request, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	BIS_EMAIL_LINKS,
	bisAdminListUrl,
	bisEmailSubject,
	getEmailLinkById,
	resetBISOptions,
	setBISOptions,
	signUpAsGuest,
	test,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { expectEmail, expectEmailContent } from '../../utils/email';

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
			await resetBISOptions( request, baseURL! );
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
				bisEmailSubject.verify( product.name )
			);
			await emailRow.getByRole( 'button', { name: 'View log' } ).click();

			await expectEmailContent(
				page,
				email,
				bisEmailSubject.verify( product.name ),
				// Confirm button text is in the rendered email body.
				/Confirm/
			);

			// Link is selected by its role in the template, then checked for the params it must carry.
			const verifyLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.verify( product.name ),
				BIS_EMAIL_LINKS.actionButton
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

			const verifyLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.verify( product.name ),
				BIS_EMAIL_LINKS.actionButton
			);

			await page.goto( verifyLink );

			// Confirmation email should be dispatched after successful verification (RSM-438).
			await expectEmail(
				page,
				email,
				bisEmailSubject.verified( product.name )
			);

			const unsubscribeLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.verified( product.name ),
				BIS_EMAIL_LINKS.unsubscribe
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

			const verifyLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.verify( product.name ),
				BIS_EMAIL_LINKS.actionButton
			);
			await page.goto( verifyLink );

			const unsubscribeLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.verified( product.name ),
				BIS_EMAIL_LINKS.unsubscribe
			);
			await page.goto( unsubscribeLink );

			// Verify via the admin notifications list: the row should now show Cancelled.
			await page.goto( bisAdminListUrl( product.id ) );
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );

			await expect( row.getByText( /Cancelled/i ) ).toBeVisible();
		} );
	}
);
