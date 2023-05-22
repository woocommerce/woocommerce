const { test, expect } = require( '@playwright/test' );
const { variableProducts: utils } = require( '../../../../utils' );
const {
	createVariableProduct,
	showVariableProductTour,
	deleteProductsAddedByTests,
	productAttributes,
} = utils;
const { getTextForLanguage } = require( './../../../../test-data/data' );


let productId;

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
		await test.step(
			`Open "Edit product" page of product id ${ productId }`,
			async () => {
				await page.goto(
					`/wp-admin/post.php?post=${ productId }&action=edit`
				);
			}
		);

		await test.step( 'Go to the "Attributes" tab.', async () => {
			const attributesTab = page
				.locator( '.attribute_tab' )
				.getByRole( 'link', { name: getTextForLanguage()['Attributes']} );

			await attributesTab.click();
		} );

		await test.step(
			`Add ${ productAttributes.length } attributes.`,
			async () => {
				for ( let i = 0; i < productAttributes.length; i++ ) {
					const attributeName = productAttributes[ i ].name;
					const attributeValues = productAttributes[ i ].options.join(
						'|'
					);

					if ( i > 0 ) {
						await test.step( "Click 'Add new'.", async () => {
							await page
								.locator( '#product_attributes .toolbar-top' )
								.getByRole( 'button', { name: getTextForLanguage()['Addnew']} )
								.click();
						} );
					}

					await test.step(
						`Add the attribute "${ attributeName }" with values "${ attributeValues }"`,
						async () => {
							await test.step(
								`Type "${ attributeName }" in the "Attribute name" input field.`,
								async () => {
									await page
										.getByPlaceholder(
											getTextForLanguage()['fesizeorcolor']
										)
										.nth( i )
										.type( attributeName );
								}
							);

							await test.step(
								`Type the attribute values "${ attributeValues }".`,
								async () => {
									await page
										.getByPlaceholder(
											getTextForLanguage()['Enteroptionsforcustomerstochoosefrom']
										)
										.nth( i )
										.type( attributeValues );
								}
							);

							await test.step(
								'Click "Save attributes".',
								async () => {
									await page
										.getByRole( 'button', {
											name: getTextForLanguage()['Saveattributes'],
										} )
										.click( { clickCount: 3 } );
								}
							);

							await test.step(
								"Wait for the tour's dismissal to be saved",
								async () => {
									await page.waitForResponse(
										( response ) =>
											response
												.url()
												.includes( '/post.php' ) &&
											response.status() === 200
									);
								}
							);

							await test.step(
								`Expect the attribute "${ attributeName }" to be saved`,
								async () => {
									const savedAttributeHeading = page.getByRole(
										'heading',
										{ name: attributeName }
									);

									await expect(
										savedAttributeHeading
									).toBeVisible();
								}
							);
						}
					);
				}
			}
		);
	} );
} );
