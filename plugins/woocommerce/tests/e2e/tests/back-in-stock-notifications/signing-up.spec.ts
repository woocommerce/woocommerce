/**
 * Internal dependencies
 */
import { expect, request, tags } from '../../fixtures/fixtures';
import { CUSTOMER_STATE_PATH } from '../../playwright.config';
import { customer } from '../../test-data/data';
import {
	bisConsentCheckbox,
	bisEmailSubject,
	bisFormLocator,
	bisNotice,
	bisTargetProductInput,
	expectEmailAsAdmin,
	expectNoSignupAsAdmin,
	findCustomerByEmail,
	resetBISOptions,
	setBISOptions,
	signUpOnProductPage,
	test,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';
import { clearFilters, setFilterValue } from '../../utils/filters';

test.describe(
	'Back in Stock Notifications — signing up',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.afterAll( async ( { baseURL } ) => {
			await resetBISOptions( request, baseURL! );
		} );

		test.describe( 'Signups disabled', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: false,
					createAccountOnSignup: false,
				} );
			} );

			test( 'turning signups off removes the form from the product page', async ( {
				page,
				product,
				baseURL,
			} ) => {
				// Positive control: the form is there while signups are on, so
				// a product page that renders no form for an unrelated reason
				// cannot pass the assertion below.
				await page.goto( product.permalink );
				await expect( bisFormLocator( page ) ).toHaveCount( 1 );

				// Flipped inside the test rather than in `beforeAll` so the
				// control above and the assertion below run against the same
				// product. The next describe's `beforeAll` sets it back.
				await setBISOptions( request, baseURL!, {
					allowSignups: false,
				} );

				await page.goto( product.permalink );
				await expect( bisFormLocator( page ) ).toHaveCount( 0 );
				await expect(
					page.getByRole( 'heading', {
						name: /Want to be notified when this product is back in stock\?/i,
					} )
				).toHaveCount( 0 );
			} );
		} );

		test.describe( 'Logged-in customer, single opt-in', () => {
			test.use( { storageState: CUSTOMER_STATE_PATH } );

			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: false,
					createAccountOnSignup: false,
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

			test( 'submitting the form surfaces a success notice with a link to manage notifications', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );
				await signUpOnProductPage( page );

				await expect(
					page.getByText( bisNotice.success( product.name ) )
				).toBeVisible();

				// Logged-in signups get a "Manage notifications" CTA in front
				// of the notice, pointing at the account endpoint.
				await expect(
					page.getByRole( 'link', { name: 'Manage notifications' } )
				).toHaveAttribute( 'href', /stock-notifications/ );
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
					page.getByText( bisNotice.success( product.name ) )
				).toBeVisible();

				// Submitting the form a second time (the "already joined"
				// notice only renders as a form-submit response; the cached
				// state shown on page reload is behind the off-by-default
				// `woocommerce_customer_stock_notifications_personalization_enabled`
				// filter).
				await page.goto( product.permalink );
				await signUpOnProductPage( page );
				await expect(
					page.getByText( bisNotice.alreadyJoined )
				).toBeVisible();
			} );

			test( 'a missing nonce is rejected once nonce checks are on', async ( {
				page,
				product,
			} ) => {
				// Core only verifies the signup nonce when personalization is
				// on and the shopper is logged in (or an account is required),
				// so guest forms survive HTML caching. Turn personalization on
				// through the test helper's filter cookie to reach that branch.
				await setFilterValue(
					page,
					'woocommerce_customer_stock_notifications_personalization_enabled',
					true
				);

				await page.goto( product.permalink );

				// Blank the nonce the form posts, the way a stale cached page
				// or a forged request would.
				await page
					.locator( 'input[name="wc_bis_nonce"]' )
					.evaluate( ( input: HTMLInputElement ) => {
						input.value = '';
					} );
				await signUpOnProductPage( page );

				await expect(
					page.getByText( bisNotice.errors.failed )
				).toBeVisible();

				// Positive control on the same page: with the nonce intact the
				// signup goes through, so the rejection above was the nonce.
				await page.goto( product.permalink );
				await signUpOnProductPage( page );
				await expect(
					page.getByText( bisNotice.success( product.name ) )
				).toBeVisible();

				await clearFilters( page );
			} );
		} );

		test.describe( 'Guest — single opt-in', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: false,
					createAccountOnSignup: false,
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

				// The consent checkbox only belongs to the account-creation
				// setup, which is off here.
				await expect( bisConsentCheckbox( page ) ).toHaveCount( 0 );
			} );

			test( 'submitting the form surfaces a success notice', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );
				await signUpOnProductPage( page, {
					email: uniqueGuestEmail( 'bis-guest-single' ),
				} );

				await expect(
					page.getByText( bisNotice.success( product.name ) )
				).toBeVisible();
			} );

			test( 'an invalid email address is rejected', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				// The form opts out of browser validation (`novalidate`), so
				// the malformed address reaches the server and it is the
				// server's rejection that renders.
				await signUpOnProductPage( page, { email: 'not-an-email' } );

				await expect(
					page.getByText( bisNotice.errors.invalidEmail )
				).toBeVisible();
			} );

			test( 'a product id that does not exist is rejected', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				await bisTargetProductInput( page ).evaluate(
					( input: HTMLInputElement ) => {
						input.value = '999999999';
					}
				);
				await signUpOnProductPage( page, {
					email: uniqueGuestEmail( 'bis-guest-bad-product' ),
				} );

				await expect(
					page.getByText( bisNotice.errors.invalidProduct )
				).toBeVisible();
			} );
		} );

		test.describe( 'Guest — double opt-in', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: true,
					requireAccount: false,
					createAccountOnSignup: false,
				} );
			} );

			test( 'submitting signup dispatches a verification email', async ( {
				page,
				product,
				browser,
			} ) => {
				const email = uniqueGuestEmail( 'bis-guest-double' );

				await page.goto( product.permalink );
				await signUpOnProductPage( page, { email } );

				await expect(
					page.getByText( bisNotice.doubleOptIn )
				).toBeVisible();

				// WP Mail Logging is an admin-only screen, so the log is read
				// from a separate admin context.
				await expectEmailAsAdmin(
					browser,
					email,
					bisEmailSubject.verify( product.name )
				);
			} );
		} );

		test.describe( 'Guest — create account on signup', () => {
			test.describe( 'Single opt-in', () => {
				test.beforeAll( async ( { baseURL } ) => {
					await setBISOptions( request, baseURL!, {
						allowSignups: true,
						doubleOptIn: false,
						requireAccount: false,
						createAccountOnSignup: true,
					} );
				} );

				test( 'the consent checkbox renders and the signup is refused until it is ticked', async ( {
					page,
					product,
					restApi,
					browser,
					accountEmail: email,
				} ) => {
					await page.goto( product.permalink );

					const consent = bisConsentCheckbox( page );
					await expect( consent ).toBeVisible();
					await expect( consent ).not.toBeChecked();

					await signUpOnProductPage( page, { email } );

					await expect(
						page.getByText( bisNotice.errors.missingConsent )
					).toBeVisible();

					// The refusal has to happen before anything is written:
					// no account for the address, and no signup either.
					expect(
						await findCustomerByEmail( restApi, email )
					).toBeUndefined();
					await expectNoSignupAsAdmin( browser, product.id, email );
				} );

				test( 'ticking consent signs up, registers an account and sends the welcome email', async ( {
					page,
					product,
					restApi,
					browser,
					accountEmail: email,
				} ) => {
					await page.goto( product.permalink );
					await signUpOnProductPage( page, { email, consent: true } );

					await expect(
						page.getByText(
							bisNotice.accountCreated( product.name )
						)
					).toBeVisible();

					expect(
						await findCustomerByEmail( restApi, email )
					).toBeDefined();

					// The "check your e-mail for details" in the notice is
					// WooCommerce's own new-account email, sent with the
					// generated password.
					await expectEmailAsAdmin(
						browser,
						email,
						/account has been created!/
					);
				} );
			} );

			test.describe( 'Double opt-in', () => {
				test.beforeAll( async ( { baseURL } ) => {
					await setBISOptions( request, baseURL!, {
						allowSignups: true,
						doubleOptIn: true,
						requireAccount: false,
						createAccountOnSignup: true,
					} );
				} );

				test( 'ticking consent registers an account and still asks for email verification', async ( {
					page,
					product,
					restApi,
					browser,
					accountEmail: email,
				} ) => {
					await page.goto( product.permalink );
					await signUpOnProductPage( page, { email, consent: true } );

					await expect(
						page.getByText( bisNotice.accountCreatedDoubleOptIn )
					).toBeVisible();

					expect(
						await findCustomerByEmail( restApi, email )
					).toBeDefined();

					await expectEmailAsAdmin(
						browser,
						email,
						bisEmailSubject.verify( product.name )
					);
				} );
			} );
		} );

		test.describe( 'Guest — requires account', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: true,
					createAccountOnSignup: false,
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
				await expect(
					page.getByRole( 'button', { name: /Notify me/i } )
				).toHaveCount( 0 );

				// Pair the absence with the prompt core renders in its place,
				// so a 404 or a failed render can't pass as account gating.
				await expect(
					page.getByText( bisNotice.accountRequired )
				).toBeVisible();
			} );

			test( 'logging in from the prompt lets the customer sign up', async ( {
				page,
				product,
			} ) => {
				await page.goto( product.permalink );

				await page.getByRole( 'link', { name: 'log in' } ).click();

				await page.locator( '#username' ).fill( customer.username );
				await page.locator( '#password' ).fill( customer.password );
				await page
					.getByRole( 'button', { name: 'Log in', exact: true } )
					.click();

				// Login lands on the account dashboard, so go back to the
				// product to find the form now rendered for the customer.
				await page.goto( product.permalink );
				await expect(
					page.getByText( bisNotice.accountRequired )
				).toHaveCount( 0 );

				await signUpOnProductPage( page );

				await expect(
					page.getByText( bisNotice.success( product.name ) )
				).toBeVisible();
				await expect(
					page.getByRole( 'link', { name: 'Manage notifications' } )
				).toBeVisible();
			} );
		} );
	}
);
