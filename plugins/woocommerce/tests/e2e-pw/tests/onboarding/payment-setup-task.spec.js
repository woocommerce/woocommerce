const { test: baseTest, expect, tags } = require( '../../fixtures/fixtures' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	page: async ( { api, page, wpApi, wcAdminApi }, use ) => {
		// Disable the help popover.
		await wpApi.post( './wp-json/wp/v2/users/1?_locale=user', {
			data: {
				woocommerce_meta: {
					help_panel_highlight_shown: '"yes"',
				},
			},
		} );

		// Ensure store's base country location is a WooPayments non-supported country (AF).
		// Otherwise, the WooPayments task page logic or WooPayments redirects will kick in.
		const initialDefaultCountry = await api.get(
			'settings/general/woocommerce_default_country'
		);
		await api.put( 'settings/general/woocommerce_default_country', {
			value: 'AF',
		} );

		// Ensure the task list is not hidden.
		// Otherwise, the direct url page=wc-admin&task=payments will not work
		const initialTaskListHiddenState = await wcAdminApi.get(
			'options?options=woocommerce_task_list_hidden'
		);
		await wcAdminApi.put( 'options', {
			woocommerce_task_list_hidden: 'no',
		} );

		const bacsInitialState = await api.get( 'payment_gateways/bacs' );
		const codInitialState = await api.get( 'payment_gateways/cod' );

		await use( page );

		// Reset the payment gateways to their initial state.
		await api.put( 'payment_gateways/bacs', {
			enabled: bacsInitialState.data.enabled,
		} );
		await api.put( 'payment_gateways/cod', {
			enabled: codInitialState.data.enabled,
		} );
		await api.put( 'settings/general/woocommerce_default_country', {
			value: initialDefaultCountry.data.value,
		} );
		await wcAdminApi.put( 'options', initialTaskListHiddenState.data );
	},
} );

test.describe( 'Payment setup task', () => {
	test(
		'Saving valid bank account transfer details enables the payment method',
		{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
		async ( { page, api } ) => {
			await api.put( 'payment_gateways/bacs', {
				enabled: false,
			} );

			// Load the bank transfer page.
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&task=payments&id=bacs'
			);

			// Fill in bank transfer form.
			await page
				.locator( '//input[@placeholder="Account name"]' )
				.fill( 'Savings' );
			await page
				.locator( '//input[@placeholder="Account number"]' )
				.fill( '1234' );
			await page
				.locator( '//input[@placeholder="Bank name"]' )
				.fill( 'Test Bank' );
			await page
				.locator( '//input[@placeholder="Sort code"]' )
				.fill( '12' );
			await page
				.locator( '//input[@placeholder="IBAN"]' )
				.fill( '12 3456 7890' );
			await page
				.locator( '//input[@placeholder="BIC / Swift"]' )
				.fill( 'ABBA' );
			await page.getByRole( 'button', { name: 'Save' } ).click();

			// Check that bank transfers were set up.
			await expect(
				page.locator( 'div.components-snackbar__content' )
			).toContainText(
				'Direct bank transfer details added successfully'
			);

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=checkout'
			);

			await expect(
				page.locator(
					'//tr[@data-gateway_id="bacs"]/td[@class="status"]/a'
				)
			).toHaveClass( 'wc-payment-gateway-method-toggle-enabled' );
		}
	);

	test(
		'Enabling cash on delivery enables the payment method',
		{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
		async ( { page, api } ) => {
			await api.put( 'payment_gateways/cod', {
				enabled: false,
			} );

			const paymentGatewaysResponse = page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes( 'wp-json/wc/v3/payment_gateways' ) &&
					response.ok()
			);
			await page.goto( 'wp-admin/admin.php?page=wc-admin&task=payments' );
			await paymentGatewaysResponse;

			// Enable COD payment option.
			await page
				.locator( 'div.woocommerce-task-payment-cod' )
				.getByRole( 'button', { name: 'Enable' } )
				.click();
			// Check that COD was set up.
			await expect(
				page
					.locator( 'div.woocommerce-task-payment-cod' )
					.getByRole( 'button', { name: 'Manage' } )
			).toBeVisible();

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=checkout'
			);

			// Check that the COD payment method was enabled.
			await expect(
				page.locator(
					'//tr[@data-gateway_id="cod"]/td[@class="status"]/a'
				)
			).toHaveClass( 'wc-payment-gateway-method-toggle-enabled' );
		}
	);
} );
