const { test: base, expect, request } = require( '@playwright/test' );
const { activateTheme, DEFAULT_THEME } = require( '../../utils/themes' );
const { setOption } = require( '../../utils/options' );
const { AssemblerPage } = require( './assembler/assembler.page' );

const CUSTOMIZE_STORE_URL =
	'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store';

const test = base.extend( {
	assemblerPageObject: async ( { page }, use ) => {
		const pageObject = new AssemblerPage( { page } );
		await use( pageObject );
	},
} );

test.describe(
	'Store owner can view the Intro page',
	{ tag: '@gutenberg' },
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeAll( async ( { baseURL } ) => {
			// In some environments the tour blocks clicking other elements.
			await setOption(
				request,
				baseURL,
				'woocommerce_customize_store_onboarding_tour_hidden',
				'yes'
			);

			// Need a block enabled theme to test
			await activateTheme( baseURL, 'twentytwentyfour' );
		} );

		test.beforeEach( async ( { baseURL } ) => {
			try {
				await setOption(
					request,
					baseURL,
					'woocommerce_admin_customize_store_completed',
					'no'
				);
			} catch ( error ) {
				console.log( 'Store completed option not updated' );
			}
		} );

		test.afterAll( async ( { baseURL } ) => {
			// Reset theme to the default.
			await activateTheme( baseURL, DEFAULT_THEME );

			// Reset tour to visible.
			await setOption(
				request,
				baseURL,
				'woocommerce_customize_store_onboarding_tour_hidden',
				'no'
			);
		} );

		test( 'it shows the "offline banner" when the network is offline', async ( {
			page,
			context,
		} ) => {
			await page.goto( CUSTOMIZE_STORE_URL );
			await expect(
				page.locator( 'text=Design your own' )
			).toBeVisible();
			await context.setOffline( true );

			await expect( page.locator( '.offline-banner' ) ).toBeVisible();
			await expect(
				page.locator( 'text=Looking to design your store using AI?' )
			).toBeVisible();
		} );

		test( 'it shows the "no AI" banner on Core when the task is not completed', async ( {
			page,
		} ) => {
			await page.goto( CUSTOMIZE_STORE_URL );

			await expect( page.locator( '.no-ai-banner' ) ).toBeVisible();
			await expect(
				page.locator( 'text=Design your own' )
			).toBeVisible();
			await expect(
				page.getByRole( 'button', { name: 'Start designing' } )
			).toBeVisible();
		} );

		test( 'it shows the "no AI customize theme" banner when the task is completed', async ( {
			page,
			baseURL,
		} ) => {
			try {
				await setOption(
					request,
					baseURL,
					'woocommerce_admin_customize_store_completed',
					'yes'
				);
			} catch ( error ) {
				console.log( 'Store completed option not updated' );
			}
			await page.goto( CUSTOMIZE_STORE_URL );

			await expect(
				page.locator( '.existing-no-ai-theme-banner' )
			).toBeVisible();
			await expect( page.locator( 'h1' ) ).toHaveText(
				'Customize your theme'
			);
			await expect(
				page.getByRole( 'button', { name: 'Customize your store' } )
			).toBeVisible();
		} );

		test( 'it shows the "non default block theme" banner when the theme is a block theme different than TT4', async ( {
			page,
			baseURL,
		} ) => {
			await activateTheme( baseURL, 'twentytwentythree' );

			await page.goto( CUSTOMIZE_STORE_URL );

			await expect( page.locator( 'h1' ) ).toHaveText(
				'Customize your theme'
			);
			await expect(
				page.getByRole( 'button', { name: 'Go to the Editor' } )
			).toBeVisible();
		} );

		test( 'clicking on "Go to the Customizer" with a classic theme should go to the customizer', async ( {
			page,
			baseURL,
		} ) => {
			await activateTheme( baseURL, 'twentytwenty' );

			await page.goto( CUSTOMIZE_STORE_URL );

			await page
				.getByRole( 'button', { name: 'Go to the Customizer' } )
				.click();

			await page.waitForNavigation();
			await expect( page.url() ).toContain( 'customize.php' );
		} );
	}
);
