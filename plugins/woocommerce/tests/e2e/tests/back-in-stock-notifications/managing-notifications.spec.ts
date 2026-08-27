/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { expect, request, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	bisAdminListUrl,
	bisEmailSubject,
	resetBISOptions,
	setBISOptions,
	signUpAsGuest,
	test,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { expectEmail } from '../../utils/email';

/**
 * Click the notification edit-form "Update" button.
 *
 * Scrolling into view first guards against narrow CI viewports where the
 * button can sit just below the fold.
 */
async function submitNotificationEditForm( page: Page ): Promise< void > {
	const updateButton = page.getByRole( 'button', {
		name: 'Update',
		exact: true,
	} );
	await updateButton.scrollIntoViewIfNeeded();
	await updateButton.click();
}

test.describe(
	'Back in Stock Notifications — admin management',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.afterAll( async ( { baseURL } ) => {
			await resetBISOptions( request, baseURL! );
		} );

		// Grouped by opt-in mode rather than flipping the option inside a test:
		// these options are global, so a mid-test write leaks into every test
		// declared after it.
		test.describe( 'Double opt-in — signups land PENDING', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: true,
					requireAccount: false,
				} );
			} );

			test( 'notifications list renders a signup row filtered by product', async ( {
				page,
				product,
				browser,
			} ) => {
				const email = uniqueGuestEmail( 'bis-admin-list' );

				await signUpAsGuest( browser, product.permalink, email );

				await page.goto( bisAdminListUrl( product.id ) );
				await expect(
					page.getByRole( 'cell', { name: email, exact: true } )
				).toBeVisible();
			} );

			test( 'Resend verification email on a pending notification dispatches a new verify email', async ( {
				page,
				product,
				browser,
			} ) => {
				const email = uniqueGuestEmail( 'bis-admin-resend-pending' );

				await signUpAsGuest( browser, product.permalink, email );

				// Wait for the initial verify email so we can count a new one later.
				await expectEmail(
					page,
					email,
					bisEmailSubject.verify( product.name )
				);

				await page.goto( bisAdminListUrl( product.id ) );
				// Surface cross-test bleed (duplicate rows for the same email/product)
				// as an explicit failure instead of silently picking one via .first().
				const row = page.getByRole( 'row' ).filter( {
					has: page.getByText( email, { exact: true } ),
				} );
				await expect( row ).toHaveCount( 1 );
				// Click the title-column link instead of the row-actions "Edit"
				// anchor — the row-actions div is visibility:hidden until hover,
				// and the title link leads to the same edit page.
				await row.locator( 'a.row-title' ).click();

				await page
					.locator(
						'select[name="wc_customer_stock_notification_action"]'
					)
					.selectOption( 'send_verification_email' );
				await submitNotificationEditForm( page );

				await expect(
					page.getByText( `Verification email sent to "${ email }"` )
				).toBeVisible();

				// Assert a second verify email actually landed in the log — the
				// admin success notice alone would pass even if dispatch regressed.
				await expectEmail(
					page,
					email,
					bisEmailSubject.verify( product.name ),
					2
				);
			} );
		} );

		test.describe( 'Single opt-in — signups land ACTIVE', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: false,
				} );
			} );

			test( 'Resend verification is not offered for notifications that are already active', async ( {
				page,
				product,
				browser,
			} ) => {
				const email = uniqueGuestEmail( 'bis-admin-active' );

				await signUpAsGuest( browser, product.permalink, email );

				await page.goto( bisAdminListUrl( product.id ) );
				const row = page.getByRole( 'row' ).filter( {
					has: page.getByText( email, { exact: true } ),
				} );
				await expect( row ).toHaveCount( 1 );
				// Click the title-column link instead of the row-actions "Edit"
				// anchor — the row-actions div is visibility:hidden until hover,
				// and the title link leads to the same edit page.
				await row.locator( 'a.row-title' ).click();

				const actionSelect = page.locator(
					'select[name="wc_customer_stock_notification_action"]'
				);

				// `allTextContents()` does not auto-wait, so wait for the select
				// itself first. Otherwise a slow edit page reads zero options and
				// fails as a missing action rather than a timeout.
				await expect( actionSelect ).toBeVisible();

				const options = await actionSelect
					.locator( 'option' )
					.allTextContents();

				// The select renders for every status, so an empty option list
				// would make the check below pass without proving anything.
				// Anchor on an ACTIVE-only action first.
				expect(
					options.some( ( text ) => /^Cancel$/i.test( text.trim() ) )
				).toBe( true );

				// UI-layer guard: the Resend action is not offered for non-pending rows.
				expect(
					options.some( ( text ) =>
						/Resend verification email/i.test( text )
					)
				).toBe( false );
			} );

			// The Cancel action is only available on ACTIVE / SENT rows.
			test( 'admin Cancel action marks an active notification as Cancelled', async ( {
				page,
				product,
				browser,
			} ) => {
				const email = uniqueGuestEmail( 'bis-admin-cancel' );

				await signUpAsGuest( browser, product.permalink, email );

				await page.goto( bisAdminListUrl( product.id ) );
				const row = page.getByRole( 'row' ).filter( {
					has: page.getByText( email, { exact: true } ),
				} );
				await expect( row ).toHaveCount( 1 );
				// Click the title-column link instead of the row-actions "Edit"
				// anchor — the row-actions div is visibility:hidden until hover,
				// and the title link leads to the same edit page.
				await row.locator( 'a.row-title' ).click();

				await page
					.locator(
						'select[name="wc_customer_stock_notification_action"]'
					)
					.selectOption( 'cancel_notification' );
				await submitNotificationEditForm( page );

				// Wait for the success notice before navigating away —
				// `submitNotificationEditForm` only awaits the click event, not
				// the resulting POST round-trip, so on slower CI runners the
				// next `page.goto()` was racing the submit and the row was
				// still ACTIVE by the time the list view loaded.
				await expect(
					page.getByText( 'Notification updated.' )
				).toBeVisible();

				await page.goto( bisAdminListUrl( product.id ) );
				const refreshedRow = page.getByRole( 'row' ).filter( {
					has: page.getByText( email, { exact: true } ),
				} );
				await expect(
					refreshedRow.getByText( /Cancelled/i )
				).toBeVisible();
			} );
		} );
	}
);
