const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { CustomizeStorePage } = require( '../customize-store.page' );

const { activateTheme } = require( '../../../utils/themes' );
const { setOption } = require( '../../../utils/options' );

const test = base.extend( {
	assemblerPageObject: async ( { page }, use ) => {
		const assemblerPageObject = new AssemblerPage( { page } );
		await use( assemblerPageObject );
	},
	customizeStorePageObject: async ( use ) => {
		const assemblerPageObject = new CustomizeStorePage( { request } );
		await use( assemblerPageObject );
	},
} );

test.describe( 'Assembler -> Color Pickers', () => {
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

	test.afterAll( async ( { baseURL, customizeStorePageObject } ) => {
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
			await customizeStorePageObject.resetCustomizeStoreChanges(
				baseURL
			);
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test.beforeEach( async ( { baseURL, assemblerPageObject } ) => {
		await assemblerPageObject.setupSite( baseURL );
		await assemblerPageObject.waitForLoadingScreenFinish();
		const assembler = await assemblerPageObject.getAssembler();
		await assembler.getByText( 'Choose your color palette' ).click();
	} );

	test( 'Color pickers should be displayed', async ( {
		assemblerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();

		const colorPickers = assembler.locator(
			'.woocommerce-customize-store_global-styles-variations_item'
		);
		await expect( colorPickers ).toHaveCount( 18 );
	} );

	test( 'Picking a color should trigger an update of colors on the site preview', async ( {
		assemblerPageObject,
	}, testInfo ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const editor = await assemblerPageObject.getEditor();
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
			// The snapshot is created in headless mode. Please make sure the browser is in headless mode to ensure the snapshot is correct.
			await expect(
				( await editor.locator( 'style' ).allInnerTexts() ).join( ',' )
			).toMatchSnapshot( {
				name: 'color-palette-' + index,
			} );

			index++;
		}
	} );

	test( 'Color picker should be focused when a color is picked', async ( {
		assemblerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const colorPicker = assembler
			.locator(
				'.woocommerce-customize-store_global-styles-variations_item'
			)
			.first();

		await colorPicker.click();
		await expect( colorPicker ).toHaveClass( /is-active/ );
	} );

	test( 'Picking a color should activate the save button', async ( {
		assemblerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
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
		assemblerPageObject,
		page,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
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
		assemblerPageObject,
		page,
		baseURL,
	}, testInfo ) => {
		testInfo.snapshotSuffix = '';
		const assembler = await assemblerPageObject.getAssembler();
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
