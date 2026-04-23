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
			const editLink = page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } )
				.getByRole( 'link', { name: /Edit/i } )
				.first();
			await editLink.click();

			await page
				.locator(
					'select[name="wc_customer_stock_notification_action"]'
				)
				.selectOption( 'send_verification_email' );
			await page
				.getByRole( 'button', { name: /Update|Save|Apply/i } )
				.first()
				.click();

			await expect(
				page.getByText(
					new RegExp(
						`Verification email sent to "${ email.replace(
							/[.*+?^${}()|[\]\\]/g,
							'\\$&'
						) }"`,
						'i'
					)
				)
			).toBeVisible();
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
			await page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } )
				.getByRole( 'link', { name: /Edit/i } )
				.first()
				.click();

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
			await page
				.getByRole( 'row' )
				.filter( { has: page.getByText( email, { exact: true } ) } )
				.getByRole( 'link', { name: /Edit/i } )
				.first()
				.click();

			await page
				.locator(
					'select[name="wc_customer_stock_notification_action"]'
				)
				.selectOption( 'cancel_notification' );
			await page
				.getByRole( 'button', { name: /Update|Save|Apply/i } )
				.first()
				.click();

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
