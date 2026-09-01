/**
 * Internal dependencies
 */
import { expect, request, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	BIS_EMAIL_ELEMENTS,
	BIS_EMAIL_LINKS,
	bisEmailBody,
	bisEmailSubject,
	bisFormLocator,
	bisTargetProductInput,
	escapeRegExp,
	getEmailLinkById,
	openEmailInMailLog,
	resetBISOptions,
	restockVariation,
	selectVariation,
	setBISOptions,
	setProductSignupsAllowed,
	signUpAsGuest,
	signUpOnProductPage,
	test,
	triggerStockNotificationsBatch,
	uniqueGuestEmail,
} from '../../utils/back-in-stock-notifications';

test.describe(
	'Back in Stock Notifications — variable products and variations',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.afterAll( async ( { baseURL } ) => {
			await resetBISOptions( request, baseURL! );
		} );

		test.describe( 'Single opt-in', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
					requireAccount: false,
				} );
			} );

			test( 'the form follows the selected variation and targets it', async ( {
				page,
				variableProduct,
			} ) => {
				await page.goto( variableProduct.permalink );

				const form = bisFormLocator( page );
				const targetProduct = bisTargetProductInput( page );

				// Before a variation is picked the form is rendered but
				// hidden, and still points at the parent product.
				//
				// Asserted on the `hidden` class rather than on visibility:
				// that class is the contract `back-in-stock-form.js` drives,
				// while whether it actually hides the form depends on the
				// theme's stylesheet.
				await expect( form ).toContainClass( 'hidden' );
				await expect( targetProduct ).toHaveValue(
					String( variableProduct.id )
				);

				await selectVariation(
					page,
					variableProduct,
					variableProduct.outOfStockVariation
				);

				await expect( form ).not.toContainClass( 'hidden' );
				await expect(
					form.getByRole( 'button', { name: /Notify me/i } )
				).toBeVisible();
				await expect( targetProduct ).toHaveValue(
					String( variableProduct.outOfStockVariation.id )
				);

				// Switching to an in-stock variation hides it again. Asserted
				// as a transition from the shown state above, so a form that
				// was never shown at all cannot pass this.
				await selectVariation(
					page,
					variableProduct,
					variableProduct.inStockVariation!
				);

				await expect( form ).toContainClass( 'hidden' );
			} );

			test( 'signing up for an out-of-stock variation confirms the variation by name', async ( {
				browser,
				variableProduct,
			} ) => {
				const email = uniqueGuestEmail( 'bis-variation-signup' );
				const { outOfStockVariation } = variableProduct;

				// Driven in a guest context so the PDP renders the email field,
				// and so the success notice can be read before it is torn down.
				const guestContext = await browser.newContext( {
					storageState: { cookies: [], origins: [] },
				} );
				const guestPage = await guestContext.newPage();

				await guestPage.goto( variableProduct.permalink );
				await selectVariation(
					guestPage,
					variableProduct,
					outOfStockVariation
				);
				await signUpOnProductPage( guestPage, { email } );

				// The notice names the variation rather than the parent
				// product, which is what shows the signup was recorded against
				// the variation the shopper picked.
				// `wptexturize` turns the hyphen WooCommerce generated the
				// variation title with into an en dash by the time the notice
				// is printed, so match either.
				await expect(
					guestPage.getByText(
						new RegExp(
							`You have successfully signed up! You will be notified when "${ escapeRegExp(
								variableProduct.name
							) } [-–] ${
								outOfStockVariation.option
							}" is back in stock\\.`
						)
					)
				).toBeVisible();

				await guestContext.close();
			} );

			test( 'restocking a variation notifies its subscribers and links back to it', async ( {
				page,
				browser,
				restApi,
				variableProduct,
			} ) => {
				const email = uniqueGuestEmail( 'bis-variation-restock' );
				const { outOfStockVariation } = variableProduct;

				await signUpAsGuest(
					browser,
					variableProduct.permalink,
					email,
					{
						selectVariation: {
							product: variableProduct,
							variation: outOfStockVariation,
						},
					}
				);

				await restockVariation(
					restApi,
					variableProduct.id,
					outOfStockVariation.id
				);

				// StockSyncController schedules an AS job; drain it synchronously.
				await triggerStockNotificationsBatch( page );

				const subject = bisEmailSubject.backInStock(
					outOfStockVariation.notificationName
				);

				const productLink = await getEmailLinkById(
					page,
					email,
					subject,
					BIS_EMAIL_LINKS.actionButton
				);

				// The CTA has to land on the parent product page with the
				// variation pre-selected, so the shopper can buy the thing they
				// signed up for instead of picking it out again.
				const linkUrl = new URL( productLink );

				expect(
					linkUrl.searchParams.get( variableProduct.attributeSelect )
				).toBe( outOfStockVariation.option );

				linkUrl.searchParams.delete( 'utm_source' );
				linkUrl.searchParams.delete( 'utm_medium' );
				linkUrl.searchParams.delete( variableProduct.attributeSelect );
				expect( linkUrl.toString() ).toBe(
					new URL( variableProduct.permalink ).toString()
				);

				// Following it has to leave the variation picked, which is what
				// makes the attribute in the URL worth carrying.
				await page.goto( productLink );
				await expect(
					page.locator(
						`.variations select[name="${ variableProduct.attributeSelect }"]`
					)
				).toHaveValue( outOfStockVariation.option );
			} );

			test( 'a parent opted out of signups renders no form on its product page', async ( {
				page,
				restApi,
				variableProduct,
			} ) => {
				// Positive control: the form is there before the opt-out, so a
				// product page that renders no form for an unrelated reason
				// cannot pass the assertion below.
				await page.goto( variableProduct.permalink );
				await expect( bisFormLocator( page ) ).toHaveCount( 1 );

				await setProductSignupsAllowed(
					restApi,
					variableProduct.id,
					false
				);

				// `maybe_render_form()` reads the parent from `global $product`,
				// so the parent's opt-out meta removes the form from the whole
				// variable product page — there is nothing left to show for
				// any variation.
				await page.goto( variableProduct.permalink );
				await expect( bisFormLocator( page ) ).toHaveCount( 0 );
			} );
		} );

		test.describe( 'Double opt-in', () => {
			test.beforeAll( async ( { baseURL } ) => {
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: true,
					requireAccount: false,
				} );
			} );

			test( 'the verification email names the variation and lists its attributes', async ( {
				page,
				browser,
				variableProduct,
			} ) => {
				const email = uniqueGuestEmail( 'bis-variation-verify' );
				const { outOfStockVariation } = variableProduct;

				await signUpAsGuest(
					browser,
					variableProduct.permalink,
					email,
					{
						selectVariation: {
							product: variableProduct,
							variation: outOfStockVariation,
						},
					}
				);

				const subject = bisEmailSubject.verify(
					outOfStockVariation.notificationName
				);

				await openEmailInMailLog( page, email, subject );

				const emailBody = bisEmailBody( page );

				await expect(
					emailBody.locator( BIS_EMAIL_ELEMENTS.productTitle )
				).toHaveText( outOfStockVariation.notificationName );

				// The body carries the variation's own attributes, which a
				// simple product's email has no equivalent of.
				await expect(
					emailBody.locator( BIS_EMAIL_ELEMENTS.productAttributes )
				).toContainText( variableProduct.attributeLabel );
				await expect(
					emailBody.locator( BIS_EMAIL_ELEMENTS.productAttributes )
				).toContainText( outOfStockVariation.option );
			} );

			test( 'an "Any" variation records the attribute value the shopper chose', async ( {
				page,
				browser,
				anyAttributeVariableProduct,
			} ) => {
				const email = uniqueGuestEmail( 'bis-any-variation' );
				const { outOfStockVariation } = anyAttributeVariableProduct;

				await signUpAsGuest(
					browser,
					anyAttributeVariableProduct.permalink,
					email,
					{
						selectVariation: {
							product: anyAttributeVariableProduct,
							variation: outOfStockVariation,
						},
					}
				);

				const subject = bisEmailSubject.verify(
					outOfStockVariation.notificationName
				);

				await openEmailInMailLog( page, email, subject );

				const emailBody = bisEmailBody( page );

				// An "Any Color" variation carries no attribute value of its
				// own, so its title is the bare parent title and the chosen
				// value is only known from what was submitted: core stores it
				// as the notification's `posted_attributes` meta and renders it
				// as the email's attribute list.
				await expect(
					emailBody.locator( BIS_EMAIL_ELEMENTS.productTitle )
				).toHaveText( anyAttributeVariableProduct.name );
				await expect(
					emailBody.locator( BIS_EMAIL_ELEMENTS.productAttributes )
				).toContainText( anyAttributeVariableProduct.attributeLabel );
				await expect(
					emailBody.locator( BIS_EMAIL_ELEMENTS.productAttributes )
				).toContainText( outOfStockVariation.option );
			} );
		} );
	}
);
