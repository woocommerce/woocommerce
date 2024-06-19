const { test, expect } = require( '@playwright/test' );
const { variableProducts: utils } = require( '../../../../utils' );
const {
	createVariableProduct,
	showVariableProductTour,
	deleteProductsAddedByTests,
	productAttributes,
} = utils;

let productId;

/**
 *
 * @param {import('@playwright/test').Page} page
 */
const step_goToAttributesTab = async ( page ) => {
	await test.step( 'Go to the "Attributes" tab.', async () => {
		const attributesTab = page
			.locator( '.attribute_tab' )
			.getByRole( 'link', { name: 'Attributes' } );

		await attributesTab.click();
	} );
};

test.describe.configure( { mode: 'serial' } );

test.describe( 'Add product attributes', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		productId = await createVariableProduct();

		await showVariableProductTour( browser, false );
	} );

	test.afterAll( async () => {
		await deleteProductsAddedByTests();
	} );

	test( 'can add custom product attributes', async ( { page } ) => {
		const textbox_attributeName =
			page.getByPlaceholder( 'f.e. size or color' );
		const textbox_attributeValues = page.getByPlaceholder(
			'Enter options for customers to choose from'
		);
		const checkbox_visible = page
			.getByText( 'Visible on the product page' )
			.getByRole( 'checkbox' );
		const checkbox_variations = page
			.getByText( 'Used for variations' )
			.getByRole( 'checkbox' );

		await test.step( `Open "Edit product" page of product id ${ productId }`, async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId }&action=edit`
			);
		} );

		await step_goToAttributesTab( page );

		for ( let i = 0; i < productAttributes.length; i++ ) {
			const attribute = productAttributes[ i ];
			const attributeName = attribute.name;
			const attributeValues = attribute.options.join( ' | ' );

			if ( i > 0 ) {
				await test.step( "Click 'Add new'.", async () => {
					await page
						.getByRole( 'button', { name: 'Add new' } )
						.click();

					await expect(
						page
							.getByRole( 'heading', {
								name: 'New attribute',
							} )
							.first()
					).toBeVisible();
				} );
			}

			await test.step( `Add the attribute "${ attributeName }" with values "${ attributeValues }"`, async () => {
				await test.step( `Type "${ attributeName }" in the "Attribute name" input field.`, async () => {
					await textbox_attributeName.last().type( attributeName );
				} );

				await test.step( `Type the attribute values "${ attributeValues }".`, async () => {
					await textbox_attributeValues
						.last()
						.type( attributeValues );
				} );

				await test.step( `Expect "Visible on the product page" checkbox to be checked by default`, async () => {
					await expect( checkbox_visible.last() ).toBeChecked();
				} );

				await test.step( `Expect "Used for variations" checkbox to be checked by default`, async () => {
					await expect( checkbox_variations.last() ).toBeChecked();
				} );

				await test.step( 'Click "Save attributes".', async () => {
					await page
						.getByRole( 'button', {
							name: 'Save attributes',
						} )
						.click();
				} );

				await test.step( "Wait for the tour's dismissal to be saved", async () => {
					await page.waitForResponse(
						( response ) =>
							response.url().includes( '/post.php' ) &&
							response.status() === 200
					);
				} );

				await test.step( `Wait for the loading overlay to disappear.`, async () => {
					await expect(
						page.locator( '.blockOverlay' )
					).toBeHidden();
				} );
			} );
		}

		await test.step( `Click "Update".`, async () => {
			// "Update" triggers a lot of requests. Wait for the final one to complete before proceeding.
			// Otherwise, succeeding steps would be flaky.
			const finalRequestResolution = page.waitForResponse( ( response ) =>
				response
					.url()
					.includes(
						'options=woocommerce_task_list_reminder_bar_hidden'
					)
			);
			await page.getByRole( 'button', { name: 'Update' } ).click();

			const response = await finalRequestResolution;
			expect( response.ok() ).toBeTruthy();
		} );

		await step_goToAttributesTab( page );

		for ( let j = 0; j < productAttributes.length; j++ ) {
			const attribute = productAttributes[ j ];
			const attributeName = attribute.name;
			const attributeValues = attribute.options.join( ' | ' );

			await test.step( `Expect "${ attributeName }" to appear on the list of saved attributes, and expand it.`, async () => {
				const heading_attributeName = page
					.getByRole( 'heading', {
						name: attributeName,
					} )
					.last();

				await expect( heading_attributeName ).toBeVisible();
				await heading_attributeName.click();
			} );

			await test.step( `Expect its details to be saved correctly`, async () => {
				await expect( textbox_attributeName.nth( j ) ).toHaveValue(
					attributeName
				);
				await expect( textbox_attributeValues.nth( j ) ).toHaveValue(
					attributeValues
				);
				await expect( checkbox_visible.nth( j ) ).toBeChecked();
				await expect( checkbox_variations.nth( j ) ).toBeChecked();
			} );
		}
	} );
} );
