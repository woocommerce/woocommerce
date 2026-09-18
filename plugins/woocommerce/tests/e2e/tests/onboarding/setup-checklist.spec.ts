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

// The wp-admin request that starts the WooCommerce.com OAuth hand-off.
const isConnectRequest = ( url: URL ) =>
	url.searchParams.get( 'wc-helper-connect' ) === '1';

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
			const setupTaskProgressHeader = page.locator(
				'.woocommerce-task-progress-header'
			);

			await setupTaskProgressHeader
				.getByRole( 'button', { name: 'Task list options' } )
				.click();
			await page.getByText( 'Hide setup list', { exact: true } ).click();
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

test(
	'Can connect to WooCommerce.com',
	{ tag: [ tags.SERVICES ] },
	async ( { page, baseURL } ) => {
		// Clicking Connect asks WooCommerce to start the OAuth hand-off: it checks a nonce, trades a
		// token with WooCommerce.com server-side, then redirects the browser on to WooCommerce.com,
		// which bounces it to WordPress.com to log in. Building that redirect is WooCommerce's job;
		// what WordPress.com renders is not. Run the real request, but stop at the redirect, so the
		// test doesn't depend on a third-party page that bot-challenges CI runners.
		let authorizeUrl: string | undefined;
		await page.route( isConnectRequest, async ( route ) => {
			// Let the real request run — nonce check, server-side token exchange with
			// WooCommerce.com and all — but read the redirect instead of following it.
			const response = await route.fetch( { maxRedirects: 0 } );
			authorizeUrl = response.headers().location;
			// 204 leaves the browser where it is rather than sending it onward.
			await route.fulfill( { status: 204, body: '' } );
		} );

		await page.goto( 'wp-admin/admin.php?page=wc-admin' );
		await test.step( 'Go to WC Home and make sure the total sales is visible', async () => {
			await page
				.getByRole( 'menuitem', { name: 'Total sales' } )
				.waitFor( { state: 'visible', timeout: 30000 } );
		} );

		await test.step( 'Go to the extensions tab and connect store', async () => {
			const connectButton = page.getByRole( 'link', {
				name: 'Connect',
				exact: true,
			} );

			// Set up response waiter BEFORE navigation to avoid race condition
			const waitForSubscriptionsResponse = page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes(
							'/wp-json/wc/v3/marketplace/subscriptions'
						) && response.status() === 200
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

		await test.step( 'Check we hand off to WooCommerce.com with a valid OAuth request', async () => {
			await expect
				.poll( () => authorizeUrl ?? '', { timeout: 30000 } )
				.toContain( '/oauth/authorize' );

			const params = new URL( authorizeUrl ?? '' ).searchParams;

			// `secret` is minted by WooCommerce.com in reply to the server-side
			// `oauth/request_token` call, so a non-empty value is proof that the
			// handshake actually ran.
			expect( params.get( 'secret' ) ).toBeTruthy();

			// The store identifies itself, and says where to come back to afterwards.
			expect( new URL( params.get( 'home_url' ) ?? '' ).origin ).toBe(
				new URL( baseURL ?? '' ).origin
			);

			const redirectUri = new URL( params.get( 'redirect_uri' ) ?? '' );
			expect( redirectUri.pathname ).toContain( 'admin.php' );
			expect( redirectUri.searchParams.get( 'wc-helper-return' ) ).toBe(
				'1'
			);
			expect(
				redirectUri.searchParams.get( 'wc-helper-nonce' )
			).toBeTruthy();

			// Where the merchant lands in wp-admin once the connection completes.
			expect( params.get( 'redirect_admin_url' ) ).toContain(
				'page=wc-admin'
			);

			expect( [ '0', '1' ] ).toContain( params.get( 'wum-installed' ) );
		} );
	}
);
