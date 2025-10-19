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
			"Green",
		],
		variation: true,
		visible: true,
	},
	{
		name: "Size",
		options: [
			"S",
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
				option: "Green"
			},
			{
				name: "Size",
				option: "S"
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
	await editor.page.getByRole( 'menuitem', { name: 'Edit Site' } ).click();
	await editor.page.waitForLoadState();
	await editor.setPreferences( 'core/edit-site', {
		welcomeGuide: false,
	} );
	await editor.openDocumentSettingsSidebar();
}

async function switchCartBlockVersion ( editor, targetBlockVersion ) {
		const cartBlock = await editor.canvas.getByRole( 'document', { name: /Block: Add to Cart (with|\+) Options/ } );
		const isLegacyCart = await cartBlock.getAttribute( 'aria-label' ) === 'Block: Add to Cart with Options';

		await editor.selectBlocks( cartBlock );
		if ( isLegacyCart && targetBlockVersion === 'new' ) {
				await editor.page.getByRole( 'button', { name: 'Upgrade to the Add to Cart + Options block', exact: true } ).click();
		} else if ( ! isLegacyCart && targetBlockVersion === 'legacy' ) {
				await editor.page.getByRole( 'button', { name: 'Switch back' } ).click();
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
		await firstSaveButton.click();

		if ( ! isOnlyCurrentEntityDirty ) {
			await editor.page
				.getByLabel( /(Editor publish|Save panel)/ )
				.getByRole( 'button', {
					name: 'Save', exact: true
				} )
				.click();
		}
		await editor.page
			.getByRole('button', { name: 'Dismiss this notice' })
			.getByText(/(updated|published)\./)
			.first()
			.waitFor();
	}
}

async function setCartBlockAttributes(
	editor,
	{ optionStyle, autoselect, disabledAttributesAction } = {
		optionStyle: undefined,
		autoselect: false,
		disabledAttributesAction: 'disable',
	},
) {
	const page = editor.page;
	let isOnlyCurrentEntityDirty = true;
	await editor.selectBlocks( await editor.canvas.getByRole( 'document', { name: /Block: Add to Cart \+ Options/ } ) );

	await page.getByRole( 'button', { name: 'Switch product type' } ).click();
	await page.getByRole( 'menuitem', { name: 'Variable product' } ).click();
	await editor.canvas.getByLabel( 'Block: Add to Cart + Options' ).getByLabel( 'Block: Variation Selector: Attribute Options' ).first().click();
	const optionStyleInput = await page.getByRole( 'radio', { name: optionStyle, exact: true } )
	if ( ! await optionStyleInput.isChecked() ) {
		isOnlyCurrentEntityDirty = false;
	}
	await optionStyleInput.click();

	const autoselectInput = await page.getByRole( 'checkbox', { name: 'Auto-select when only one attribute is compatible' } )
	const disabledAttributesActionInput = await page.getByLabel( 'Values in conflict');

	if (
		await autoselectInput.isChecked() !== autoselect ||
		await disabledAttributesActionInput.inputValue() !== disabledAttributesAction
	) {
		isOnlyCurrentEntityDirty = false;
	}
	await autoselectInput.setChecked( autoselect );
	await disabledAttributesActionInput.selectOption( { value: disabledAttributesAction } );
	await saveBlockEditor( editor, isOnlyCurrentEntityDirty );
}

async function selectBlockAttribute(
	page,
	attributeName,
	attributeValue,
	optionStyle=undefined,
) {
	if ( optionStyle === 'Dropdown' ) {
		await page.getByLabel( attributeName ).selectOption( attributeValue );
	} else if ( optionStyle === 'Pills' ) {
		if ( attributeValue === '' ) {
			await page.getByLabel( attributeName ).locator( 'label:has(:checked)' ).click();
		} else {
			await page.getByLabel( attributeName ).getByText( attributeValue ).click();
		}
	} else {
		throw new Error( `Bad values for optionStyle (${ optionStyle })` );
	}
}

async function expectSelectedAttributes( page, expectedValues={}, optionStyle=undefined ) {
	for ( let { name: attributeName, options: attributeValues } of productAttributes ) {
		if ( optionStyle === 'Dropdown' ) {
			if ( attributeName in expectedValues && expectedValues[ attributeName ] !== '' ) {
				await expect(
					page.getByLabel( attributeName, { exact: true } )
				).toHaveValue( expectedValues[ attributeName ] );
			} else {
				await expect(
					page.getByLabel( attributeName, { exact: true } )
				).toHaveValue( '' );
			}
		} else if ( optionStyle === 'Pills' ) {
			if ( attributeName in expectedValues && expectedValues[ attributeName ] !== '' ) {
				attributeValues = attributeValues.filter( item => item !== expectedValues[ attributeName ] ); // Omit attributeName
				await expect (
					page.getByLabel( attributeName, { exact: true } ).getByLabel( expectedValues[ attributeName ], { exact: true } )
				).toBeChecked();
			}
			if ( attributeValues.length ) {
				for ( const attributeValue of attributeValues ) {
					await expect(
						page
							.getByLabel( attributeName, { exact: true } )
							.getByLabel( attributeValue, { exact: true } )
					).not.toBeChecked();
				}
			}
		} else {
			throw new Error( `Bad value for optionStyle (${ optionStyle })` );
		}
	}
}

test.describe(
	'Add to Cart autoselect behavior',
	{ tag: [] },
	() => {
		let productId;
		let commonSetupExecuted = false;

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

		test.beforeEach( async ( { page, editor, context } ) => {
			// Login
			await page.goto( 'wp-login.php' );
			await page.getByRole( 'textbox', { name: 'Username or Email Address' } ).fill( admin.username );
			await page.getByRole( 'textbox', { name: 'Password' } ).fill( admin.password );
			await page.getByRole( 'checkbox', { name: 'Remember Me' } ).check();
			await page.getByRole( 'button', { name: 'Log In' } ).click();
			await page.waitForURL( '**/wp-admin/' );

			// Tests expect to start in editor of product template
			await goToProductTemplateEditor( editor );

			if ( commonSetupExecuted ) {
				// Setup already done at suite level, skip template changes
			} else {
				await switchCartBlockVersion( editor, 'new' )
				await saveBlockEditor( editor, true );
			}
		} );

		test.afterEach( async ( { editor } ) => {
			if (commonSetupExecuted) {
				// leave the template intact until end of suite
			} else {
				await goToProductTemplateEditor( editor );
				await switchCartBlockVersion( editor, 'legacy' )
				await saveBlockEditor( editor, true );
			}
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
			'common setup',
			{ tag: [] },
			async ( { page, editor } ) => {
				// do any common setup here

				// This is only required to save execution time by skipping
				// common parts of setup/cleanup for each test.
				commonSetupExecuted = true;
			}
		);

		for ( const optionStyle of [ 'Pills', 'Dropdown' ] ) {
			test(
				`${ optionStyle }: Add to Cart + Options: Auto-select should work (on page load)`,
				{ tag: [] },
				async ( { page, editor } ) => {
					await test.step( `${ optionStyle }: Set the autoselect setting to false`, async () => {
						await setCartBlockAttributes( editor, { optionStyle: optionStyle, autoselect: false } );
					} );
					await test.step( `${ optionStyle }: Expect NOTHING to be auto-selected (on page load)`, async () => {
						await page.goto( productPermalink );

						await expectSelectedAttributes( page, { Type: '', Colour: '', Size: '' }, optionStyle );
					} );

					await test.step( `${ optionStyle }: Set the autoselect setting to true`, async () => {
						await goToProductTemplateEditor( editor );
						await setCartBlockAttributes( editor, { optionStyle: optionStyle, autoselect: true } );
					} );
					await test.step( `${ optionStyle }: Expect only the Type to be auto-selected (on page load)`, async () => {
						await page.goto( productPermalink );

						// Expect Size to be auto-selected (on page load) to "T-shirt", the rest of the attributes should not be selected.
						await expectSelectedAttributes( page, { Type: 'T-shirt', Colour: '', Size: '' }, optionStyle );
					} );
				}
			);
			test(
				`${ optionStyle }: Add to Cart + Options: Auto-select on user selection should work`,
				{ tag: [] },
				async ( { page, editor } ) => {
					await test.step( `${ optionStyle }: Set the autoselect setting to false`, async () => {
						await setCartBlockAttributes( editor, { optionStyle: optionStyle, autoselect: false } );
					} );
					await test.step( `${ optionStyle }: Expect attributes to NOT auto-select when user selects something`, async () => {
						await page.goto( productPermalink );

						// Expect nothing to be auto-selected
						await selectBlockAttribute( page, 'Colour', 'Blue', optionStyle );

						await expectSelectedAttributes( page, { Type: '', Colour: 'Blue', Size: '' }, optionStyle );
					} );

					await test.step( `${ optionStyle }: Set the autoselect setting to true`, async () => {
						await goToProductTemplateEditor( editor );
						await setCartBlockAttributes( editor, { optionStyle: optionStyle, autoselect: true } );
					} );
					await test.step( `${ optionStyle }: Expect attributes to auto-select when user selects something`, async () => {
						await page.goto( productPermalink );

						// By setting the Colour to "Blue", we expect the Type to be auto-selected to "T-shirt", and the Size to "XL".
						await selectBlockAttribute( page, 'Colour', 'Blue', optionStyle );

						await expectSelectedAttributes( page, { Type: 'T-shirt', Colour: 'Blue', Size: 'XL' }, optionStyle );
					} );
				}
			);
			test(
				`${ optionStyle }: Add to Cart + Options: Test the multiple choices of the Values in conflict setting`,
				{ tag: [] },
				async ( { page, editor } ) => {
					async function setDisabledAttributesAction( value ) {
						await test.step( `${ optionStyle }: Set the unattached_attribute_action setting to "${ value }"`, async () => {
							await setCartBlockAttributes( editor, { optionStyle: optionStyle, disabledAttributesAction: value } );
						} );
					}
					async function preselect() {
						await page.goto( productPermalink );

						// By setting the Colour to "Blue", the only possible Size remaining is "XL".
						await selectBlockAttribute( page, 'Colour', 'Blue', optionStyle );
					}

					await setDisabledAttributesAction( 'hide' );
					await test.step( `${ optionStyle }: Expect unattached options to be hidden (by attribute)`, async () => {
						await preselect();

						await expect(
							page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
						).not.toBeVisible();
					} );

					await goToProductTemplateEditor( editor );
					await setDisabledAttributesAction( 'disable' );
					await test.step( `${ optionStyle }: Expect unattached options to be disabled (by prop)`, async () => {
						await preselect();

						await expect(
							page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
						).toBeDisabled();
					} );
				}
			);
			test(
				`${ optionStyle }: Add to Cart + Options: Combining Auto-select on user selection and Values in conflict settings should work`,
				{ tag: [] },
				async ( { page, editor } ) => {
					async function preselect() {
						await page.goto( productPermalink );

						// By setting the Colour to "Blue", the only possible Size remaining is "XL".
						await selectBlockAttribute( page, 'Colour', 'Blue', optionStyle );
						// Now, we deselect the Colour.
						await selectBlockAttribute( page, 'Colour', '', optionStyle );
						// Now, the options should look like this:
						// Type: T-shirt
						// Colour: ''
						// Size: XL
						// Because the Size is XL, the only Colours possible are Red and Blue.
						// Now if we select Size: S, the Colour should auto-select to Green.
						await selectBlockAttribute( page, 'Size', 'S', optionStyle );
						// Now, the options should look like this:
						// Type: T-shirt
						// Colour: Green
						// Size: S
					}

					for ( const value of [ 'hide', 'disable' ] ) {
						await goToProductTemplateEditor( editor );

						await test.step( `${ optionStyle }: Set the disabled_attribute_action setting to "${ value }"`, async () => {
							await setCartBlockAttributes( editor, { autoselect: true, optionStyle: optionStyle, disabledAttributesAction: value } );
						} );
						await test.step( `unattachedAttributesAction === ${ value }: Expect options to be properly auto-selected`, async () => {
							await preselect();

							await expectSelectedAttributes( page, { Type: 'T-shirt', Colour: 'Green', Size: 'S' }, optionStyle );
						} );
					}
				}
			);
		}

		test(
			'common cleanup',
			{ tag: [] },
			async ( { page, editor } ) => {
				// do any common cleanup here
				commonSetupExecuted = false;
			}
		);
	}
);
