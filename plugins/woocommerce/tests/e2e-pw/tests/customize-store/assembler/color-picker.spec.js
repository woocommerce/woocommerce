const { test: base, expect, request } = require( '@playwright/test' );
const { AssemblerPage } = require( './assembler.page' );
const { CustomizeStorePage } = require( '../customize-store.page' );
const { encodeCredentials } = require( '../../../utils/plugin-utils' );

const { activateTheme, DEFAULT_THEME } = require( '../../../utils/themes' );
const { getInstalledWordPressVersion } = require( '../../../utils/wordpress' );
const { setOption } = require( '../../../utils/options' );
const { admin } = require( '../../../test-data/data' );
const { tags } = require( '../../../fixtures/fixtures' );
const { ADMIN_STATE_PATH } = require( '../../../playwright.config' );

const test = base.extend( {
	assemblerPageObject: async ( { page }, use ) => {
		const assemblerPageObject = new AssemblerPage( { page } );
		await use( assemblerPageObject );
	},
	customizeStorePageObject: async ( {}, use ) => {
		const assemblerPageObject = new CustomizeStorePage( { request } );
		await use( assemblerPageObject );
	},
} );

const colorPalette = {
	name: 'Slate',
	button: {
		background: 'rgb(255, 223, 109)',
		color: 'rgb(253, 251, 239)',
	},
	paragraph: {
		color: [ 'rgb(239, 242, 249)', 'rgb(255, 255, 255)' ],
	},
	header: {
		color: [ 'rgb(239, 242, 249)', 'rgb(255, 255, 255)' ],
	},
};

test.describe(
	'Assembler -> Color Pickers',
	{ tag: [ tags.GUTENBERG, tags.NOT_E2E ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

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

				// Reset theme back to default.
				await activateTheme( baseURL, DEFAULT_THEME );
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

		test( `Color palette ${ colorPalette.name } should be applied`, async ( {
			assemblerPageObject,
			page,
		} ) => {
			const assembler = await assemblerPageObject.getAssembler();
			const editor = await assemblerPageObject.getEditor();

			const colorPicker = assembler.getByLabel( colorPalette.name );

			await colorPicker.click();

			await assembler.locator( '[aria-label="Back"]' ).click();

			const saveButton = assembler.getByText( 'Finish customizing' );

			const waitResponse = page.waitForResponse(
				( response ) =>
					response.url().includes( 'wp-json/wp/v2/global-styles' ) &&
					response.status() === 200
			);

			const buttons = await editor
				.locator( '.wp-block-button > .wp-block-button__link' )
				.evaluateAll( ( elements ) =>
					elements.map( ( element ) => {
						const style = window.getComputedStyle( element );
						return {
							background: style.backgroundColor,
							color: style.color,
						};
					} )
				);

			const paragraphs = await editor
				.locator(
					'p.wp-block.wp-block-paragraph:not([aria-label="Empty block; start writing or type forward slash to choose a block"])'
				)
				.evaluateAll( ( elements ) =>
					elements.map( ( element ) => {
						const style = window.getComputedStyle( element );
						return {
							background: style.backgroundColor,
							color: style.color,
						};
					} )
				);

			const headers = await editor
				.locator( 'h1, h2, h3, h4, h5, h6' )
				.evaluateAll( ( elements ) =>
					elements.map( ( element ) => {
						const style = window.getComputedStyle( element );
						return {
							background: style.backgroundColor,
							color: style.color,
						};
					} )
				);

			const headersInCoverBlock = await editor
				.locator(
					`.wp-block-cover__inner-container h1,
					 .wp-block-cover__inner-container h2,
					 .wp-block-cover__inner-container h3,
					 .wp-block-cover__inner-container h4,
					 .wp-block-cover__inner-container h5,
					 .wp-block-cover__inner-container h6`
				)
				.evaluateAll( ( elements ) =>
					elements.map( ( element ) => {
						const style = window.getComputedStyle( element );
						return {
							background: style.backgroundColor,
							color: style.color,
						};
					} )
				);

			for ( const element of buttons ) {
				await expect( element.background ).toEqual(
					colorPalette.button.background
				);
			}

			for ( const element of paragraphs ) {
				expect(
					colorPalette.paragraph.color.includes( element.color )
				).toBe( true );
			}

			for ( const element of headers ) {
				expect(
					colorPalette.header.color.includes( element.color )
				).toBe( true );
			}

			// Check that the headers in the cover block are white text.
			// See: https://github.com/woocommerce/woocommerce/pull/48447
			for ( const element of headersInCoverBlock ) {
				expect( element.color ).toEqual( 'rgb(255, 255, 255)' );
			}

			await saveButton.click();

			await waitResponse;
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

		test(
			'Selected color palette should be applied on the frontend',
			{ tag: tags.SKIP_ON_PRESSABLE },
			async ( { assemblerPageObject, page, baseURL } ) => {
				const assembler = await assemblerPageObject.getAssembler();
				const colorPicker = assembler
					.locator(
						'.woocommerce-customize-store_global-styles-variations_item'
					)
					.last();

				await colorPicker.click();

				await assembler.locator( '[aria-label="Back"]' ).click();

				const saveButton = assembler.getByText( 'Finish customizing' );

				const waitResponseGlobalStyles = page.waitForResponse(
					( response ) =>
						response
							.url()
							.includes( 'wp-json/wp/v2/global-styles' ) &&
						response.status() === 200
				);

				const wordPressVersion = await getInstalledWordPressVersion();

				await saveButton.click();

				await Promise.all( [
					waitResponseGlobalStyles,
					wordPressVersion < 6.6
						? page.waitForResponse(
								( response ) =>
									response.url().includes(
										// When CYS will support all block themes, this URL will change.
										'wp-json/wp/v2/templates/twentytwentyfour//home'
									) && response.status() === 200
						  )
						: Promise.resolve(),
				] );

				await page.goto( baseURL );

				const paragraphs = await page
					.locator(
						'p.wp-block.wp-block-paragraph:not([aria-label="Empty block; start writing or type forward slash to choose a block"])'
					)
					.evaluateAll( ( elements ) =>
						elements.map( ( element ) => {
							const style = window.getComputedStyle( element );
							return {
								background: style.backgroundColor,
								color: style.color,
							};
						} )
					);

				const buttons = await page
					.locator( '.wp-block-button > .wp-block-button__link' )
					.evaluateAll( ( elements ) =>
						elements.map( ( element ) => {
							const style = window.getComputedStyle( element );
							return {
								background: style.backgroundColor,
								color: style.color,
							};
						} )
					);

				const headers = await page
					.locator( 'h1, h2, h3, h4, h5, h6' )
					.evaluateAll( ( elements ) =>
						elements.map( ( element ) => {
							const style = window.getComputedStyle( element );
							return {
								background: style.backgroundColor,
								color: style.color,
							};
						} )
					);

				for ( const element of buttons ) {
					await expect( element.background ).toEqual(
						colorPalette.button.background
					);
				}

				for ( const element of paragraphs ) {
					expect(
						colorPalette.paragraph.color.includes( element.color )
					).toBe( true );
				}

				for ( const element of headers ) {
					expect(
						colorPalette.header.color.includes( element.color )
					).toBe( true );
				}
			}
		);

		test(
			'Create "your own" pickers should be visible',
			{ tag: tags.SKIP_ON_PRESSABLE },
			async ( { assemblerPageObject, baseURL }, testInfo ) => {
				testInfo.snapshotSuffix = '';
				const wordPressVersion = await getInstalledWordPressVersion();

				const assembler = await assemblerPageObject.getAssembler();
				const colorPicker = assembler.getByText( 'Create your own' );

				await colorPicker.click();

				// Check if Gutenberg is installed
				const apiContext = await request.newContext( {
					baseURL,
					extraHTTPHeaders: {
						Authorization: `Basic ${ encodeCredentials(
							admin.username,
							admin.password
						) }`,
						cookie: '',
					},
				} );
				const listPluginsResponse = await apiContext.get(
					`./wp-json/wp/v2/plugins`,
					{
						failOnStatusCode: true,
					}
				);
				const pluginsList = await listPluginsResponse.json();
				const gutenbergPlugin = pluginsList.find(
					( { textdomain } ) => textdomain === 'gutenberg'
				);

				const mapTypeFeatures = {
					background: [ 'solid', 'gradient' ],
					text: [],
					heading: [ 'text', 'background', 'gradient' ],
					button: [ 'text', 'background', 'gradient' ],
					link: [ 'default', 'hover' ],
					captions: [],
				};
				const mapTypeFeaturesGutenberg = {
					background: [ 'color', 'gradient' ],
					text: [],
					heading: [ 'text', 'background', 'gradient' ],
					button: [ 'text', 'background', 'gradient' ],
					link: [ 'default', 'hover' ],
					captions: [],
				};

				const customColorSelector =
					'.components-color-palette__custom-color-button';
				const gradientColorSelector =
					'.components-custom-gradient-picker__gradient-bar-background';

				const mapFeatureSelectors = {
					solid: customColorSelector,
					text: customColorSelector,
					background: customColorSelector,
					default: customColorSelector,
					hover: customColorSelector,
					gradient: gradientColorSelector,
				};
				const mapFeatureSelectorsGutenberg = {
					color: customColorSelector,
					text: customColorSelector,
					background: customColorSelector,
					default: customColorSelector,
					hover: customColorSelector,
					gradient: gradientColorSelector,
				};

				for ( const type of Object.keys( mapTypeFeatures ) ) {
					await assembler
						.locator(
							'.woocommerce-customize-store__color-panel-container'
						)
						.getByText( type )
						.click();

					// eslint-disable-next-line playwright/no-conditional-in-test
					if ( gutenbergPlugin || wordPressVersion >= 6.6 ) {
						for ( const feature of mapTypeFeaturesGutenberg[
							type
						] ) {
							const container = assembler.locator(
								'.block-editor-panel-color-gradient-settings__dropdown-content'
							);
							await container
								.getByRole( 'tab', {
									name: feature,
								} )
								.click();

							const selector =
								mapFeatureSelectorsGutenberg[ feature ];
							const featureSelector =
								container.locator( selector );

							await expect( featureSelector ).toBeVisible();
						}
					} else {
						for ( const feature of mapTypeFeatures[ type ] ) {
							const container = assembler.locator(
								'.block-editor-panel-color-gradient-settings__dropdown-content'
							);
							await container
								.getByRole( 'tab', {
									name: feature,
								} )
								.click();

							const selector = mapFeatureSelectors[ feature ];
							const featureSelector =
								container.locator( selector );

							await expect( featureSelector ).toBeVisible();
						}
					}
				}
			}
		);
	}
);
