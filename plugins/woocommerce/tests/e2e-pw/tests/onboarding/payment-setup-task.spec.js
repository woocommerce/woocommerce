/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import {
	WC_API_PATH,
	WC_ADMIN_API_PATH,
	WP_API_PATH,
} from '../../utils/api-client';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	page: async ( { restApi, page }, use ) => {
		// Disable the help popover.
		await restApi.post( `${ WP_API_PATH }/users/1?_locale=user`, {
			data: {
				woocommerce_meta: {
					help_panel_highlight_shown: '"yes"',
				},
			},
		} );

		// Ensure store's base country location is a WooPayments non-supported country (AF).
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

		// Ensure the task list is not hidden.
		// Otherwise, the direct url page=wc-admin&task=payments will not work
		const initialTaskListHiddenState = await restApi.get(
			`${ WC_ADMIN_API_PATH }/options?options=woocommerce_task_list_hidden`
		);
		await restApi.put( `${ WC_ADMIN_API_PATH }/options`, {
			woocommerce_task_list_hidden: 'no',
		} );

		const bacsInitialState = await restApi.get(
			`${ WC_API_PATH }/payment_gateways/bacs`
		);
		const codInitialState = await restApi.get(
			`${ WC_API_PATH }/payment_gateways/cod`
		);

		await use( page );

		// Reset the payment gateways to their initial state.
		await restApi.put( `${ WC_API_PATH }/payment_gateways/bacs`, {
			enabled: bacsInitialState.data.enabled,
		} );
		await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
			enabled: codInitialState.data.enabled,
		} );
		await restApi.put(
			`${ WC_API_PATH }/settings/general/woocommerce_default_country`,
			{
				value: initialDefaultCountry.data.value,
			}
		);
		await restApi.put(
			`${ WC_ADMIN_API_PATH }/options`,
			initialTaskListHiddenState.data
		);
	},
} );

test.describe( 'Payment setup task', () => {
	test(
		'Saving valid bank account transfer details enables the payment method',
		{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
		async ( { page, restApi } ) => {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/bacs`, {
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
		async ( { page, restApi } ) => {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
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
