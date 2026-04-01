/**
 * External dependencies
 */
import { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { expect, tags } from '../../fixtures/fixtures';
import { test } from '../../fixtures/paypal-fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { setFilterValue } from '../../utils/filters';

test.describe(
	'PayPal Standard enablement and Jetpack onboarding',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		const visibilityOptions = { timeout: 30000 };

		/**
		 * Navigates to the WooCommerce settings page by clicking on the WooCommerce link in the WordPress admin menu.
		 *
		 * @param {Page} page The Playwright Page object representing the browser page to interact with.
		 */
		async function openWCSettings( page: Page ) {
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

		/**
		 * Navigates to the Payments settings page by first opening the WooCommerce settings and then clicking on the Payments tab.
		 *
		 * @param {Page} page The Playwright Page object representing the browser page to interact with.
		 */
		async function openPayments( page: Page ) {
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

		/**
		 * Waits for the PayPal Standard section to load on the Payments settings page by checking for the visibility of the PayPal div.
		 *
		 * @param {Page} page The Playwright Page object representing the browser page to interact with.
		 */
		async function waitForPayPalToLoad( page: Page ) {
			const paypalDiv = page.locator( '#paypal' );
			await expect( paypalDiv ).toBeVisible( visibilityOptions );

			return paypalDiv;
		}

		/**
		 * Temporary function to remove the disabled attribute from the Save changes button, as it is currently disabled by default and prevents saving changes in tests.
		 * This should be removed once the underlying issue is resolved and the Save changes button can be enabled as expected.
		 * See: https://github.com/woocommerce/woocommerce/issues/63498
		 *
		 * @param {Page} page The Playwright Page object representing the browser page to interact with.
		 */
		async function enableSaveButton( page: Page ) {
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
		} );

		test( 'PayPal Standard onboards to Jetpack upon changing any setting', async ( {
			page,
		} ) => {
			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			// Ensure PayPal Standard is enabled before trying to open the Manage dialog.
			const enableLink = paypalDiv.getByRole( 'link', {
				name: 'Enable',
			} );

			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( await enableLink.isVisible() ) {
				await enableLink.click();
				await expect(
					paypalDiv
						.getByText( 'Active' )
						.or( paypalDiv.getByText( 'Test account' ) )
				).toBeVisible( visibilityOptions );
			}

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
					message: 'OK',
				},
				body: JSON.stringify( {
					public_id: 'test_public_id',
				} ),
			} );

			// Ensure that the filters above are 100% in place by reloading the page.
			await page.reload();

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
