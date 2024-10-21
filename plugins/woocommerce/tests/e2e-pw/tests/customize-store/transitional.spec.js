const { test: base, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );
const { activateTheme, DEFAULT_THEME } = require( '../../utils/themes' );
const { AssemblerPage } = require( './assembler/assembler.page' );

const CUSTOMIZE_STORE_URL =
	'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store';
const TRANSITIONAL_URL = `${ CUSTOMIZE_STORE_URL }%2Ftransitional`;
const INTRO_URL = `${ CUSTOMIZE_STORE_URL }%2Fintro`;

const test = base.extend( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new AssemblerPage( { page } );
		await use( pageObject );
	},
} );

test.describe(
	'Store owner can view the Transitional page',
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
			// Reset theme back to default.
			await activateTheme( baseURL, DEFAULT_THEME );

			// Reset tour to visible.
			await setOption(
				request,
				baseURL,
				'woocommerce_customize_store_onboarding_tour_hidden',
				'no'
			);
		} );

		test( 'Accessing the transitional page when the CYS flow is not completed should redirect to the Intro page', async ( {
			page,
			baseURL,
		} ) => {
			await page.goto( TRANSITIONAL_URL );

			const locator = page.locator( 'h1:visible' );
			await expect( locator ).not.toHaveText( 'Your store looks great!' );

			await expect( page.url() ).toBe( `${ baseURL }${ INTRO_URL }` );
		} );

		test( 'Clicking on "Finish customizing" in the assembler should go to the transitional page', async ( {
			pageObject,
			baseURL,
		} ) => {
			await pageObject.setupSite( baseURL );
			await pageObject.waitForLoadingScreenFinish();

			const assembler = await pageObject.getAssembler();
			await assembler
				.getByRole( 'button', { name: 'Finish customizing' } )
				.click();

			await expect(
				assembler.locator( 'text=Your store looks great!' )
			).toBeVisible();
			await expect(
				assembler.locator( 'text=Go to Products' )
			).toBeVisible();
			await expect(
				assembler.locator( 'text=Go to the Editor' )
			).toBeVisible();
			await expect(
				assembler.locator( 'text=Back to home' )
			).toBeVisible();
		} );

		test( 'Clicking on "View store" should go to the store home page', async ( {
			pageObject,
			baseURL,
			page,
		} ) => {
			await setOption(
				request,
				baseURL,
				'woocommerce_admin_customize_store_completed',
				'yes'
			);

			await page.goto( TRANSITIONAL_URL );
			const assembler = await pageObject.getAssembler();

			await assembler.getByRole( 'link', { name: 'View store' } ).click();

			await expect( page ).toHaveURL( '/' );
		} );

		test( 'Clicking on "Share feedback" should open the survey modal', async ( {
			pageObject,
			baseURL,
			page,
		} ) => {
			await setOption(
				request,
				baseURL,
				'woocommerce_admin_customize_store_completed',
				'yes'
			);

			await setOption(
				request,
				baseURL,
				'woocommerce_admin_customize_store_survey_completed',
				'no'
			);

			await page.goto( TRANSITIONAL_URL );
			const assembler = await pageObject.getAssembler();

			const shareFeedbackButton = assembler.getByRole( 'button', {
				name: 'Share feedback',
			} );
			const shareFeedbackModal = assembler.locator(
				'.woocommerce-ai-survey-modal'
			);
			const sendButton = assembler.getByRole( 'button', {
				name: 'Send',
			} );
			const cancelButton = assembler.getByRole( 'button', {
				name: 'Cancel',
			} );

			await expect(
				page.locator( '.edit-site-site-hub__site-title' )
			).toBeVisible();

			await shareFeedbackButton.click();

			await expect( shareFeedbackModal ).toBeVisible();
			await expect( sendButton ).toBeDisabled();

			await cancelButton.click();
			await expect( shareFeedbackModal ).toBeHidden();

			await shareFeedbackButton.click();

			await expect( shareFeedbackModal ).toBeVisible();
			await expect( sendButton ).toBeDisabled();
			await expect(
				assembler.locator( 'text=I wanted to design my own theme.' )
			).toBeVisible();

			await assembler
				.getByRole( 'button', { name: '★' } )
				.first()
				.click();
			await assembler
				.locator( 'text=I wanted to design my own theme.' )
				.click();
			await assembler
				.getByRole( 'button', { name: '★' } )
				.first()
				.click();
			await assembler
				.locator( `text=I didn't like any of the available themes.` )
				.click();
			await assembler
				.getByRole( 'button', { name: '★' } )
				.first()
				.click();
			await assembler
				.locator( `text=I didn't find a theme that matched my needs.` )
				.click();
			await expect( sendButton ).toBeEnabled();
			await sendButton.click();
			await expect( shareFeedbackModal ).toBeHidden();

			await expect( shareFeedbackButton ).toBeHidden();
		} );
	}
);
