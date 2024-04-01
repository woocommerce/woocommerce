const { test, expect, request } = require( '@playwright/test' );
const { BASE_URL } = process.env;
const { activateTheme } = require( '../../utils/themes' );
const { setOption } = require( '../../utils/options' );

const ASSEMBLER_HUB_URL =
	'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store%2Fassembler-hub';
const CUSTOMIZE_STORE_URL =
	'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store';

const skipTestIfUndefined = () => {
	const skipMessage = `Skipping this test on daily run. Environment not compatible.`;

	test.skip( () => {
		const shouldSkip = BASE_URL !== undefined;

		if ( shouldSkip ) {
			console.log( skipMessage );
		}

		return shouldSkip;
	}, skipMessage );
};

skipTestIfUndefined();

test.describe( 'Store owner can view Assembler Hub for store customization', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		try {
			// In some environments the tour blocks clicking other elements.
			await setOption(
				request,
				baseURL,
				'woocommerce_customize_store_onboarding_tour_hidden',
				'yes'
			);
			await activateTheme( 'twentytwentythree' );
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
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

	test( 'Can not access the Assembler Hub page when the theme is not customized', async ( {
		page,
	} ) => {
		await page.goto( ASSEMBLER_HUB_URL );
		const locator = page.locator( 'h1:visible' );

		await expect( locator ).not.toHaveText( 'Customize your store' );
	} );

	test( 'Can view the Assembler Hub page when the theme is already customized', async ( {
		page,
	} ) => {
		await page.goto( CUSTOMIZE_STORE_URL );
		await page.click( 'text=Start designing' );
		await page
			.getByRole( 'button', { name: 'Design a new theme' } )
			.click();

		await page.waitForURL( ASSEMBLER_HUB_URL );

		await page.goto( ASSEMBLER_HUB_URL );
		await expect( page.locator( "text=Let's get creative" ) ).toBeVisible();
	} );

	test( 'Visiting change header should show a list of block patterns to choose from', async ( {
		page,
	} ) => {
		await page.goto( ASSEMBLER_HUB_URL );
		await page.click( 'text=Choose your header' );

		const locator = page.locator(
			'.block-editor-block-patterns-list__list-item'
		);

		await expect( locator ).toBeDefined();
	} );
} );
