/**
 * Internal dependencies
 */
import { expect, request, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH, CUSTOMER_STATE_PATH } from '../../playwright.config';
import {
	bisEmailSubject,
	resetBISOptions,
	setBISOptions,
	signUpOnProductPage,
	test,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { expectEmail } from '../../utils/email';

test.describe(
	'Back in Stock Notifications — signing up',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.afterAll( async ( { baseURL } ) => {
			await resetBISOptions( request, baseURL! );
		} );

		test.describe( 'Logged-in customer, single opt-in', () => {
			test.use( { storageState: CUSTOMER_STATE_PATH } );

			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: false,
				} );
			} );

			test( 'the signup form renders on an out-of-stock simple product page', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				await expect(
					page.getByRole( 'heading', {
						name: /Want to be notified when this product is back in stock\?/i,
					} )
				).toBeVisible();

				// A logged-in customer does not see the email field — email is derived server-side.
				await expect(
					page.getByRole( 'textbox', {
						name: /Email address to be notified/i,
					} )
				).toHaveCount( 0 );
				await expect(
					page.getByRole( 'button', { name: /Notify me/i } )
				).toBeVisible();
			} );

			test( 'submitting the form surfaces a success notice', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );
				await signUpOnProductPage( page );

				await expect(
					page.getByText( /You have successfully signed up/i )
				).toBeVisible();
			} );

			test( 'a repeated signup surfaces the "already joined" notice', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );
				await signUpOnProductPage( page );

				// Wait for the first submission to finish so the re-navigation
				// below can't race it. This is the first signup for a freshly
				// created product, so it always succeeds.
				await expect(
					page.getByText( /You have successfully signed up/i )
				).toBeVisible();

				// Submitting the form a second time (the "already joined"
				// notice only renders as a form-submit response; the cached
				// state shown on page reload is behind the off-by-default
				// `woocommerce_customer_stock_notifications_personalization_enabled`
				// filter).
				await page.goto( product.permalink );
				await signUpOnProductPage( page );
				await expect(
					page.getByText( /You have already joined this waitlist/i )
				).toBeVisible();
			} );
		} );

		test.describe( 'Guest — single opt-in', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: false,
				} );
			} );

			test( 'the signup form shows an email field for guests', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				await expect(
					page.getByRole( 'textbox', {
						name: /Email address to be notified/i,
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'button', { name: /Notify me/i } )
				).toBeVisible();
			} );
		} );

		test.describe( 'Guest — double opt-in', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: true,
					requireAccount: false,
				} );
			} );

			// eslint-disable-next-line playwright/expect-expect -- `expectEmail()` asserts on the mail log.
			test( 'submitting signup dispatches a verification email', async ( {
				page,
				product,
				browser,
			} ) => {
				const email = uniqueGuestEmail( 'bis-guest-double' );

				await page.goto( product.permalink );
				await signUpOnProductPage( page, { email } );

				// Switch to an admin context to inspect the mail log —
				// WP Mail Logging is an admin-only screen.
				const adminContext = await browser.newContext( {
					storageState: ADMIN_STATE_PATH,
				} );
				const adminPage = await adminContext.newPage();
				await expectEmail(
					adminPage,
					email,
					bisEmailSubject.verify( product.name )
				);
				await adminContext.close();
			} );
		} );

		test.describe( 'Guest — requires account', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: true,
				} );
			} );

			test( 'the signup form hides the email field when an account is required', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				await expect(
					page.getByRole( 'textbox', {
						name: /Email address to be notified/i,
					} )
				).toHaveCount( 0 );

				// Pair the absence with the prompt core renders in its place,
				// so a 404 or a failed render can't pass as account gating.
				await expect(
					page.getByText(
						/Please log in to sign up for stock notifications/i
					)
				).toBeVisible();
			} );
		} );
	}
);
