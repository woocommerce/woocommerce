const { test: base, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );
const { activateTheme } = require( '../../utils/themes' );
const { AssemblerPage } = require( './assembler/assembler.page' );
const { features } = require( '../../utils' );

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

test.describe( 'Store owner can view the Transitional page', () => {
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

	test( 'Accessing the transitional page when the CYS flow is not completed should redirect to the Intro page', async ( {
		page,
		baseURL,
	} ) => {
		await page.goto( TRANSITIONAL_URL );

		const locator = page.locator( 'h1:visible' );
		await expect( locator ).not.toHaveText( 'Your store looks great!' );

		await expect( page.url() ).toBe( `${ baseURL }${ INTRO_URL }` );
	} );

	test( 'Clicking on "Done" in the assembler should go to the transitional page', async ( {
		pageObject,
		baseURL,
	} ) => {
		await pageObject.setupSite( baseURL );
		await pageObject.waitForLoadingScreenFinish();

		const assembler = await pageObject.getAssembler();
		await assembler.getByRole( 'button', { name: 'Done' } ).click();

		await expect(
			assembler.locator( 'text=Your store looks great!' )
		).toBeVisible();
		await expect(
			assembler.locator( 'text=Go to Products' )
		).toBeVisible();
		await expect(
			assembler.locator( 'text=Go to the Editor' )
		).toBeVisible();
		await expect( assembler.locator( 'text=Back to home' ) ).toBeVisible();
	} );

	test( 'Clicking on "View store" should go to the store home page in a new page', async ( {
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

		const newTabPromise = page.waitForEvent( 'popup' );
		await assembler.getByRole( 'button', { name: 'View store' } ).click();

		const newTab = await newTabPromise;
		await newTab.waitForLoadState();

		await expect( newTab ).toHaveURL( '/' );
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
		const sendButton = assembler.getByRole( 'button', { name: 'Send' } );
		const cancelButton = assembler.getByRole( 'button', {
			name: 'Cancel',
		} );

		await shareFeedbackButton.click();

		await expect( shareFeedbackModal ).toBeVisible();
		await expect( sendButton ).toBeDisabled();

		await cancelButton.click();
		await expect( shareFeedbackModal ).toBeHidden();

		await shareFeedbackButton.click();

		await assembler.getByRole( 'button', { name: '★' } ).first().click();
		await assembler
			.locator( 'text=I wanted to design my own theme.' )
			.click();
		await sendButton.click();
		await expect( shareFeedbackModal ).toBeHidden();

		await expect( shareFeedbackButton ).toBeHidden();
	} );
} );
