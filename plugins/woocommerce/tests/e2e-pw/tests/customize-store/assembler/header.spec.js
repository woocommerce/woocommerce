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

	test( 'The Done button should be visible after clicking save', async ( {
		assemblerPage,
		page,
	} ) => {
		const assembler = await assemblerPage.getAssembler();
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

	test( 'The selected header should be applied on the frontend', async ( {
		assemblerPage,
		page,
		baseURL,
	}, testInfo ) => {
		testInfo.snapshotSuffix = '';
		const assembler = await assemblerPage.getAssembler();
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

	test( 'Picking a header should trigger an update on the site preview', async ( {
		assemblerPage,
	}, testInfo ) => {
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

		let index = 0;
		for ( const headerPicker of headerPickers ) {
			await headerPicker.waitFor();
			await headerPicker.click();

			const headerPickerClass = await headerPicker
				.frameLocator( 'iframe' )
				.locator( '.wc-blocks-header-pattern' )
				.getAttribute( 'class' );

			const expectedHeaderClass = extractHeaderClass( headerPickerClass );

			const headerPattern = await editor.locator(
				`header div.wc-blocks-header-pattern`
			);

			await expect(
				await headerPattern.getAttribute( 'class' )
			).toContain( expectedHeaderClass );

			index++;
		}
	} );
} );

const extractHeaderClass = ( headerPickerClass ) => {
	const regex = /\bwc-blocks-pattern-header\S*/;

	const match = headerPickerClass.match( regex );

	return match ? match[ 0 ] : null;
};
