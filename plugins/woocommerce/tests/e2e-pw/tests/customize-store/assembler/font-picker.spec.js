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

const getUsedFonts = async ( editor ) => {
	return await editor.locator( ':root' ).evaluate( () => {
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

	test.only( 'Picking a color should trigger an update of colors on the site preview', async ( {
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

			console.log( 'usedFonts:', usedFonts );
			console.log( 'primaryFont:', primaryFont );
			console.log( 'secondaryFont:', secondaryFont );

			const isPrimaryFontUsed = usedFonts.primaryFont.some( ( font ) =>
				primaryFont.includes( font )
			);

			const isSecondaryFontUsed = usedFonts.secondaryFont.some(
				( font ) => secondaryFont.includes( font )
			);

			expect( isPrimaryFontUsed ).toBe( true );
			expect( isSecondaryFontUsed ).toBe( true );
		}
	} );

	test( 'Color picker should be focused when a color is picked', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const colorPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.first();

		await colorPicker.click();
		await expect( colorPicker ).toHaveClass( /is-active/ );
	} );

	test( 'Picking a color should activate the save button', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const colorPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.nth( 2 );

		await colorPicker.click();

		const saveButton = assembler.getByText( 'Save' );

		await expect( saveButton ).toBeEnabled();
	} );

	test( 'The Done button should be visible after clicking save', async ( {
		pageObject,
		page,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const colorPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.nth( 2 );

		await colorPicker.click();

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

	test( 'Selected color palette should be applied on the frontend', async ( {
		pageObject,
		page,
		baseURL,
	}, testInfo ) => {
		testInfo.snapshotSuffix = '';
		const assembler = await pageObject.getAssembler();
		const colorPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.last();

		await colorPicker.click();

		const saveButton = assembler.getByText( 'Save' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/global-styles' ) &&
				response.status() === 200
		);

		await saveButton.click();

		await waitResponse;

		await page.goto( baseURL );
		const style = await page
			.locator( '#global-styles-inline-css' )
			.innerHTML();

		// The snapshot is created in headless mode. Please make sure the browser is in headless mode to ensure the snapshot is correct.
		expect( style ).toMatchSnapshot( {
			name: 'color-palette-frontend',
		} );
	} );
} );
