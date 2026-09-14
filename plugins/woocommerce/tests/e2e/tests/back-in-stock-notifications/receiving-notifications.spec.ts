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
	getEmailLinkById,
	openEmailInMailLog,
	resetBISOptions,
	restockProduct,
	setBISOptions,
	signUpAsCustomer,
	signUpAsGuest,
	test,
	triggerStockNotificationsBatch,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { expectEmail } from '../../utils/email';

test.describe(
	'Back in Stock Notifications — receiving back-in-stock emails',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { baseURL } ) => {
			// Single opt-in so the notification becomes ACTIVE immediately
			// (no verify step), which is what the back-in-stock dispatch needs.
			await setBISOptions( request, baseURL!, {
				allowSignups: true,
				doubleOptIn: false,
				requireAccount: false,
				createAccountOnSignup: false,
			} );
		} );

		test.afterAll( async ( { baseURL } ) => {
			await resetBISOptions( request, baseURL! );
		} );

		test( 'restocking a product dispatches the back-in-stock email with UTM params', async ( {
			page,
			product,
			restApi,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-restock' );

			await signUpAsGuest( browser, product.permalink, email );

			await restockProduct( restApi, product.id );

			// StockSyncController schedules an AS job; drain it synchronously.
			await triggerStockNotificationsBatch( page );

			await expectEmail(
				page,
				email,
				bisEmailSubject.backInStock( product.name )
			);

			const productLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.backInStock( product.name ),
				BIS_EMAIL_LINKS.actionButton
			);

			// The CTA is the product permalink with exactly utm_source and
			// utm_medium appended (UtmHelper::add_email_utm_params), so the
			// tracking params must be right and stripping them must leave the
			// product page — the link has to actually go somewhere useful.
			const linkUrl = new URL( productLink );

			expect( linkUrl.searchParams.get( 'utm_source' ) ).toBe(
				'back-in-stock-notifications'
			);
			expect( linkUrl.searchParams.get( 'utm_medium' ) ).toBe( 'email' );

			linkUrl.searchParams.delete( 'utm_source' );
			linkUrl.searchParams.delete( 'utm_medium' );
			expect( linkUrl.toString() ).toBe(
				new URL( product.permalink ).toString()
			);

			// A guest signup gets the unsubscribe wording in the footer.
			await openEmailInMailLog(
				page,
				email,
				bisEmailSubject.backInStock( product.name )
			);
			await expect(
				bisEmailBody( page ).locator( 'body' )
			).toContainText( BIS_EMAIL_FOOTER.guest );
		} );

		test( "a logged-in customer's back-in-stock email offers account management instead of unsubscribing", async ( {
			page,
			product,
			restApi,
			browser,
		} ) => {
			await signUpAsCustomer( browser, product.permalink );

			await restockProduct( restApi, product.id );
			await triggerStockNotificationsBatch( page );

			await openEmailInMailLog(
				page,
				customer.email,
				bisEmailSubject.backInStock( product.name )
			);

			const body = bisEmailBody( page ).locator( 'body' );

			// The template forks on whether the signup belongs to an account;
			// the guest fork is what every other email test exercises.
			await expect( body ).toContainText( BIS_EMAIL_FOOTER.loggedIn );
			await expect( body ).not.toContainText( BIS_EMAIL_FOOTER.guest );

			// The wording changes; the link it wraps still has to be there.
			await expect(
				bisEmailBody( page ).locator(
					`a${ BIS_EMAIL_LINKS.unsubscribe }`
				)
			).toHaveAttribute( 'href', /email_link_action=unsubscribe/ );
		} );

		test( 'unsubscribe link in the back-in-stock email cancels the notification', async ( {
			page,
			product,
			restApi,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-restock-unsub' );

			await signUpAsGuest( browser, product.permalink, email );

			await restockProduct( restApi, product.id );
			await triggerStockNotificationsBatch( page );

			await expectEmail(
				page,
				email,
				bisEmailSubject.backInStock( product.name )
			);

			const unsubscribeLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.backInStock( product.name ),
				BIS_EMAIL_LINKS.unsubscribe
			);
			await page.goto( unsubscribeLink );

			// The shopper sees the outcome on the shop page they are sent to.
			await expect(
				page.getByText( bisNotice.unsubscribed( email, product.name ) )
			).toBeVisible();

			await page.goto( bisAdminListUrl( product.id ) );
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
			await expect( row.getByText( /Cancelled/i ) ).toBeVisible();
		} );

		test( 'an unsubscribe link with a tampered key changes nothing', async ( {
			page,
			product,
			restApi,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-restock-bad-key' );

			await signUpAsGuest( browser, product.permalink, email );

			await restockProduct( restApi, product.id );
			await triggerStockNotificationsBatch( page );

			const unsubscribeLink = await getEmailLinkById(
				page,
				email,
				bisEmailSubject.backInStock( product.name ),
				BIS_EMAIL_LINKS.unsubscribe
			);

			await page.goto( corruptEmailLinkKey( unsubscribeLink ) );

			// A rejected key is a no-op: no redirect to the shop, no notice.
			await expect( page ).toHaveURL( /notification_id=/ );
			await expect(
				page.getByText( bisNotice.unsubscribed( email, product.name ) )
			).toHaveCount( 0 );

			await page.goto( bisAdminListUrl( product.id ) );
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
			await expect( row ).toHaveCount( 1 );
			await expect( row.getByText( /Cancelled/i ) ).toHaveCount( 0 );

			// Positive control: the untouched link still unsubscribes, so the
			// row above survived because of the key and not because
			// unsubscribing is broken.
			await page.goto( unsubscribeLink );
			await expect(
				page.getByText( bisNotice.unsubscribed( email, product.name ) )
			).toBeVisible();
		} );
	}
);
