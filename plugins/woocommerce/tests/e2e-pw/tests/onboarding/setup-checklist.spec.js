/**
 * External dependencies
 */
import {
	WC_ADMIN_API_PATH,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,

	page: async ( { page, restApi }, use ) => {
		const initialTaskListState = await restApi.get(
			`${ WC_ADMIN_API_PATH }/options?options=woocommerce_task_list_hidden`
		);

		// Ensure task list is visible.
		await restApi.put( `${ WC_ADMIN_API_PATH }/options`, {
			woocommerce_task_list_hidden: 'no',
		} );

		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		await use( page );

		// Reset the task list to its initial state.
		await restApi.put(
			`${ WC_ADMIN_API_PATH }/options`,
			initialTaskListState.data
		);
	},

	nonSupportedWooPaymentsCountryPage: async ( { page, restApi }, use ) => {
		// Ensure store's base country location is a WooPayments non-supported country (e.g. AF).
		// Otherwise, the WooPayments task page logic or WooPayments redirects will kick in.
		const initialDefaultCountry = await restApi.get(
			`${ WC_API_PATH }/settings/general/woocommerce_default_country`
		);
		await restApi.put(
			`${ WC_API_PATH }/settings/general/woocommerce_default_country`,
			{
				value: 'AF',
			}
		);

		await use( page );

		// Reset the default country to its initial state.
		await restApi.put(
			`${ WC_API_PATH }/settings/general/woocommerce_default_country`,
			{
				value: initialDefaultCountry.data.value,
			}
		);
	},
} );

test(
	'Can hide the task list',
	{ tag: [ tags.NOT_E2E ] },
	async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );
		await test.step( 'Load the WC Admin page.', async () => {
			await expect(
				page.getByRole( 'button', { name: 'Customize your store' } )
			).toBeVisible();
			await expect( page.getByText( 'Store management' ) ).toBeHidden();
		} );

		await test.step( 'Hide the task list', async () => {
			await page
				.getByRole( 'button', { name: 'Task List Options' } )
				.first()
				.click();
			await page
				.getByRole( 'button', { name: 'Hide setup list' } )
				.click();
			await expect(
				page.getByRole( 'heading', {
					name: 'Customize your store',
				} )
			).toBeHidden();
			await expect( page.getByText( 'Store management' ) ).toBeVisible();
		} );
	}
);

test(
	'Payments task list item links to Payments settings page',
	{ tag: [ tags.NOT_E2E ] },
	/**
	 * @param {{ nonSupportedWooPaymentsCountryPage: import('@playwright/test').Page }} page
	 */
	async ( { nonSupportedWooPaymentsCountryPage } ) => {
		await nonSupportedWooPaymentsCountryPage.goto(
			'wp-admin/admin.php?page=wc-admin'
		);
		await nonSupportedWooPaymentsCountryPage
			.locator( '.woocommerce-task-list__item' )
			.filter( { hasText: 'Set up payments' } )
			.click();

		await expect(
			nonSupportedWooPaymentsCountryPage.locator(
				'.woocommerce-layout__header-wrapper > h1'
			)
		).toHaveText( 'Settings' );
	}
);

test( 'Can connect to WooCommerce.com', async ( { page } ) => {
	await page.goto( 'wp-admin/admin.php?page=wc-admin' );
	await test.step( 'Go to WC Home and make sure the total sales is visible', async () => {
		await page
			.getByRole( 'menuitem', { name: 'Total sales' } )
			.waitFor( { state: 'visible', timeout: 30000 } );
	} );

	await test.step( 'Go to the extensions tab and connect store', async () => {
		const connectButton = page.getByRole( 'link', {
			name: 'Connect',
		} );

		// Set up response waiter BEFORE navigation to avoid race condition
		const waitForSubscriptionsResponse = page.waitForResponse(
			( response ) =>
				response
					.url()
					.includes( '/wp-json/wc/v3/marketplace/subscriptions' ) &&
				response.status() === 200
		);

		await page.goto(
			'wp-admin/admin.php?page=wc-admin&tab=my-subscriptions&path=%2Fextensions'
		);

		await expect(
			page.getByText(
				'Hundreds of vetted products and services. Unlimited potential.'
			)
		).toBeVisible( { timeout: 30000 } );
		await expect(
			page.getByRole( 'button', { name: 'My Subscriptions' } )
		).toBeVisible();
		await expect( connectButton ).toBeVisible();

		// Wait for the API response before checking button attributes
		await waitForSubscriptionsResponse;

		await expect( connectButton ).toHaveAttribute(
			'href',
			/my-subscriptions/
		);
		await connectButton.click();
	} );

	await test.step( 'Check that we are sent to wp.com', async () => {
		// Use polling assertion for URL check since page.url() is not auto-retrying
		await expect
			.poll( () => page.url(), { timeout: 30000 } )
			.toContain( 'wordpress.com/log-in' );
		await expect(
			page.getByRole( 'heading', {
				name: 'Log in to Woo with WordPress.com',
			} )
		).toBeVisible( { timeout: 30000 } );
	} );
} );
