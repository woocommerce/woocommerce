const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme, DEFAULT_THEME } = require( '../../../utils/themes' );
const { setOption } = require( '../../../utils/options' );

const extractFooterClass = ( footerPickerClass ) => {
	const regex = /\bwc-blocks-pattern-footer\S*/;

	const match = footerPickerClass.match( regex );

	return match ? match[ 0 ] : null;
};

const test = base.extend( {
	assemblerPage: async ( { page }, use ) => {
		const assemblerPage = new AssemblerPage( { page } );
		await use( assemblerPage );
	},
} );

test.describe( 'Assembler -> Footers', { tag: '@gutenberg' }, () => {
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
			// Reset theme back to default.
			await activateTheme( baseURL, DEFAULT_THEME );
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

	test( 'The selected footer should be applied on the frontend', async ( {
		assemblerPage,
		page,
		baseURL,
	} ) => {
		const assembler = await assemblerPage.getAssembler();
		const footer = assembler
			.locator( '.block-editor-block-patterns-list__item' )
			.nth( 2 )
			.frameLocator( 'iframe' )
			.locator( '.wc-blocks-footer-pattern' );

		const expectedFooterClass = extractFooterClass(
			await footer.getAttribute( 'class' )
		);

		await footer.click();

		await assembler.locator( '[aria-label="Back"]' ).click();

		const saveButton = assembler.getByText( 'Finish customizing' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/template-parts' ) &&
				response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await page.goto( baseURL );

		const selectedFooterClasses = await page
			.locator( 'footer div.wc-blocks-footer-pattern' )
			.getAttribute( 'class' );

		expect( selectedFooterClasses ).toContain( expectedFooterClass );
	} );

	test( 'Picking a footer should trigger an update on the site preview', async ( {
		assemblerPage,
	} ) => {
		const assembler = await assemblerPage.getAssembler();
		const editor = await assemblerPage.getEditor();

		await assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.waitFor( {
				strict: false,
			} );

		const footerPickers = await assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.all();

		for ( const footerPicker of footerPickers ) {
			await footerPicker.waitFor();
			await footerPicker.click( { delay: 300 } );

			const footerPickerClass = await footerPicker
				.frameLocator( 'iframe' )
				.locator( '.wc-blocks-footer-pattern' )
				.getAttribute( 'class' );

			const expectedFooterClass = extractFooterClass( footerPickerClass );

			const footerPattern = editor.locator(
				`footer div.wc-blocks-footer-pattern`
			);

			await footerPattern.waitFor();
			await expect(
				await footerPattern.getAttribute( 'class' )
			).toContain( expectedFooterClass );
		}
	} );
} );
