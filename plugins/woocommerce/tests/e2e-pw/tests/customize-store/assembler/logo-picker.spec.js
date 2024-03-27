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

const getEmptyLogoPickerLocator = ( assembler ) =>
	assembler.locator( '.block-library-site-logo__inspector-upload-container' );

const getLogoPickerLocator = ( assembler ) =>
	assembler.locator( '.woocommerce-customize-store__sidebar-logo-container' );

const getLogoLocator = ( editorOrPage ) =>
	editorOrPage.locator( 'header  .custom-logo' );

const pickImage = async ( assembler ) => {
	await assembler.getByText( 'Media Library' ).click();

	await assembler.getByLabel( 'image-03' ).click();
	await assembler
		.getByRole( 'button', { name: 'Select', exact: true } )
		.click();
};

test.describe( 'Assembler -> Logo Picker', () => {
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
		await assembler.getByText( 'Add your logo' ).click();
	} );

	test( 'Logo Picker is empty', async ( { pageObject } ) => {
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		await expect( getLogoLocator( editor ) ).toBeHidden();
		await expect( getEmptyLogoPickerLocator( assembler ) ).toBeVisible();
	} );

	test( 'Picking an image should trigger an update of the site preview', async ( {
		pageObject,
	} ) => {
		const assembler = await pageObject.getAssembler();
		const editor = await pageObject.getEditor();

		const emptyLogoPicker = getEmptyLogoPickerLocator( assembler );

		await emptyLogoPicker.click();
		await pickImage( assembler );
		await expect( emptyLogoPicker ).toBeHidden();
		await expect( getLogoPickerLocator( assembler ) ).toBeVisible();
		await expect( getLogoLocator( editor ) ).toBeVisible();
		await expect( assembler.getByText( 'Save' ) ).toBeEnabled();
	} );

	test( 'Selected image should be applied on the frontend', async ( {
		pageObject,
		page,
		baseURL,
	} ) => {
		const assembler = await pageObject.getAssembler();

		const emptyLogoPicker = getEmptyLogoPickerLocator( assembler );

		await emptyLogoPicker.click();
		await pickImage( assembler );
		await assembler.getByText( 'Save' ).click();
		const waitForLogoResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/settings' ) &&
				response.status() === 200
		);

		const waitForHeaderResponse = page.waitForResponse(
			( response ) =>
				response
					.url()
					.includes(
						'wp-json/wp/v2/template-parts/twentytwentyfour//header'
					) && response.status() === 200
		);

		await Promise.all( [ waitForLogoResponse, waitForHeaderResponse ] );

		await page.goto( baseURL );

		await expect( getLogoLocator( page ) ).toBeVisible();
	} );
} );
