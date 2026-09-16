/**
 * External dependencies
 */
import { request } from '@playwright/test';
import { WC_ADMIN_API_PATH } from '@woocommerce/e2e-utils-playwright';

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
		// Completing the profiler saves the store location, and the location list leaves out
		// the selected option. Reset it before every test so each one can pick the same location.
		test.beforeEach( async ( { baseURL } ) => {
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

		test( 'Allows mobile zoom without automatic input focus zoom', async ( {
			page,
		} ) => {
			test.skip(
				!! process.env.IS_MULTISITE,
				'Test not working on a multisite setup, see https://github.com/woocommerce/woocommerce/issues/55066'
			);
			await page.setViewportSize( { width: 390, height: 844 } );
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
			);

			await expect(
				page.locator( 'meta[name="viewport"]' )
			).toHaveAttribute(
				'content',
				'width=device-width, initial-scale=1.0'
			);
			await page
				.getByRole( 'button', { name: 'Set up my store' } )
				.click();
			await page
				.getByRole( 'radio' )
				.filter( { hasText: 'just starting my business' } )
				.click();
			await page.getByRole( 'button', { name: 'Continue' } ).click();

			await expect(
				page.getByRole( 'heading', {
					name: 'Tell us a bit about your store',
				} )
			).toBeVisible();
			await expect(
				page.getByPlaceholder( 'Ex. My awesome store' )
			).toHaveCSS( 'font-size', '16px' );
			await expect(
				page.getByPlaceholder( 'wordpress@example.com' )
			).toHaveCSS( 'font-size', '16px' );
			await expect(
				page.locator(
					'.woocommerce-profiler-select-control__industry .woocommerce-select-control__control-input'
				)
			).toHaveCSS( 'font-size', '16px' );
			await expect(
				page.locator(
					'.woocommerce-profiler-select-control__country .woocommerce-select-control__control-input'
				)
			).toHaveCSS( 'font-size', '16px' );
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

		test( 'Can complete the core profiler with an extension selected', async ( {
			page,
			restApi,
		} ) => {
			test.skip(
				!! process.env.IS_MULTISITE,
				'Test not working on a multisite setup, see https://github.com/woocommerce/woocommerce/issues/55066'
			);

			// Installing from WordPress.org would make this title depend on an outside
			// service and leave real plugins on the shared site, so the two requests the
			// profiler sends for the selection are answered here with the REST
			// controller's success shape. What stays under test is the browser half:
			// the selection reaches those requests, and a successful install finishes
			// the profiler on the home screen.
			const slug = 'google-listings-and-ads';
			const pluginRequests: Array< { path: string; body: unknown } > = [];
			await page.route(
				( url ) =>
					/\/wc-admin\/plugins\/(install|activate)(?:[?&]|$)/.test(
						decodeURIComponent( url.toString() )
					),
				async ( route ) => {
					const path = /\/wc-admin\/plugins\/activate(?:[?&]|$)/.test(
						decodeURIComponent( route.request().url() )
					)
						? 'activate'
						: 'install';
					pluginRequests.push( {
						path,
						body: route.request().postDataJSON(),
					} );
					const errors = { errors: {}, error_data: {} };
					await route.fulfill( {
						json:
							path === 'install'
								? {
										data: {
											installed: [ slug ],
											results: { [ slug ]: true },
											install_time: { [ slug ]: 1 },
											plugin_details: {},
										},
										errors,
										success: true,
								  }
								: {
										data: {
											activated: [ slug ],
											active: [ slug ],
											plugin_details: {},
										},
										errors,
										success: true,
								  },
					} );
				}
			);

			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
			);

			await test.step( 'Walk the profiler to the extensions step', async () => {
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

				await expect(
					page.getByRole( 'heading', {
						name: 'Tell us a bit about your store',
					} )
				).toBeVisible();
				await page
					.locator(
						'form.woocommerce-profiler-business-information-form > div > div > div > div > input'
					)
					.first()
					.click();
				await page
					.getByRole( 'option', { name: 'Clothing and accessories' } )
					.click();
				// The location field is required.
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

			await test.step( 'Select one extension and continue', async () => {
				await expect(
					page.getByRole( 'heading', {
						name: 'Get a boost with our free features',
					} )
				).toBeVisible();
				// Every recommendation that is not active starts selected. Clear them all,
				// then pick one that needs no Jetpack connection, so the profiler ends on
				// the home screen rather than the Jetpack authorization page.
				const cards = page.locator(
					'.woocommerce-profiler-plugins-plugin-card'
				);
				await expect( cards.first() ).toBeVisible();
				for ( const checkbox of await cards
					.getByRole( 'checkbox' )
					.all() ) {
					await checkbox.uncheck();
				}
				await expect(
					cards.locator( 'input[type="checkbox"]:checked' )
				).toHaveCount( 0 );
				await cards
					.and( page.locator( `[data-slug="${ slug }"]` ) )
					.getByRole( 'checkbox' )
					.check();
				await page.getByRole( 'button', { name: 'Continue' } ).click();
			} );

			await test.step( 'Confirm the selection was installed and the profiler finished', async () => {
				await expect(
					page.getByRole( 'heading', { name: 'Home', exact: true } )
				).toBeVisible();

				expect( pluginRequests ).toEqual( [
					{
						path: 'install',
						body: {
							plugins: slug,
							async: false,
							source: 'core-profiler',
						},
					},
					{ path: 'activate', body: { plugins: slug } },
				] );

				// The profiler records what the install reported, which only happens on
				// the success path: a failed install returns to the extensions step.
				const { data: profile } = await restApi.get(
					`${ WC_ADMIN_API_PATH }/onboarding/profile`
				);
				expect( profile.business_extensions ).toEqual( [ slug ] );
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
