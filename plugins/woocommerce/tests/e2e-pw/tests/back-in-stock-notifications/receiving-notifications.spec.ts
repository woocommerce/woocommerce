/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

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
	getLinkFromEmailBody,
	restockProduct,
	setBISOptions,
	signUpOnProductPage,
	triggerStockNotificationsBatch,
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

async function signUpAsGuest(
	page: Page,
	permalink: string,
	email: string
): Promise< void > {
	const guestContext = await page.context().browser()!.newContext( {
		storageState: { cookies: [], origins: [] },
	} );
	const guestPage = await guestContext.newPage();
	await guestPage.goto( permalink );
	await signUpOnProductPage( guestPage, { email } );
	await guestContext.close();
}

test.describe(
	'Back in Stock Notifications — receiving back-in-stock emails',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeEach( async ( { baseURL } ) => {
			// Single opt-in so the notification becomes ACTIVE immediately
			// (no verify step), which is what the back-in-stock dispatch needs.
			await setBISOptions( request, baseURL!, {
				allowSignups: true,
				doubleOptIn: false,
				requireAccount: false,
			} );
		} );

		test.afterEach( async ( { baseURL } ) => {
			for ( const option of Object.values( BIS_OPTIONS ) ) {
				await deleteOption( request, baseURL!, option );
			}
		} );

		test( 'restocking a product dispatches the back-in-stock email with UTM params', async ( {
			page,
			product,
			restApi,
		} ) => {
			const email = uniqueGuestEmail( 'bis-restock' );

			await signUpAsGuest( page, product.permalink, email );

			await restockProduct( restApi, product.id );

			// StockSyncController schedules an AS job; drain it synchronously.
			await triggerStockNotificationsBatch( page );

			const emailRow = await expectEmail(
				page,
				email,
				/is back in stock!/
			);
			await emailRow.getByRole( 'button', { name: 'View log' } ).click();

			const productLink = await getLinkFromEmailBody(
				page,
				email,
				/is back in stock!/,
				/utm_source=back-in-stock-notifications/
			);

			expect( productLink ).toMatch(
				/utm_source=back-in-stock-notifications/
			);
			expect( productLink ).toMatch( /utm_medium=email/ );
		} );

		test( 'unsubscribe link in the back-in-stock email cancels the notification', async ( {
			page,
			product,
			restApi,
		} ) => {
			const email = uniqueGuestEmail( 'bis-restock-unsub' );

			await signUpAsGuest( page, product.permalink, email );

			await restockProduct( restApi, product.id );
			await triggerStockNotificationsBatch( page );

			await expectEmail( page, email, /is back in stock!/ );

			const unsubscribeLink = await getLinkFromEmailBody(
				page,
				email,
				/is back in stock!/,
				/email_link_action=unsubscribe/
			);
			await page.goto( unsubscribeLink );

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
