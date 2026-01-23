/**
 * Internal dependencies
 */
import { expect, tags, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe(
	'PayPal Standard Enablement',
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
			await expect( wcMenu ).toBeVisible();

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
			).toBeVisible();
		}

		async function waitForPayPalToLoad( page ) {
			const paypalDiv = page.locator( '#paypal' );
			await expect( paypalDiv ).toBeVisible();

			return paypalDiv;
		}

		test( 'PayPal Standard can be enabled', async ( { page } ) => {
			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			await test.step( 'Enable PayPal Standard', async () => {
				// Confirm the Enable button is present.
				const enableButton = paypalDiv.getByRole( 'link', {
					name: 'Enable',
				} );
				await expect( enableButton ).toBeVisible();

				// Click the Enable button.
				await enableButton.click();
			} );

			const labelActive = paypalDiv.getByText( 'Active' );
			const labelTestAccount = paypalDiv.getByText( 'Test account' );

			// Confirm the status label is present with any of the expected texts.
			await expect( labelActive.or( labelTestAccount ) ).toBeVisible();

			// Clean up by disabling PayPal again.
			await test.step( 'Disable PayPal Standard', async () => {
				const optionsButton = paypalDiv.getByRole( 'button', {
					name: 'Payment provider options',
				} );
				await expect( optionsButton ).toBeVisible();

				await optionsButton.click();

				const disableButton = page.getByRole( 'button', {
					name: 'Disable',
				} );
				await expect( disableButton ).toBeVisible();

				await disableButton.click();

				// Confirm the Enable button is present again.
				await expect(
					paypalDiv.getByRole( 'link', { name: 'Enable' } )
				).toBeVisible();
			} );
		} );
	}
);
