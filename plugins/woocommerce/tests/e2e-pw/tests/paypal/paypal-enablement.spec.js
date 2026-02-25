/**
 * Internal dependencies
 */
import { expect, tags } from '../../fixtures/fixtures';
import { test } from '../../fixtures/paypal-fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe(
	'PayPal Standard Enablement',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		const visibilityOptions = { timeout: 30000 };

		async function openWCSettings( page ) {
			await page.goto( '/wp-admin/index.php', {
				// networkidle is needed to ensure all JS files are loaded and avoid race conditions
				// eslint-disable-next-line playwright/no-networkidle
				waitUntil: 'networkidle',
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
			).toBeVisible( visibilityOptions );
		}

		async function waitForPayPalToLoad( page ) {
			const paypalDiv = page.locator( '#paypal' );
			await expect( paypalDiv ).toBeVisible( visibilityOptions );

			return paypalDiv;
		}

		test( 'PayPal Standard can be enabled', async ( { page } ) => {
			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			await test.step( 'Enable PayPal Standard', async () => {
				const enableLink = paypalDiv.getByRole( 'link', {
					name: 'Enable',
				} );
				await expect( enableLink ).toBeVisible( visibilityOptions );
				await enableLink.click();
			} );

			const labelActive = paypalDiv.getByText( 'Active' );
			const labelTestAccount = paypalDiv.getByText( 'Test account' );

			await expect( labelActive.or( labelTestAccount ) ).toBeVisible(
				visibilityOptions
			);

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
				).toBeVisible( visibilityOptions );
			} );
		} );
	}
);
