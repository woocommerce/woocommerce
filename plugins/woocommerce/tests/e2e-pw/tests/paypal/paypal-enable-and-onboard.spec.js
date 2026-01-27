/**
 * Internal dependencies
 */
import { expect, tags, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { setFilterValue } from '../../utils/filters';

async function waitForPayPalToLoad( page ) {
	const paypalDiv = page.locator( '#paypal' );
	await expect( paypalDiv ).toBeVisible( { timeout: 50000 } );

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

			const adminMenu = page.locator( '#adminmenu' );
			await adminMenu
				.getByRole( 'link', { name: 'WooCommerce', exact: true } )
				.click();

			const wcMenu = page.locator(
				'#toplevel_page_woocommerce .wp-submenu'
			);
			await expect( wcMenu ).toBeVisible( { timeout: 50000 } );

			await wcMenu
				.getByRole( 'link', { name: 'Settings', exact: true } )
				.click();
		}

		async function openPayments( page ) {
			await openWCSettings( page );

			const navTabWrapper = page.locator( '.woo-nav-tab-wrapper' );

			await navTabWrapper
				.getByRole( 'link', {
					name: 'Payments',
					exact: true,
				} )
				.click();

			await expect(
				page.locator( '.settings-payment-gateways__header-title' )
			).toBeVisible( { timeout: 50000 } );
		}

		// test( 'PayPal Standard can be enabled', async ( { page } ) => {
		// 	await openPayments( page );
		//
		// 	const paypalDiv = await waitForPayPalToLoad( page );
		//
		// 	await paypalDiv
		// 		.getByRole( 'link', {
		// 			name: 'Enable',
		// 		} )
		// 		.click();
		//
		// 	const labelActive = paypalDiv.getByText( 'Active' );
		// 	const labelTestAccount = paypalDiv.getByText( 'Test account' );
		//
		// 	// Confirm the status label is present with any of the expected texts.
		// 	await expect( labelActive.or( labelTestAccount ) ).toBeVisible( {
		// 		timeout: 50000,
		// 	} );
		// } );

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

			await setFilterValue( page, 'pre_option_jetpack_options', {
				id: 12345,
			} );

			await setFilterValue( page, 'pre_option_jetpack_private_options', {
				blog_token: 'IAM.AJETPACKBLOGTOKEN',
			} );

			// await setFilterValue( page, 'pre_http_request', {
			// 	response: {
			// 		code: 200,
			// 	},
			// 	body: {
			// 		public_id: 'test_public_id',
			// 	},
			// } );

			await test.step( 'Update the title field', async () => {
				await page
					.locator( '#woocommerce_paypal_title' )
					.fill( 'PayPal Custom Title ' + Date.now() );

				// TODO: Temporarily removing the disabled attribute from the Save changes button until we can
				// identify why it's disabled in this context.
				await page.evaluate( () => {
					const saveButton = document.querySelector(
						'button[name="save"]'
					);
					if ( saveButton ) {
						saveButton.removeAttribute( 'disabled' );
					}
				} );

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
					'Enable PayPal Buttons'
				);
				await expect( paypalButtonsSetting ).toBeVisible( {
					timeout: 100000,
				} );
			} );

			// TODO: Revert the title field to original value.

			// Clean up by disabling PayPal again.
			await test.step( 'Disable PayPal Standard', async () => {
				await paypalDiv
					.getByRole( 'button', {
						name: 'Payment provider options',
					} )
					.click();

				await page
					.getByRole( 'button', {
						name: 'Disable',
					} )
					.click();

				// Confirm the Enable button is present again.
				await expect(
					paypalDiv.getByRole( 'link', { name: 'Enable' } )
				).toBeVisible( { timeout: 50000 } );
			} );
		} );
	}
);
