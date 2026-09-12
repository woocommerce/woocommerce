/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { setOption } from '../../utils/options';
import { setComingSoon } from '../../utils/coming-soon';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.use( { storageState: ADMIN_STATE_PATH } );

test.afterAll( async ( { baseURL } ) => {
	await setComingSoon( { baseURL, enabled: 'no' } );
} );

test.describe(
	'Store owner can complete the core profiler',
	{ tag: tags.SKIP_ON_EXTERNAL_ENV },
	() => {
		test.beforeAll( async ( { baseURL } ) => {
			try {
				await setOption(
					request,
					baseURL,
					'woocommerce_remote_variant_assignment',
					'60'
				);
				await setOption(
					request,
					baseURL,
					'woocommerce_default_country',
					'US:CA'
				);
			} catch ( error ) {
				console.log( error );
			}
		} );

		test( 'Can complete the core profiler skipping extension install', async ( {
			page,
		} ) => {
			test.skip(
				!! process.env.IS_MULTISITE,
				'Test not working on a multisite setup, see https://github.com/woocommerce/woocommerce/issues/55066'
			);
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
			);

			await test.step( 'Intro page and opt in to data sharing', async () => {
				await expect(
					page.getByRole( 'heading', { name: 'Welcome to Woo!' } )
				).toBeVisible();
				await page
					.getByRole( 'checkbox', {
						name: 'I agree to share my data',
					} )
					.uncheck();
				await page
					.getByRole( 'button', { name: 'Set up my store' } )
					.click();
			} );

			await test.step( 'User profile information', async () => {
				await expect(
					page.getByRole( 'heading', {
						name: 'Which one of these best describes you?',
					} )
				).toBeVisible();
				await page
					.getByRole( 'radio' )
					.filter( { hasText: 'just starting my business' } )
					.click();
				await page.getByRole( 'button', { name: 'Continue' } ).click();
			} );

			await test.step( 'Business Information', async () => {
				await expect(
					page.getByRole( 'heading', {
						name: 'Tell us a bit about your store',
					} )
				).toBeVisible();
				await expect(
					page.getByPlaceholder( 'Ex. My awesome store' )
				).toHaveValue( 'WooCommerce Core E2E Test Suite' );
				await page
					.locator(
						'form.woocommerce-profiler-business-information-form > div > div > div > div > input'
					)
					.first()
					.click();
				// select clothing and accessories
				await page
					.getByRole( 'option', { name: 'Clothing and accessories' } )
					.click();
				// select a WooPayments compatible location
				await page.getByRole( 'combobox' ).last().click();
				await page.getByRole( 'combobox' ).last().fill( 'Australia' );
				await page
					.getByRole( 'option', {
						name: 'Australia — Northern Territory',
					} )
					.click();

				await page
					.getByPlaceholder( 'wordpress@example.com' )
					.fill( 'merchant@example.com' );
				await page.getByLabel( 'Opt-in to receive tips,' ).uncheck();
				await page.getByRole( 'button', { name: 'Continue' } ).click();
			} );

			await test.step( 'Extensions -- do not install any', async () => {
				await expect(
					page.getByRole( 'heading', {
						name: 'Get a boost with our free features',
					} )
				).toBeVisible();
				// skip this step so that no extensions are installed
				await page
					.getByRole( 'button', { name: 'Skip this step' } )
					.click();
			} );

			await test.step( 'Confirm that core profiler was completed and no extensions installed', async () => {
				// intermediate page shown
				await expect(
					page.getByRole( 'heading', {
						name: 'Turning on the lights',
					} )
				).toBeVisible();
				await expect(
					page.locator(
						'.woocommerce-onboarding-progress-bar__filler'
					)
				).toBeVisible();
				// dashboard shown
				await expect(
					page.getByRole( 'heading', {
						name: 'Home',
						exact: true,
					} )
				).toBeVisible();

				// go to the plugins page to make sure that extensions weren't installed
				await page.goto( 'wp-admin/plugins.php?plugin_status=active' );
				await expect(
					page.getByRole( 'heading', {
						name: 'Plugins',
						exact: true,
					} )
				).toBeVisible();
				// confirm that some of the optional extensions aren't present
				await expect(
					page.getByText( 'MailPoet for WooCommerce', {
						exact: true,
					} )
				).toBeHidden();
				await expect(
					page.getByText( 'Pinterest for WooCommerce', {
						exact: true,
					} )
				).toBeHidden();
				await expect(
					page.getByText( 'Google for WooCommerce', { exact: true } )
				).toBeHidden();
			} );

			await test.step( 'Confirm that information from core profiler saved', async () => {
				await page.goto( 'wp-admin/admin.php?page=wc-settings' );
				await expect(
					page.getByRole( 'textbox', {
						name: 'Australia — Northern Territory',
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'textbox', {
						name: 'Australian dollar ($)',
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'textbox', { name: 'Left' } )
				).toBeVisible();
				await expect(
					page.getByLabel( 'Thousand separator', { exact: true } )
				).toHaveValue( ',' );
				await expect(
					page.getByLabel( 'Decimal separator', { exact: true } )
				).toHaveValue( '.' );
				await expect(
					page.getByLabel( 'Number of decimals' )
				).toHaveValue( '2' );
			} );
		} );
	}
);

test.describe(
	'Store owner can skip the core profiler',
	{ tag: tags.SKIP_ON_EXTERNAL_ENV },
	() => {
		test( 'Can skip the guided setup', async ( { page } ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
			);

			await page
				.getByRole( 'button', { name: 'Skip guided setup' } )
				.click();

			await expect(
				page.getByRole( 'heading', {
					name: 'Where is your business located?',
				} )
			).toBeVisible();
			await page.getByLabel( 'Select country/region' ).click();
			await page
				.getByLabel( 'Select country/region' )
				.fill( 'California' );
			await page
				.getByRole( 'option', {
					name: 'United States (US) — California',
				} )
				.click();
			await page
				.getByRole( 'button', { name: 'Go to my store' } )
				.click();

			await expect(
				page.getByRole( 'heading', { name: 'Turning on the lights' } )
			).toBeVisible();

			await expect(
				page.getByRole( 'heading', {
					name: 'Home',
					exact: true,
				} )
			).toBeVisible();

			await test.step( 'Confirm that the store is in coming soon mode after skipping the core profiler', async () => {
				await page.goto( 'wp-admin/admin.php?page=wc-admin' );
				await expect(
					page
						.getByRole( 'menuitem' )
						.filter( { hasText: 'coming soon' } )
				).toBeVisible();
			} );
		} );
	}
);
