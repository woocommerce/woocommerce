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
	restockProduct,
	setBISOptions,
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

			await page.goto( bisAdminListUrl( product.id ) );
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
			await expect( row.getByText( /Cancelled/i ) ).toBeVisible();
		} );
	}
);
