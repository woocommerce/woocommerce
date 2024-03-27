const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme } = require( '../../../utils/themes' );
const { setOption } = require( '../../../utils/options' );

const test = base.extend( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new AssemblerPage( { page } );
		await use( pageObject );
	},
} );

test.describe( 'Assembler -> headers', () => {
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

	test.beforeEach( async ( { baseURL, pageObject } ) => {
		await pageObject.setupSite( baseURL );
		await pageObject.waitForLoadingScreenFinish();
		const assembler = await pageObject.getAssembler();
		await assembler.getByText( 'Choose your header' ).click();
	} );

	test( 'Available headers should be displayed', async ( { pageObject } ) => {
		const assembler = await pageObject.getAssembler();

		const headers = assembler.locator(
			'.block-editor-block-patterns-list__list-item'
		);

		await expect( headers ).toHaveCount( 4 );
	} );

	test( 'The selected header should be focused when is clicked', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const header = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await header.click();
		await expect( header ).toHaveClass( /is-selected/ );
	} );

	test( 'The Done button should be visible after clicking save', async ( {
		pageObject,
		page,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const header = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await header.click();

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

	test( 'Selected header should be applied on the frontend', async ( {
		pageObject,
		page,
		baseURL,
	}, testInfo ) => {
		testInfo.snapshotSuffix = '';
		const assembler = await pageObject.getAssembler();
		const header = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 1 );

		await header.click();

		const saveButton = assembler.getByText( 'Save' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/template-parts' ) &&
				response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await page.goto( baseURL );
		const headerHTML = await page.locator( 'header' ).innerHTML();

		// The snapshot is created in headless mode. Please make sure the browser is in headless mode to ensure the snapshot is correct.
		expect( headerHTML ).toMatchSnapshot( {
			name: 'cys-selected-header',
		} );
	} );
} );
