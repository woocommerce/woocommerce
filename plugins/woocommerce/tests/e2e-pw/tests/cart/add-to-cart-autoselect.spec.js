/**
 * External dependencies
 */
import {
	addAProductToCart,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

import { Editor, test as test_wp } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test as test_wc, expect } from '../../fixtures/fixtures';
import { admin } from '../../test-data/data';

const test = Object.assign( test_wc, test_wp );

const productName = `Cart product test ${ Date.now() }`;
const productPrice = '13.99';
let productPermalink;
const productAttributes = [
	{
		name: "Type",
		options: [
			"T-shirt",
		],
		variation: true,
		visible: true,
	},
	{
		name: "Colour",
		options: [
			"Red",
			"Blue",
		],
		variation: true,
		visible: true,
	},
	{
		name: "Size",
		options: [
			"L",
			"XL",
		],
		variation: true,
		visible: true,
	},
]
const productVariations = [
	{
		regular_price: productPrice,
		attributes: [
			{
				name: "Type",
				option: "T-shirt"
			},
			{
				name: "Colour",
				option: "Red"
			},
			{
				name: "Size",
				option: "L"
			},
		]
	},
	{
		regular_price: productPrice,
		attributes: [
			{
				name: "Type",
				option: "T-shirt"
			},
			{
				name: "Colour",
				option: "Red"
			},
			{
				name: "Size",
				option: "XL"
			},
		]
	},
	{
		regular_price: productPrice,
		attributes: [
			{
				name: "Type",
				option: "T-shirt"
			},
			{
				name: "Colour",
				option: "Blue"
			},
			{
				name: "Size",
				option: "XL"
			},
		]
	},
]

async function goToProductTemplateEditor ( editor ) {
	await editor.page.goto( productPermalink );
	await editor.page.getByRole('menuitem', { name: 'Edit Site' }).click();
	await editor.page.waitForLoadState();
	await editor.setPreferences( 'core/edit-site', {
		welcomeGuide: false,
	} );
}

async function switchCartBlockVersion ( editor, targetBlockVersion ) {
	const cartBlock = await editor.canvas.getByRole( 'document', { name: /Block: Add to Cart (with|\+) Options/ } );
	const isLegacyCart = await cartBlock.getAttribute( 'aria-label' ) === 'Block: Add to Cart with Options';

	await editor.selectBlocks( cartBlock );
	if ( isLegacyCart && targetBlockVersion === 'new' ) {
		await editor.page.getByRole('button', { name: 'Upgrade to the Add to Cart + Options block', exact: true }).click();
	} else if ( ! isLegacyCart && targetBlockVersion === 'legacy' ) {
		await editor.page.getByRole('button', { name: 'Switch back' }).click();
	}
}

async function saveBlockEditor( editor, isOnlyCurrentEntityDirty ) {
	const firstSaveButton = await editor.page.getByRole( 'region', {
		name: 'Editor top bar',
	} )
	.getByRole( 'button', {
		name: 'Save',
		exact: true,
	} )

	if ( await firstSaveButton.isEnabled() ) {
		firstSaveButton.click();

		const secondSaveButton = await editor.page
			.getByLabel( /(Editor publish|Save panel)/ )
			.getByRole( 'button', {
				name: 'Save', exact: true
			} );
		try {
			// If the second save button is present, click it.
			await secondSaveButton.waitFor( { timeout: 1000 } );
			secondSaveButton.click();
		} catch {}

		await editor.page
			.getByRole('button', { name: 'Dismiss this notice' })
			.getByText(/(updated|published)\./)
			.first()
			.waitFor();
	}
}

async function setCartBlockAttributes(
	editor,
	targetBlockVersion,
	{ optionStyle, autoselect, autoselectOnPageLoad, disabledAttributesAction } = {
		optionStyle: undefined,
		autoselect: false,
		autoselectOnPageLoad: false,
		disabledAttributesAction: undefined,
	}
) {
	const page = editor.page;
	if ( disabledAttributesAction === undefined ) {
		if ( targetBlockVersion === 'legacy' ) {
			disabledAttributesAction = 'hide';
		} else if ( targetBlockVersion === 'new' ) {
			disabledAttributesAction = 'gray';
		}
	}
	await goToProductTemplateEditor( editor );
	await editor.openDocumentSettingsSidebar();
	await switchCartBlockVersion( editor, targetBlockVersion );

	if ( targetBlockVersion === 'new' ) {
		await page.getByRole( 'button', { name: 'Switch product type' } ).click();
		await page.getByRole( 'menuitem', { name: 'Variable product' } ).click();
		await editor.canvas.getByLabel( 'Block: Add to Cart + Options' ).getByLabel( 'Block: Variation Selector: Attribute Options' ).first().click();
		await page.getByRole( 'radio', { name: optionStyle, exact: true } ).click();
	}

	await page.getByRole( 'checkbox', { name: 'Auto-select other attributes' } ).setChecked( autoselect );
	await page.getByRole( 'checkbox', { name: 'Auto-select on page load' } ).setChecked( autoselectOnPageLoad );
	await page.getByLabel( 'Values in conflict').selectOption( { value: disabledAttributesAction } );
	await saveBlockEditor( editor, targetBlockVersion === 'new' );
}

test.describe(
	'Add to Cart autoselect behavior',
	{ tag: [] },
	() => {
		let productId;
		let variationIds = [];

		test.beforeAll( async ( { restApi } ) => {
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: productName,
					type: 'variable',
					attributes: productAttributes,
				} )
				.then( ( response ) => {
					productId = response.data.id;
					productPermalink = response.data.permalink;
				} );
			for ( const variation of productVariations ) {
				await restApi
					.post( `${ WC_API_PATH }/products/${ productId }/variations`, variation );
			}
		} );

		test.beforeEach( async ( { page, context } ) => {
			// Login
			await page.goto( 'wp-login.php' );
			await page.getByRole( 'textbox', { name: 'Username or Email Address' } ).fill( admin.username );
			await page.getByRole( 'textbox', { name: 'Password' } ).fill( admin.password );
			await page.getByRole( 'checkbox', { name: 'Remember Me' } ).check();
			await page.getByRole( 'button', { name: 'Log In' } ).click();
			await page.waitForURL( '**/wp-admin/' );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: [ productId ],
			} );
		} );

		test.use( {
			editor: async ( { page }, use ) => {
				await use( new Editor( { page } ) );
			},
		} );

		test(
			'Add to Cart with Options: Autoselect on page load should work',
			{ tag: [] },
			async ( { page, editor } ) => {
				await test.step( 'Set the autoselect_on_page_load setting to true', async () => {
					await setCartBlockAttributes( editor, 'legacy', { autoselectOnPageLoad: true } );
				} );
				await test.step( 'Expect only the Type to be auto-selected (on page load)', async () => {
					await page.goto( productPermalink );

					// Expect Size to be auto-selected (on page load) to "T-shirt", the rest of the attributes should not be selected.
					await expect(
						page.getByLabel( 'Type' )
					).toHaveValue( 'T-shirt' );
					await expect(
						page.getByLabel( 'Colour' )
					).toHaveValue( '' );
					await expect(
						page.getByLabel( 'Size' )
					).toHaveValue( '' );
				} );
			}
		);
		test(
			'Add to Cart with Options: Autoselect on user selection should work',
			{ tag: [] },
			async ( { page, editor } ) => {
				await test.step( 'Set the autoselect setting to true', async () => {
					await setCartBlockAttributes( editor, 'legacy', { autoselect: true } );
				} );
				await test.step( 'Expect attributes to auto-select when user selects something', async () => {
					await page.goto( productPermalink );

					// By setting the Colour to "Blue", we expect the Type to be auto-selected to "T-shirt", and the Size to "XL".
					await page.getByLabel( 'Colour' ).selectOption( 'Blue' );

					await expect(
						page.getByLabel( 'Type' )
					).toHaveValue( 'T-shirt' );
					await expect(
						page.getByLabel( 'Colour' )
					).toHaveValue( 'Blue' );
					await expect(
						page.getByLabel( 'Size' )
					).toHaveValue( 'XL' );
				} );
			}
		);
		test(
			'Add to Cart with Options: Test the multiple choices of the Values in conflict setting',
			{ tag: [] },
			async ( { page, editor } ) => {
				async function legacyCartSetUnattachedAttributesAction( value ) {
					await test.step( `Set the unattached_attribute_action setting to "${ value }"`, async () => {
						await setCartBlockAttributes( editor, 'legacy', { disabledAttributesAction: value } );
					} );
				}
				async function preselect() {
					await page.goto( productPermalink );

					// By setting the Colour to "Blue", the only possible Size remaining is "XL".
					await page.getByLabel( 'Colour' ).selectOption( 'Blue' );
				}

				await legacyCartSetUnattachedAttributesAction( 'hide' );
				await test.step( 'Expect unattached options to be hidden (temporarily deleted)', async () => {
					await preselect();

					await expect(
						page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
					).toHaveCount( 0 );
				} );


				await legacyCartSetUnattachedAttributesAction( 'disable' );
				await test.step( 'Expect unattached options to be disabled (by prop)', async () => {
					await preselect();

					await expect(
						page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
					).toBeDisabled();
				} );

				await legacyCartSetUnattachedAttributesAction( 'gray' );
				await test.step( 'Expect unattached options to be disabled (by class)', async () => {
					await preselect();

					await expect(
						page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
					).toHaveClass( /disabled/ ); // Replace with .toContainClass( 'disabled' ) (playwright 1.52 and above)
				} );
			}
		);

		for ( const optionStyle of [ 'Pills', 'Dropdown' ] ) {
			test(
				`${ optionStyle }: Add to Cart + Options: Autoselect on page load should work`,
				{ tag: [] },
				async ( { page, editor } ) => {
					await test.step( `${ optionStyle }: Set the autoselect_on_page_load setting to true`, async () => {
						await setCartBlockAttributes( editor, 'new', { optionStyle: optionStyle, autoselectOnPageLoad: true } );
					} );
					await test.step( `${ optionStyle }: Expect only the Type to be auto-selected (on page load)`, async () => {
						await page.goto( productPermalink );

						// Expect Size to be auto-selected (on page load) to "T-shirt", the rest of the attributes should not be selected.
						if ( optionStyle === 'Pills' ) {
							await expect(
								page.getByLabel( 'Type' )
									.getByLabel( 'T-Shirt' )
							).toBeChecked();
							await expect(
								page.getByLabel( 'Colour' )
									.getByLabel( 'Red' )
							).not.toBeChecked();
							await expect(
								page.getByLabel( 'Colour' )
									.getByLabel( 'Blue' )
							).not.toBeChecked();
							await expect(
								page.getByLabel( 'Size' )
									.getByLabel( 'L', { exact: true } )
							).not.toBeChecked();
							await expect(
								page.getByLabel( 'Size' )
									.getByLabel( 'XL' )
							).not.toBeChecked();
						} else {
							await expect(
								page.getByLabel( 'Type' )
							).toHaveValue( 'T-shirt' )
							await expect(
								page.getByLabel( 'Colour' )
							).toHaveValue( '' );
							await expect(
								page.getByLabel( 'Size' )
							).toHaveValue( '' );
						}
					} );
				}
			);
			test(
				`${ optionStyle }: Add to Cart + Options: Autoselect on user selection should work`,
				{ tag: [] },
				async ( { page, editor } ) => {
					await test.step( `${ optionStyle }: Set the autoselect setting to true`, async () => {
						await setCartBlockAttributes( editor, 'new', { optionStyle: optionStyle, autoselect: true } );
					} );
					await test.step( `${ optionStyle }: Expect attributes to auto-select when user selects something`, async () => {
						await page.goto( productPermalink );

						// By setting the Colour to "Blue", we expect the Type to be auto-selected to "T-shirt", and the Size to "XL".
						if ( optionStyle === 'Pills' ) {
							await page.getByLabel( 'Colour' ).getByText( 'Blue' ).click();
						} else if ( optionStyle === 'Dropdown' ) {
							await page.getByLabel( 'Colour' ).selectOption( 'Blue' );
						}

						if ( optionStyle === 'Pills' ) {
							await expect(
								page.getByLabel( 'Type' )
									.getByLabel( 'T-Shirt' )
							).toBeChecked();
							await expect(
								page.getByLabel( 'Colour' )
									.getByLabel( 'Blue' )
							).toBeChecked();
							await expect(
								page.getByLabel( 'Size' )
									.getByLabel( 'XL' )
							).toBeChecked();
						} else {
							await expect(
								page.getByLabel( 'Type' )
							).toHaveValue( 'T-shirt' );
							await expect(
								page.getByLabel( 'Colour' )
							).toHaveValue( 'Blue' );
							await expect(
								page.getByLabel( 'Size' )
							).toHaveValue( 'XL' );
						}
					} );
				}
			);
			test(
				`${ optionStyle }: Add to Cart + Options: Test the multiple choices of the Values in conflict setting`,
				{ tag: [] },
				async ( { page, editor } ) => {
					async function newCartSetDisabledAttributesAction( value ) {
						await test.step( `${ optionStyle }: Set the unattached_attribute_action setting to "${ value }"`, async () => {
							await setCartBlockAttributes( editor, 'new', { optionStyle: optionStyle, disabledAttributesAction: value } );
						} );
					}
					async function preselect() {
						await page.goto( productPermalink );

						// By setting the Colour to "Blue", the only possible Size remaining is "XL".
						if ( optionStyle === 'Pills' ) {
							await page.getByLabel( 'Colour' ).getByText( 'Blue' ).click();
						} else if ( optionStyle === 'Dropdown' ) {
							await page.getByLabel( 'Colour' ).selectOption( 'Blue' );
						}
					}

					await newCartSetDisabledAttributesAction( 'hide' );
					await test.step( `${ optionStyle }: Expect unattached options to be hidden (by attribute)`, async () => {
						await preselect();

						await expect(
							page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
						).not.toBeVisible();
					} );

					await newCartSetDisabledAttributesAction( 'disable' );
					await test.step( `${ optionStyle }: Expect unattached options to be disabled (by prop)`, async () => {
						await preselect();

						await expect(
							page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
						).toBeDisabled();
					} );

					await newCartSetDisabledAttributesAction( 'gray' );
					await test.step( `${ optionStyle }: Expect unattached options to be disabled (by class)`, async () => {
						await preselect();

						await expect(
							optionStyle === 'Pills'
							? page.getByLabel( 'Size' ).getByLabel( 'L', { exact: true } )
							: page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
						).toHaveClass( /disabled/ ); // Replace with .toContainClass( 'disabled' ) (playwright 1.52 and above)
					} );
				}
			);
		}
	}
);
