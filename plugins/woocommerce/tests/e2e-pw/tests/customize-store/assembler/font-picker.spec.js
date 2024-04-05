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

const getUsedFonts = async ( editorOrPage ) => {
	return await editorOrPage.locator( ':root' ).evaluate( () => {
		const headers = Array.from(
			document.querySelectorAll( 'h1, h2, h3, h4, h5, h6' )
		).map( ( e ) => getComputedStyle( e ).fontFamily );

		const paragraphs = Array.from( document.querySelectorAll( 'p' ) ).map(
			( e ) => getComputedStyle( e ).fontFamily
		);

		const buttons = Array.from( document.querySelectorAll( 'button' ) ).map(
			( e ) => getComputedStyle( e ).fontFamily
		);

		return {
			primaryFont: headers,
			secondaryFont: [ ...paragraphs, ...buttons ],
		};
	} );
};

const slugFontMap = {
	Cardo: 'Cardo Font',
	'Inter, sans-serif': 'Inter',
	'-apple-system, "system-ui", "avenir next", avenir, "segoe ui", "helvetica neue", helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif':
		'System Sans-serif',
	'-apple-system, BlinkMacSystemFont, "avenir next", avenir, "segoe ui", "helvetica neue", helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif':
		'System Sans-serif',
};

test.describe( 'Assembler -> Font Picker', () => {
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
		await assembler.getByText( 'Choose fonts' ).click();
	} );

	test( 'Font pickers should be displayed', async ( { pageObject } ) => {
		const assembler = await pageObject.getAssembler();

		const fontPickers = assembler.locator(
			'.woocommerce-customize-store_global-styles-variations_item'
		);
		await expect( fontPickers ).toHaveCount( 2 );
	} );

	test( 'Picking a font should trigger an update of fonts on the site preview', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		await assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.waitFor( {
				strict: false,
			} );

		const fontPickers = await assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.all();

		for ( const fontPicker of fontPickers ) {
			await fontPicker.waitFor();
			await fontPicker.click();
			const [ primaryFont, secondaryFont ] = (
				await fontPicker.getAttribute( 'aria-label' )
			 )
				.split( '+' )
				.map( ( e ) => e.trim() );

			const usedFonts = await getUsedFonts( editor );

			const isPrimaryFontUsed = usedFonts.primaryFont.some( ( font ) =>
				primaryFont.includes( slugFontMap[ font ] )
			);

			const isSecondaryFontUsed = usedFonts.secondaryFont.some(
				( font ) => secondaryFont.includes( slugFontMap[ font ] )
			);

			expect( isPrimaryFontUsed ).toBe( true );
			expect( isSecondaryFontUsed ).toBe( true );
		}
	} );

	test( 'Font pickers should be focused when a font is picked', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const fontPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.first();

		await fontPicker.click();
		await expect( fontPicker ).toHaveClass( /is-active/ );
	} );

	test( 'Picking a font should activate the save button', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const fontPicker = assembler.locator(
			'.woocommerce-customize-store_global-styles-variations_item:not(.is-active)'
		);

		await fontPicker.click();

		const saveButton = assembler.getByText( 'Save' );

		await expect( saveButton ).toBeEnabled();
	} );

	test( 'The Done button should be visible after clicking save', async ( {
		pageObject,
		page,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const fontPicker = assembler.locator(
			'.woocommerce-customize-store_global-styles-variations_item:not(.is-active)'
		);

		await fontPicker.click();

		const saveButton = assembler.getByText( 'Save' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/global-styles' ) &&
				response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await expect( assembler.getByText( 'Done' ) ).toBeEnabled();
	} );

	test( 'Selected font palette should be applied on the frontend', async ( {
		pageObject,
		page,
		baseURL,
	}, testInfo ) => {
		testInfo.snapshotSuffix = '';
		const assembler = await pageObject.getAssembler();
		const fontPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.last();

		await fontPicker.click();

		const [ primaryFont, secondaryFont ] = (
			await fontPicker.getAttribute( 'aria-label' )
		 )
			.split( '+' )
			.map( ( e ) => e.trim() );

		const saveButton = assembler.getByText( 'Save' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/global-styles' ) &&
				response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await page.goto( baseURL );

		const usedFonts = await getUsedFonts( page );

		const isPrimaryFontUsed = usedFonts.primaryFont.some( ( font ) =>
			primaryFont.includes( slugFontMap[ font ] )
		);

		const isSecondaryFontUsed = usedFonts.secondaryFont.some( ( font ) =>
			secondaryFont.includes( slugFontMap[ font ] )
		);

		expect( isPrimaryFontUsed ).toBe( true );
		expect( isSecondaryFontUsed ).toBe( true );
	} );
} );
