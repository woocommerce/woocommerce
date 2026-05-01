/**
 * External dependencies
 */
import type { Browser, Page } from '@playwright/test';

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
	setBISOptions,
	signUpOnProductPage,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { expectEmail } from '../../utils/email';
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

/**
 * Click the notification edit-form "Update" button.
 *
 * Scrolling into view first is required on narrow CI viewports where the
 * product-thumbnail overlay can otherwise intercept the click. On WP
 * 7.0-RC2 the locator still resolves the right button but Playwright's
 * actionability check times out — something else in the new admin chrome
 * intercepts at click time. `force: true` bypasses the check; revisit
 * once WP 7.0 is final and we can see what's actually covering the button.
 */
async function submitNotificationEditForm( page: Page ): Promise< void > {
	const updateButton = page.getByRole( 'button', {
		name: 'Update',
		exact: true,
	} );
	await updateButton.scrollIntoViewIfNeeded();
	await updateButton.click( { force: true } );
}

test.describe(
	'Back in Stock Notifications — admin management',
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

		test( 'notifications list renders a signup row filtered by product', async ( {
			page,
			product,
			browser,
		} ) => {
			const email = uniqueGuestEmail( 'bis-admin-list' );

			await signUpAsGuest( browser, product.permalink, email );

			await page.goto(
				`/wp-admin/admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=${ product.id }`
			);
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
			await expectEmail( page, email, /Join the "[^"]+" waitlist\./ );

			await page.goto(
				`/wp-admin/admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=${ product.id }`
			);
			// Surface cross-test bleed (duplicate rows for the same email/product)
			// as an explicit failure instead of silently picking one via .first().
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
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
			// Poll across reloads because Playwright's auto-retry doesn't
			// refresh the mail-log page and the second email lands
			// asynchronously via Action Scheduler.
			const mailLogUrl = `wp-admin/tools.php?page=wpml_plugin_log&search[place]=receiver&search[term]=${ encodeURIComponent(
				email
			) }&orderby=timestamp&order=desc`;
			await expect( async () => {
				await page.goto( mailLogUrl );
				await expect(
					page
						.getByRole( 'row' )
						.filter( {
							has: page.getByRole( 'cell', {
								name: email,
								exact: true,
							} ),
						} )
						.filter( {
							// `exact: true` is a no-op when `name` is a RegExp — Playwright
							// only applies it to string matchers.
							has: page.getByRole( 'cell', {
								name: /Join the "[^"]+" waitlist\./,
							} ),
						} )
				).toHaveCount( 2 );
			} ).toPass();
		} );

		test( 'Resend verification is not offered for notifications that are already active', async ( {
			page,
			product,
			baseURL,
			browser,
		} ) => {
			// Flip to single opt-in so the signup goes straight to ACTIVE.
			await setBISOptions( request, baseURL!, { doubleOptIn: false } );

			const email = uniqueGuestEmail( 'bis-admin-active' );

			await signUpAsGuest( browser, product.permalink, email );

			await page.goto(
				`/wp-admin/admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=${ product.id }`
			);
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
			await expect( row ).toHaveCount( 1 );
			// Click the title-column link instead of the row-actions "Edit"
			// anchor — the row-actions div is visibility:hidden until hover,
			// and the title link leads to the same edit page.
			await row.locator( 'a.row-title' ).click();

			const options = await page
				.locator(
					'select[name="wc_customer_stock_notification_action"] option'
				)
				.allTextContents();

			// UI-layer guard: the Resend action is not offered for non-pending rows.
			expect(
				options.some( ( text ) =>
					/Resend verification email/i.test( text )
				)
			).toBe( false );
		} );

		test( 'admin Cancel action marks an active notification as Cancelled', async ( {
			page,
			product,
			baseURL,
			browser,
		} ) => {
			// Flip to single opt-in so the signup goes straight to ACTIVE.
			// The Cancel action is only available on ACTIVE / SENT rows.
			await setBISOptions( request, baseURL!, { doubleOptIn: false } );

			const email = uniqueGuestEmail( 'bis-admin-cancel' );

			await signUpAsGuest( browser, product.permalink, email );

			await page.goto(
				`/wp-admin/admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=${ product.id }`
			);
			const row = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
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

			await page.goto(
				`/wp-admin/admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=${ product.id }`
			);
			const refreshedRow = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } );
			await expect(
				refreshedRow.getByText( /Cancelled/i )
			).toBeVisible();
		} );
	}
);
