/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
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

test.describe(
	'Back in Stock Notifications — admin management',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeEach( async ( { baseURL, request } ) => {
			await setBISOptions( request, baseURL!, {
				allowSignups: true,
				doubleOptIn: true,
				requireAccount: false,
			} );
		} );

		test.afterEach( async ( { baseURL, request } ) => {
			for ( const option of Object.values( BIS_OPTIONS ) ) {
				await deleteOption( request, baseURL!, option );
			}
		} );

		test( 'notifications list renders a signup row filtered by product', async ( {
			page,
			product,
		} ) => {
			const email = uniqueGuestEmail( 'bis-admin-list' );

			// Sign up as a guest (uses a fresh context; storageState on this spec
			// is admin, so we explicitly open a guest context for the signup).
			const guestContext = await page.context().browser()!.newContext();
			const guestPage = await guestContext.newPage();
			await guestPage.goto( product.permalink );
			await signUpOnProductPage( guestPage, { email } );
			await guestContext.close();

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
		} ) => {
			const email = uniqueGuestEmail( 'bis-admin-resend-pending' );

			const guestContext = await page.context().browser()!.newContext();
			const guestPage = await guestContext.newPage();
			await guestPage.goto( product.permalink );
			await signUpOnProductPage( guestPage, { email } );
			await guestContext.close();

			// Wait for the initial verify email so we can count a new one later.
			await expectEmail(
				page,
				email,
				/Join the "[^"]+" waitlist\./
			);

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
			request,
			baseURL,
		} ) => {
			// Flip to single opt-in so the signup goes straight to ACTIVE.
			await setBISOptions( request, baseURL!, { doubleOptIn: false } );

			const email = uniqueGuestEmail( 'bis-admin-active' );

			const guestContext = await page.context().browser()!.newContext();
			const guestPage = await guestContext.newPage();
			await guestPage.goto( product.permalink );
			await signUpOnProductPage( guestPage, { email } );
			await guestContext.close();

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

		test( 'admin Cancel action marks a pending notification as Cancelled', async ( {
			page,
			product,
		} ) => {
			const email = uniqueGuestEmail( 'bis-admin-cancel' );

			const guestContext = await page.context().browser()!.newContext();
			const guestPage = await guestContext.newPage();
			await guestPage.goto( product.permalink );
			await signUpOnProductPage( guestPage, { email } );
			await guestContext.close();

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
