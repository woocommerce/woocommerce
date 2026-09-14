/**
 * Internal dependencies
 */
import { expect, request, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { customer } from '../../test-data/data';
import {
	BIS_EMAIL_FOOTER,
	BIS_EMAIL_LINKS,
	bisAdminListUrl,
	bisEmailBody,
	bisEmailSubject,
	bisNotice,
	corruptEmailLinkKey,
	expireVerificationLinks,
	getEmailLinkById,
	openEmailInMailLog,
	resetBISOptions,
	setBISOptions,
	signUpAsCustomer,
	signUpAsGuest,
	test,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { expectEmail, expectEmailContent } from '../../utils/email';
import { clearFilters } from '../../utils/filters';

test.describe(
	'Back in Stock Notifications — receiving confirmations',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { baseURL } ) => {
			await setBISOptions( request, baseURL!, {
				allowSignups: true,
				doubleOptIn: true,
				requireAccount: false,
				createAccountOnSignup: false,
			} );
		} );

		test.afterAll( async ( { baseURL } ) => {
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

			// Read the params rather than substring-matching the URL: a
			// present-but-empty key still satisfies `email_link_action_key=`.
			const verifyUrl = new URL( verifyLink );

			expect( verifyUrl.searchParams.get( 'email_link_action' ) ).toBe(
				'verify'
			);
			expect(
				verifyUrl.searchParams.get( 'email_link_action_key' )
			).toBeTruthy();
			expect( verifyUrl.searchParams.get( 'utm_source' ) ).toBe(
				'back-in-stock-notifications'
			);
			expect( verifyUrl.searchParams.get( 'utm_medium' ) ).toBe(
				'email'
			);
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

			// The link redirects to the shop with a notice naming the product.
			await expect(
				page.getByText( bisNotice.verified( product.name ) )
			).toBeVisible();

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

			const unsubscribeUrl = new URL( unsubscribeLink );

			expect(
				unsubscribeUrl.searchParams.get( 'email_link_action' )
			).toBe( 'unsubscribe' );
			expect(
				unsubscribeUrl.searchParams.get( 'email_link_action_key' )
			).toBeTruthy();
			expect( unsubscribeUrl.searchParams.get( 'utm_source' ) ).toBe(
				'back-in-stock-notifications'
			);
			expect( unsubscribeUrl.searchParams.get( 'utm_medium' ) ).toBe(
				'email'
			);

			// A guest signup gets the unsubscribe wording in the footer, not
			// the account-management one.
			await openEmailInMailLog(
				page,
				email,
				bisEmailSubject.verified( product.name )
			);
			await expect(
				bisEmailBody( page ).locator( 'body' )
			).toContainText( BIS_EMAIL_FOOTER.guest );
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

			// The shopper sees the outcome on the shop page they are sent to.
			await expect(
				page.getByText( bisNotice.unsubscribed( email, product.name ) )
			).toBeVisible();

			// Verify via the admin notifications list: the row should now show Cancelled.
			await page.goto( bisAdminListUrl( product.id ) );
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );

			await expect( row.getByText( /Cancelled/i ) ).toBeVisible();
		} );

		test( "a logged-in customer's confirmation email offers account management instead of unsubscribing", async ( {
			page,
			product,
			browser,
		} ) => {
			await signUpAsCustomer( browser, product.permalink );

			const verifyLink = await getEmailLinkById(
				page,
				customer.email,
				bisEmailSubject.verify( product.name ),
				BIS_EMAIL_LINKS.actionButton
			);
			await page.goto( verifyLink );

			await openEmailInMailLog(
				page,
				customer.email,
				bisEmailSubject.verified( product.name )
			);

			const body = bisEmailBody( page ).locator( 'body' );

			// The templates fork on whether the signup belongs to an account;
			// every other email test signs up as a guest, so this is the only
			// place the account branch renders.
			await expect( body ).toContainText( BIS_EMAIL_FOOTER.loggedIn );
			await expect( body ).not.toContainText( BIS_EMAIL_FOOTER.guest );

			// The wording changes; the link it wraps still has to be there.
			await expect(
				bisEmailBody( page ).locator(
					`a${ BIS_EMAIL_LINKS.unsubscribe }`
				)
			).toHaveAttribute( 'href', /email_link_action=unsubscribe/ );
		} );

		test( 'a verify link with a tampered key leaves the signup pending', async ( {
			page,
			product,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-confirm-bad-key' );

			await signUpAsGuest( browser, product.permalink, email );

			const verifyLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.verify( product.name ),
				BIS_EMAIL_LINKS.actionButton
			);

			await page.goto( corruptEmailLinkKey( verifyLink ) );

			// A rejected key is a no-op: no redirect to the shop, no notice.
			await expect( page ).toHaveURL( /notification_id=/ );
			await expect(
				page.getByText( bisNotice.verified( product.name ) )
			).toHaveCount( 0 );

			await page.goto( bisAdminListUrl( product.id ) );
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
			await expect( row.getByText( /Pending/i ) ).toBeVisible();

			// Positive control: the untouched link still verifies, so the
			// signup above stayed pending because of the key and not because
			// verification is broken.
			await page.goto( verifyLink );
			await expect(
				page.getByText( bisNotice.verified( product.name ) )
			).toBeVisible();
		} );

		test( 'an expired verify link is rejected', async ( {
			page,
			product,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-confirm-expired' );

			await signUpAsGuest( browser, product.permalink, email );

			const verifyLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.verify( product.name ),
				BIS_EMAIL_LINKS.actionButton
			);

			await expireVerificationLinks( page );
			await page.goto( verifyLink );

			await expect( page ).toHaveURL( /notification_id=/ );
			await expect(
				page.getByText( bisNotice.verified( product.name ) )
			).toHaveCount( 0 );

			await page.goto( bisAdminListUrl( product.id ) );
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
			await expect( row.getByText( /Pending/i ) ).toBeVisible();

			// Positive control: back on the default threshold the very same
			// link verifies, so the expiry filter is what rejected it.
			await clearFilters( page );
			await page.goto( verifyLink );
			await expect(
				page.getByText( bisNotice.verified( product.name ) )
			).toBeVisible();
		} );
	}
);
