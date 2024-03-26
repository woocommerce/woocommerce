const { test: base, expect } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );

const test = base.extend( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new AssemblerPage( { page } );
		await use( pageObject );
	},
} );

test.describe( 'Assembler -> Color Pickers', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { baseURL, pageObject } ) => {
		// await setOption(
		// 	request,
		// 	baseURL,
		// 	'woocommerce_customize_store_onboarding_tour_hidden',
		// 	'yes'
		// );
		await pageObject.setupSite( baseURL );
		await pageObject.waitForLoadingScreenFinish();
		const assembler = await pageObject.getAssembler();
		await assembler.getByText( 'Choose your color palette' ).click();
	} );

	test( 'should be displayed', async ( { pageObject } ) => {
		const assembler = await pageObject.getAssembler();

		const colorPickers = assembler.locator(
			'.woocommerce-customize-store_global-styles-variations_item'
		);
		await expect( colorPickers ).toHaveCount( 18 );
	} );

	test( 'the click should trigger the update of colors on the site preview', async ( {
		pageObject,
	}, testInfo ) => {
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();
		testInfo.snapshotSuffix = '';

		await assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.waitFor( {
				strict: false,
			} );

		const colorPickers = await assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.all();

		let index = 0;
		for ( const colorPicker of colorPickers ) {
			await colorPicker.waitFor();
			await colorPicker.click();
			await expect(
				( await editor.locator( 'style' ).allInnerTexts() ).join( ',' )
			).toMatchSnapshot( {
				name: 'color-palette-' + index,
			} );

			index++;
		}
	} );

	test( 'should be focused when a color is picked', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const colorPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.first();

		await colorPicker.click();
		await expect( colorPicker ).toHaveClass( 'is-active' );
	} );

	test( 'should active the save button when a color is picked', async ( {
		pageObject,
		page,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const colorPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.first();

		await colorPicker.click();

		const saveButton = assembler.getByText( 'Save' );

		await expect( saveButton ).toBeEnabled();
	} );

	test( 'the save button should be disable when the user click save', async ( {
		pageObject,
		page,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const colorPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.first();

		await colorPicker.click();

		const saveButton = assembler.getByText( 'Save' );

		const waitResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/global-styles' ) &&
				response.status === 200
		);

		await saveButton.click();

		await waitResponse;

		await expect( saveButton ).toBeDisabled();
	} );

	test( 'should the color palette applied on the frontend', async ( {
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

		expect( style ).toMatchSnapshot( {
			name: 'color-palette-frontend',
		} );
	} );
} );
