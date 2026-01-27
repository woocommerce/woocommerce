/**
 * Internal dependencies
 */
import { expect, tags, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { setFilterValue } from '../../utils/filters';

async function waitForPayPalToLoad( page ) {
	const paypalDiv = page.locator( '#paypal' );
	await expect( paypalDiv ).toBeVisible();

	return paypalDiv;
}

test.describe(
	'PayPal Standard Enablement and Jetpack Onboarding',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		async function openWCSettings( page ) {
			await page.goto( '/wp-admin/index.php', {
				waitUntil: 'networkidle0',
			} );

			await page
				.locator( '#adminmenu' )
				.getByRole( 'link', { name: 'WooCommerce', exact: true } )
				.click();

			const wcMenu = page.locator(
				'#toplevel_page_woocommerce .wp-submenu'
			);
			await expect( wcMenu ).toBeVisible();

			await wcMenu
				.getByRole( 'link', { name: 'Settings', exact: true } )
				.click();
		}

		async function openPayments( page ) {
			await openWCSettings( page );

			await page
				.locator( '.woo-nav-tab-wrapper' )
				.getByRole( 'link', {
					name: 'Payments',
					exact: true,
				} )
				.click();

			await expect(
				page.locator( '.settings-payment-gateways__header-title' )
			).toBeVisible();
		}

		async function enableSaveButton( page ) {
			await page.evaluate( () => {
				const saveButton = document.querySelector(
					'button[name="save"]'
				);
				if ( saveButton ) {
					saveButton.removeAttribute( 'disabled' );
				}
			} );
		}

		test( 'PayPal Standard can be enabled', async ( { page } ) => {
			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			await paypalDiv
				.getByRole( 'link', {
					name: 'Enable',
				} )
				.click();

			const labelActive = paypalDiv.getByText( 'Active' );
			const labelTestAccount = paypalDiv.getByText( 'Test account' );

			// Confirm the status label is present with any of the expected texts.
			await expect( labelActive.or( labelTestAccount ) ).toBeVisible();
		} );

		test( 'PayPal Standard onboards to Jetpack upon changing any setting', async ( {
			page,
		} ) => {
			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			await paypalDiv
				.getByRole( 'button', {
					name: 'Manage',
				} )
				.click();

			// Set up filters to simulate a completed Jetpack onboarding.
			await setFilterValue( page, 'pre_option_jetpack_options', {
				id: 12345,
			} );

			// Simulate a connected Jetpack site with a blog token.
			await setFilterValue( page, 'pre_option_jetpack_private_options', {
				blog_token: 'IAM.AJETPACKBLOGTOKEN',
			} );

			// Mock the response for the PayPal onboarding API request (merchant account data).
			await setFilterValue( page, 'pre_http_request', {
				response: {
					code: 200,
				},
				body: {
					public_id: 'test_public_id',
				},
			} );

			const originalPayPalTitle = await page
				.locator( '#woocommerce_paypal_title' )
				.inputValue();

			await test.step( 'Update the title field', async () => {
				await page
					.locator( '#woocommerce_paypal_title' )
					.fill( 'PayPal Custom Title ' + Date.now() );

				// TODO: Temporarily removing the disabled attribute from the Save changes button.
				await enableSaveButton( page );

				await page
					.getByRole( 'button', {
						name: 'Save changes',
					} )
					.click();

				await expect(
					page.locator( 'div.updated.inline' )
				).toContainText( 'Your settings have been saved.' );
			} );

			await test.step( 'Check the setting present only when Jetpack onboarding is complete', async () => {
				const paypalButtonsSetting = page.getByText(
					'Enable PayPal Buttons',
					{ exact: true }
				);
				await expect( paypalButtonsSetting ).toBeVisible();
			} );

			// Clean up by reverting the title change and disabling PayPal Standard.
			await test.step( 'Revert title change and disable PayPal Standard', async () => {
				await page
					.locator( '#woocommerce_paypal_title' )
					.fill( originalPayPalTitle );

				await page
					.getByRole( 'checkbox', {
						name: 'Enable PayPal Standard',
					} )
					.uncheck();

				// TODO: Temporarily removing the disabled attribute from the Save changes button.
				await enableSaveButton( page );

				await page
					.getByRole( 'button', {
						name: 'Save changes',
					} )
					.click();

				await expect(
					page.locator( 'div.updated.inline' )
				).toContainText( 'Your settings have been saved.' );
			} );
		} );
	}
);
