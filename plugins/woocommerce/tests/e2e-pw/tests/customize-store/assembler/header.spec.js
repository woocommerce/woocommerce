const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme, DEFAULT_THEME } = require( '../../../utils/themes' );
const { setOption } = require( '../../../utils/options' );

const extractHeaderClass = ( headerPickerClass ) => {
	const regex = /\bwc-blocks-pattern-header\S*/;

	const match = headerPickerClass.match( regex );

	return match ? match[ 0 ] : null;
};

const test = base.extend( {
	assemblerPage: async ( { page }, use ) => {
		const assemblerPage = new AssemblerPage( { page } );
		await use( assemblerPage );
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

			await activateTheme( DEFAULT_THEME );
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test.beforeEach( async ( { baseURL, assemblerPage } ) => {
		await assemblerPage.setupSite( baseURL );
		await assemblerPage.waitForLoadingScreenFinish();
		const assembler = await assemblerPage.getAssembler();
		await assembler.getByText( 'Choose your header' ).click();
	} );

	test( 'Available headers should be displayed', async ( {
		assemblerPage,
	} ) => {
		const assembler = await assemblerPage.getAssembler();

		const headers = assembler.locator(
			'.block-editor-block-patterns-list__list-item'
		);

		await expect( headers ).toHaveCount( 4 );
	} );

	test( 'The selected header should be focused when is clicked', async ( {
		assemblerPage,
	} ) => {
		const assembler = await assemblerPage.getAssembler();
		const header = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 );

		await header.click();
		await expect( header ).toHaveClass( /is-selected/ );
	} );

	test( 'The selected header should be applied on the frontend', async ( {
		assemblerPage,
		page,
		baseURL,
	} ) => {
		const assembler = await assemblerPage.getAssembler();
		const header = await assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.nth( 1 )
			.frameLocator( 'iframe' )
			.locator( '.wc-blocks-header-pattern' );

		const expectedHeaderClass = extractHeaderClass(
			await header.getAttribute( 'class' )
		);

		await header.click();

		assembler.locator( '[aria-label="Back"]' ).click();

		const saveButton = assembler.getByText( 'Save' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/template-parts' ) &&
				response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await page.goto( baseURL );
		const selectedHeaderClasses = await page
			.locator( 'header div.wc-blocks-header-pattern' )
			.getAttribute( 'class' );

		expect( selectedHeaderClasses ).toContain( expectedHeaderClass );
	} );

	test( 'Picking a header should trigger an update on the site preview', async ( {
		assemblerPage,
	} ) => {
		const assembler = await assemblerPage.getAssembler();
		const editor = await assemblerPage.getEditor();

		await assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.waitFor( {
				strict: false,
			} );

		const headerPickers = await assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.all();

		for ( const headerPicker of headerPickers ) {
			await headerPicker.waitFor();
			await headerPicker.click();

			const headerPickerClass = await headerPicker
				.frameLocator( 'iframe' )
				.locator( '.wc-blocks-header-pattern' )
				.getAttribute( 'class' );

			const expectedHeaderClass = extractHeaderClass( headerPickerClass );

			const headerPattern = await editor.locator(
				'header div.wc-blocks-header-pattern'
			);

			await expect(
				await headerPattern.getAttribute( 'class' )
			).toContain( expectedHeaderClass );
		}
	} );
} );
