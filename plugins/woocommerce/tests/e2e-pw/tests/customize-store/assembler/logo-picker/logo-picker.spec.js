const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( '../assembler.page' );
const { LogoPickerPage } = require( './logo-picker.page' );
const { activateTheme, DEFAULT_THEME } = require( '../../../../utils/themes' );
const { CustomizeStorePage } = require( '../../customize-store.page' );
const { setOption } = require( '../../../../utils/options' );

const test = base.extend( {
	assemblerPageObject: async ( { page }, use ) => {
		const assemblerPageObject = new AssemblerPage( { page } );
		await use( assemblerPageObject );
	},
	customizeStorePageObject: async ( {}, use ) => {
		const assemblerPageObject = new CustomizeStorePage( { request } );
		await use( assemblerPageObject );
	},
	logoPickerPageObject: async ( { page }, use ) => {
		const logoPickerPageObject = new LogoPickerPage( {
			page,
			request,
		} );
		await use( logoPickerPageObject );
	},
} );

test.describe( 'Assembler -> Logo Picker', { tag: '@gutenberg' }, () => {
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

			await customizeStorePageObject.resetCustomizeStoreChanges(
				baseURL
			);
			// Reset theme back to default.
			await activateTheme( baseURL, DEFAULT_THEME );
		} catch ( error ) {
			console.log( 'Store completed option not updated' );
		}
	} );

	test.beforeEach(
		async ( { baseURL, assemblerPageObject, logoPickerPageObject } ) => {
			await logoPickerPageObject.resetLogo( baseURL );
			await assemblerPageObject.setupSite( baseURL );
			await assemblerPageObject.waitForLoadingScreenFinish();
			const assembler = await assemblerPageObject.getAssembler();
			await assembler.getByText( 'Add your logo' ).click();
		}
	);

	test( 'Logo Picker should be empty initially', async ( {
		assemblerPageObject,
		logoPickerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const editor = await assemblerPageObject.getEditor();

		await expect(
			logoPickerPageObject.getLogoLocator( editor )
		).toBeHidden();
		await expect(
			logoPickerPageObject.getEmptyLogoPickerLocator( assembler )
		).toBeVisible();
	} );

	test( 'Selecting an image should update the site preview', async ( {
		assemblerPageObject,
		logoPickerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const editor = await assemblerPageObject.getEditor();

		const imageWidth = assembler.getByText( 'Image width' );
		const linkLogoToHomepage = assembler.getByText(
			'Link logo to homepage'
		);
		const useAsSiteIcon = assembler.getByText( 'Use as site icon' );

		await expect( imageWidth ).toBeHidden();
		await expect( linkLogoToHomepage ).toBeHidden();
		await expect( useAsSiteIcon ).toBeHidden();

		const emptyLogoPicker =
			logoPickerPageObject.getEmptyLogoPickerLocator( assembler );

		await emptyLogoPicker.click();
		await logoPickerPageObject.pickImage( assembler );
		await expect( emptyLogoPicker ).toBeHidden();
		await expect(
			logoPickerPageObject.getLogoPickerLocator( assembler )
		).toBeVisible();
		await expect(
			logoPickerPageObject.getLogoLocator( editor )
		).toBeVisible();

		await expect( imageWidth ).toBeVisible();
		await expect( linkLogoToHomepage ).toBeVisible();
		await expect( useAsSiteIcon ).toBeVisible();
	} );

	test( 'Changing the image width should update the site preview and the frontend', async ( {
		assemblerPageObject,
		logoPickerPageObject,
		baseURL,
		page,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const editor = await assemblerPageObject.getEditor();
		const emptyLogoPicker =
			logoPickerPageObject.getEmptyLogoPickerLocator( assembler );

		await emptyLogoPicker.click();
		await logoPickerPageObject.pickImage( assembler );
		await assembler
			.getByRole( 'spinbutton', { name: 'Image width' } )
			.fill( '100' );

		await expect(
			editor
				.getByLabel( 'Block: Header' )
				.getByLabel( 'Block: Site Logo' )
		).toHaveCSS( 'width', '100px' );

		await logoPickerPageObject.saveLogoSettings( assembler );
		const imageFrontend = await logoPickerPageObject.getLogoLocator( page );
		await page.goto( baseURL );
		await expect( imageFrontend ).toHaveAttribute( 'width', '100' );
	} );

	test( 'Clicking the Delete button should remove the selected image', async ( {
		assemblerPageObject,
		logoPickerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const emptyLogoPicker =
			logoPickerPageObject.getEmptyLogoPickerLocator( assembler );

		await emptyLogoPicker.click();
		await logoPickerPageObject.pickImage( assembler );
		const emptyLogoLocator =
			logoPickerPageObject.getEmptyLogoPickerLocator( assembler );
		await expect( emptyLogoLocator ).toBeHidden();
		await assembler.getByLabel( 'Options', { exact: true } ).click();
		await assembler.getByText( 'Delete' ).click();
		await expect( emptyLogoLocator ).toBeVisible();
	} );

	test( 'Clicking the replace image should open the media gallery', async ( {
		assemblerPageObject,
		logoPickerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const emptyLogoPicker =
			logoPickerPageObject.getEmptyLogoPickerLocator( assembler );
		await emptyLogoPicker.click();
		await logoPickerPageObject.pickImage( assembler );
		await assembler.getByLabel( 'Options', { exact: true } ).click();
		await assembler.getByText( 'Replace' ).click();
		await expect( assembler.getByText( 'Media Library' ) ).toBeVisible();
	} );

	// This test checks this regression: https://github.com/woocommerce/woocommerce/issues/49668
	test( 'Logo should be visible after header update', async ( {
		assemblerPageObject,
		logoPickerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const emptyLogoPicker =
			logoPickerPageObject.getEmptyLogoPickerLocator( assembler );
		await emptyLogoPicker.click();
		await logoPickerPageObject.pickImage( assembler );

		await assembler.getByLabel( 'Back' ).click();

		await assembler.getByText( 'Choose your header' ).click();

		const header = assembler
			.locator( '.block-editor-block-patterns-list__list-item' )
			.nth( 1 )
			.frameLocator( 'iframe' )
			.locator( '.wc-blocks-header-pattern' );

		await header.click();

		await assembler.getByLabel( 'Back' ).click();

		await assembler.getByText( 'Add your logo' ).click();
		const emptyLogoLocator =
			logoPickerPageObject.getPlaceholderPreview( assembler );

		await expect( emptyLogoLocator ).toBeHidden();
	} );

	test(
		'Enabling the "use as site icon" option should set the image as the site icon',
		{ tag: '@skip-on-default-pressable' },
		async ( { page, assemblerPageObject, logoPickerPageObject } ) => {
			const assembler = await assemblerPageObject.getAssembler();
			const emptyLogoPicker =
				logoPickerPageObject.getEmptyLogoPickerLocator( assembler );
			await emptyLogoPicker.click();
			await logoPickerPageObject.pickImage( assembler );
			await assembler.getByText( 'Use as site icon' ).click();
			await logoPickerPageObject.saveLogoSettings( assembler );

			// alternative way to verify new site icon on the site
			// verifying site icon shown in the new tab is impossible in headless mode
			const date = new Date();
			const month = ( date.getMonth() + 1 ).toString().padStart( 2, '0' );
			await expect(
				page.goto(
					`/wp-content/uploads/${ date.getFullYear() }/${ month }/image-03-100x100.png`
				)
			).toBeTruthy();
		}
	);

	test( 'The selected image should be visible on the frontend', async ( {
		page,
		baseURL,
		logoPickerPageObject,
		assemblerPageObject,
	} ) => {
		const assembler = await assemblerPageObject.getAssembler();
		const emptyLogoPicker =
			logoPickerPageObject.getEmptyLogoPickerLocator( assembler );

		await emptyLogoPicker.click();
		await logoPickerPageObject.pickImage( assembler );
		await logoPickerPageObject.saveLogoSettings( assembler );

		await page.goto( baseURL );

		await expect(
			logoPickerPageObject.getLogoLocator( page )
		).toBeVisible();
	} );
} );
