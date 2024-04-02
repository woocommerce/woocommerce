const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme } = require( '../../../utils/themes' );
const { setOption } = require( '../../../utils/options' );

const test = base.extend( {
	assemblerPage: async ( { page }, use ) => {
		const assemblerPage = new AssemblerPage( { page } );
		await use( assemblerPage );
	},
} );

test.describe( 'Assembler -> Footers', () => {
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
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test.afterAll( async ( { baseURL } ) => {
		try {
			// In some environments the tour blocks clicking other elements.
			await setOption(
				request,
				baseURL,
				'woocommerce_customize_store_onboarding_tour_hidden',
				'no'
			);
			await setOption(
				request,
				baseURL,
				'woocommerce_admin_customize_store_completed',
				'no'
			);

			await activateTheme( 'twentynineteen' );
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test.beforeEach( async ( { baseURL, assemblerPage } ) => {
		await assemblerPage.setupSite( baseURL );
		await assemblerPage.waitForLoadingScreenFinish();
		const assembler = await assemblerPage.getAssembler();
		await assembler.getByText( 'Choose your footer' ).click();
	} );

	test( 'Available footers should be displayed', async ( {
		assemblerPage,
	} ) => {
		const assembler = await assemblerPage.getAssembler();

		const footers = assembler.locator(
			'.block-editor-block-patterns-list__list-item'
		);

		await expect( footers ).toHaveCount( 3 );
	} );

	test( 'The selected footer should be focused when is clicked', async ( {
		assemblerPage,
	} ) => {
		const assembler = await assemblerPage.getAssembler();
		const footer = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await footer.click();
		await expect( footer ).toHaveClass( /is-selected/ );
	} );

	test( 'The Done button should be visible after clicking save', async ( {
		assemblerPage,
		page,
	} ) => {
		const assembler = await assemblerPage.getAssembler();
		const footer = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await footer.click();

		const saveButton = assembler.getByText( 'Save' );
		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/template-parts' ) &&
				response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await expect( assembler.getByText( 'Done' ) ).toBeEnabled();
	} );

	test( 'Selected footer should be applied on the frontend', async ( {
		assemblerPage,
		page,
		baseURL,
	}, testInfo ) => {
		testInfo.snapshotSuffix = '';
		const assembler = await assemblerPage.getAssembler();
		const footer = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await footer.click();

		const saveButton = assembler.getByText( 'Save' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/template-parts' ) &&
				response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await page.goto( baseURL );
		const footerHTML = await page.locator( 'footer' ).innerHTML();

		// The snapshot is created in headless mode. Please make sure the browser is in headless mode to ensure the snapshot is correct.
		expect( footerHTML ).toMatchSnapshot( {
			name: 'cys-selected-footer',
		} );
	} );
} );
