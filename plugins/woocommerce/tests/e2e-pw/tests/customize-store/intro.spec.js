const { test, expect, request } = require('@playwright/test');
const { BASE_URL } = process.env;
const { features } = require('../../utils');
const { activateTheme } = require('../../utils/themes');
const { setOption } = require('../../utils/options');

const CUSTOMIZE_STORE_URL =
	'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store';

const skipTestIfUndefined = () => {
	const skipMessage = `Skipping this test on daily run. Environment not compatible.`;

	test.skip(() => {
		const shouldSkip = BASE_URL !== undefined;

		if (shouldSkip) {
			console.log(skipMessage);
		}

		return shouldSkip;
	}, skipMessage);
};

skipTestIfUndefined();

test.describe( 'Store owner can view the Intro page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		// In some environments the tour blocks clicking other elements.
		await setOption(
			request,
			baseURL,
			'woocommerce_customize_store_onboarding_tour_hidden',
			'yes'
		);

		await features.setFeatureFlag(
			request,
			baseURL,
			'customize-store',
			true
		);

		// Need a block enabled theme to test
		await activateTheme( 'twentytwentyfour' );
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
			console.log( 'Store completed option not updated', error );
		}
	} );

	test.afterAll( async ( { baseURL } ) => {
		await features.resetFeatureFlags( request, baseURL );

		// Reset theme back to twentynineteen
		await activateTheme( 'twentynineteen' );

		// Reset tour to visible.
		await setOption(
			request,
			baseURL,
			'woocommerce_customize_store_onboarding_tour_hidden',
			'no'
		);
	} );

	test( 'it shows the offline banner when the network is offline', async ( {
		page,
		context,
	} ) => {
		await page.goto( CUSTOMIZE_STORE_URL );
		await expect( page.locator( 'text=Design your own' ) ).toBeVisible();
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
		await expect( page.locator( 'text=Design your own' ) ).toBeVisible();
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
			console.log( 'Store completed option not updated', error );
		}
		await page.goto( CUSTOMIZE_STORE_URL );

		await expect(
			page.locator( '.existing-no-ai-theme-banner' )
		).toBeVisible();
		await expect(
			page.locator( 'text=Edit your custom theme' )
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Customize your theme' } )
		).toBeVisible();
	} );

	test( 'it shows the "no AI" banner when the task is completed and the theme is not the default', async ( {
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
			console.log( 'Store completed option not updated', error );
		}
		await activateTheme( 'twentytwentythree' );

		await page.goto( CUSTOMIZE_STORE_URL );

		await expect( page.locator( '.no-ai-banner' ) ).toBeVisible();
		await expect( page.locator( 'text=Design your own' ) ).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Start designing' } )
		).toBeVisible();
	} );

	test( 'it shows the "no AI" banner, when the task is completed and the theme is not the default', async ( {
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
			console.log( 'Store completed option not updat,ed', error );
		}
		await activateTheme( 'twentytwentythree' );

		await page.goto( CUSTOMIZE_STORE_URL );

		await expect( page.locator( '.no-ai-banner' ) ).toBeVisible();
		await expect( page.locator( 'text=Design your own' ) ).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Start designing' } )
		).toBeVisible();
	} );
} );
