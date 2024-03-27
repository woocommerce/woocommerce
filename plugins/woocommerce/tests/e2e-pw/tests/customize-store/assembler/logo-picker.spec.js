const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { activateTheme } = require( '../../../utils/themes' );
const { CustomizeStorePage } = require( '../customize-store.page' );
const { setOption } = require( '../../../utils/options' );
const { encodeCredentials } = require( '../../../utils' );

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

const resetLogo = async ( baseURL ) => {
	const apiContext = await request.newContext( {
		baseURL,
		extraHTTPHeaders: {
			Authorization: `Basic ${ encodeCredentials(
				'admin',
				'password'
			) }`,
			cookie: '',
		},
	} );

	await apiContext.post( '/wp-json/wc-admin-test-helper/tools/reset-cys', {
		data: {
			site_logo: null,
		},
	} );
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

			await resetLogo( baseURL );

			await customizeStorePageObject.resetCustomizeStoreChanges(
				baseURL
			);
			await activateTheme( 'twentynineteen' );
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test.beforeEach( async ( { baseURL, assemblerPageObject } ) => {
		await assemblerPageObject.setupSite( baseURL );
		await assemblerPageObject.waitForLoadingScreenFinish();
		const assembler = await assemblerPageObject.getAssembler();
		await assembler.getByText( 'Add your logo' ).click();
	} );

	test( 'Logo Picker is empty', async ( { assemblerPageObject } ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const editor = await assemblerPageObject.getEditor();

		await expect( getLogoLocator( editor ) ).toBeHidden();
		await expect( getEmptyLogoPickerLocator( assembler ) ).toBeVisible();
	} );

	test( 'Picking an image should trigger an update of the site preview', async ( {
		assemblerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const editor = await assemblerPageObject.getEditor();

		const emptyLogoPicker = getEmptyLogoPickerLocator( assembler );

		await emptyLogoPicker.click();
		await pickImage( assembler );
		await expect( emptyLogoPicker ).toBeHidden();
		await expect( getLogoPickerLocator( assembler ) ).toBeVisible();
		await expect( getLogoLocator( editor ) ).toBeVisible();
		await expect( assembler.getByText( 'Save' ) ).toBeEnabled();
	} );

	test( 'Selected image should be applied on the frontend', async ( {
		assemblerPageObject,
		page,
		baseURL,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();

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
