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

		async function waitForPayPalToLoad( page ) {
			const paypalDiv = page.locator( '#paypal' );
			await expect( paypalDiv ).toBeVisible();

			return paypalDiv;
		}

		test( 'PayPal Standard can be enabled', async ( { page } ) => {
			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			await test.step( 'Enable PayPal Standard', async () => {
				await paypalDiv
					.getByRole( 'link', {
						name: 'Enable',
					} )
					.click();
			} );

			const labelActive = paypalDiv.getByText( 'Active' );
			const labelTestAccount = paypalDiv.getByText( 'Test account' );

			// Confirm the status label is present with any of the expected texts.
			await expect( labelActive.or( labelTestAccount ) ).toBeVisible();

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
				).toBeVisible();
			} );
		} );
	}
);
