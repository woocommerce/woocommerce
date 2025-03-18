const { tags, test: baseTest, expect } = require( '../../fixtures/fixtures' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,

	page: async ( { page, wcAdminApi }, use ) => {
		const initialTaskListState = await wcAdminApi.get(
			'options?options=woocommerce_task_list_hidden'
		);

		// Ensure task list is visible.
		await wcAdminApi.put( 'options', {
			woocommerce_task_list_hidden: 'no',
		} );

		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		await use( page );

		// Reset the task list to its initial state.
		await wcAdminApi.put( 'options', initialTaskListState.data );
	},

	nonSupportedWooPaymentsCountryPage: async ( { page, api }, use ) => {
		// Ensure store's base country location is a WooPayments non-supported country (e.g. AF).
		// Otherwise, the WooPayments task page logic or WooPayments redirects will kick in.
		const initialDefaultCountry = await api.get(
			'settings/general/woocommerce_default_country'
		);
		await api.put( 'settings/general/woocommerce_default_country', {
			value: 'AF',
		} );

		await use( page );

		// Reset the default country to its initial state.
		await api.put( 'settings/general/woocommerce_default_country', {
			value: initialDefaultCountry.data.value,
		} );
	},
} );

test(
	'Can hide the task list',
	{ tag: [ tags.NOT_E2E ] },
	async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );
		await test.step( 'Load the WC Admin page.', async () => {
			await expect(
				page.getByText( 'Customize your store' )
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
					name: 'Start customizing your store',
				} )
			).toBeHidden();
			await expect( page.getByText( 'Store management' ) ).toBeVisible();
		} );
	}
);

test(
	'Can visit the payment setup task from from the task list',
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
			.filter( { hasText: 'Get paid' } )
			.click();

		await expect(
			nonSupportedWooPaymentsCountryPage.locator(
				'.woocommerce-layout__header-wrapper > h1'
			)
		).toHaveText( 'Get paid' );
	}
);

test( 'Can connect to WooCommerce.com', async ( { page } ) => {
	await page.goto( 'wp-admin/admin.php?page=wc-admin' );
	await test.step( 'Go to WC Home and make sure the total sales is visible', async () => {
		await page
			.getByRole( 'menuitem', { name: 'Total sales' } )
			.waitFor( { state: 'visible' } );
	} );

	await test.step( 'Go to the extensions tab and connect store', async () => {
		const connectButton = page.getByRole( 'link', {
			name: 'Connect',
		} );
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&tab=my-subscriptions&path=%2Fextensions'
		);
		const waitForSubscriptionsResponse = page.waitForResponse(
			( response ) =>
				response
					.url()
					.includes( '/wp-json/wc/v3/marketplace/subscriptions' ) &&
				response.status() === 200
		);
		await expect(
			page.getByText(
				'Hundreds of vetted products and services. Unlimited potential.'
			)
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'My Subscriptions' } )
		).toBeVisible();
		await expect( connectButton ).toBeVisible();
		await waitForSubscriptionsResponse;
		await expect( connectButton ).toHaveAttribute(
			'href',
			/my-subscriptions/
		);
		await connectButton.click();
	} );

	await test.step( 'Check that we are sent to wp.com', async () => {
		await expect( page.url() ).toContain( 'wordpress.com/log-in' );
		await expect(
			page.getByRole( 'heading', {
				name: 'Log in to your account',
			} )
		).toBeVisible( { timeout: 30000 } );
	} );
} );
