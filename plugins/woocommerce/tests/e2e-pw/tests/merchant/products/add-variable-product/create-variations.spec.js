const { test, expect } = require( '@playwright/test' );
const { variableProducts: utils } = require( '../../../../utils' );
const {
	createVariableProduct,
	showVariableProductTour,
	deleteProductsAddedByTests,
	generateVariationsFromAttributes,
	productAttributes,
} = utils;

let expectedGeneratedVariations,
	productId_addManually,
	productId_generateVariations,
	variationsToManuallyCreate;

test.describe( 'Add variations', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		productId_generateVariations = await createVariableProduct(
			productAttributes
		);

		productId_addManually = await createVariableProduct(
			productAttributes
		);

		expectedGeneratedVariations =
			generateVariationsFromAttributes( productAttributes );

		variationsToManuallyCreate = expectedGeneratedVariations.slice( 0, 3 );

		await showVariableProductTour( browser, false );
	} );

	test.afterAll( async () => {
		await deleteProductsAddedByTests();
	} );

	test( 'can generate variations from product attributes', async ( {
		page,
	} ) => {
		await test.step( `Open "Edit product" page of product id ${ productId_generateVariations }`, async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_generateVariations }&action=edit`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.locator( '.variations_tab' ).click();
		} );

		await test.step( 'Click on the "Generate variations" button.', async () => {
			// event listener for handling the link_all_variations confirmation dialog
			page.on( 'dialog', ( dialog ) => dialog.accept() );

			await page.locator( 'button.generate_variations' ).click();
		} );

		await test.step( `Expect the number of variations to be ${ expectedGeneratedVariations.length }`, async () => {
			const variations = page.locator( '.woocommerce_variation' );

			await expect( variations ).toHaveCount(
				expectedGeneratedVariations.length
			);
		} );

		for ( const variation of expectedGeneratedVariations ) {
			await test.step( `Expect the variation "${ variation.join(
				', '
			) }" to be generated.`, async () => {
				let variationRow = page.locator( '.woocommerce_variation h3' );

				for ( const attributeValue of variation ) {
					variationRow = variationRow.filter( {
						has: page.locator( 'option[selected]', {
							hasText: attributeValue,
						} ),
					} );
				}

				await expect( variationRow ).toBeVisible();
			} );
		}
	} );

	test( 'can manually add a variation', async ( { page } ) => {
		await test.step( `Open "Edit product" page of product id ${ productId_addManually }`, async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_addManually }&action=edit`
			);
		} );

		// hook up the woocommerce_variations_added jQuery trigger so we can check if it's fired
		await test.step( 'Hook up the woocommerce_variations_added jQuery trigger', async () => {
			await page.evaluate( () => {
				window.woocommerceVariationsAddedFunctionCalls = [];

				window
					.jQuery( '#variable_product_options' )
					.on( 'woocommerce_variations_added', ( event, data ) => {
						window.woocommerceVariationsAddedFunctionCalls.push( [
							event,
							data,
						] );
					} );
			} );
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.locator( '.variations_tab' ).click();
		} );

		await test.step( `Manually add ${ variationsToManuallyCreate.length } variations`, async () => {
			const variationRows = page.locator( '.woocommerce_variation h3' );
			let variationRowsCount = await variationRows.count();
			const originalVariationRowsCount = variationRowsCount;

			for ( const variationToCreate of variationsToManuallyCreate ) {
				await test.step( 'Click "Add manually"', async () => {
					const addManuallyButton = page.getByRole( 'button', {
						name: 'Add manually',
					} );

					await addManuallyButton.click();

					await expect( variationRows ).toHaveCount(
						++variationRowsCount
					);

					// verify that the woocommerce_variations_added jQuery trigger was fired
					const woocommerceVariationsAddedFunctionCalls =
						await page.evaluate(
							() => window.woocommerceVariationsAddedFunctionCalls
						);
					expect(
						woocommerceVariationsAddedFunctionCalls.length
					).toEqual(
						variationRowsCount - originalVariationRowsCount
					);
				} );

				for ( const attributeValue of variationToCreate ) {
					const attributeName = productAttributes.find(
						( { options } ) => options.includes( attributeValue )
					).name;
					const addAttributeMenu = variationRows
						.nth( 0 )
						.locator( 'select', {
							has: page.locator( 'option', {
								hasText: attributeValue,
							} ),
						} );

					await test.step( `Select "${ attributeValue }" from the "${ attributeName }" attribute menu`, async () => {
						await addAttributeMenu.selectOption( attributeValue );
					} );
				}

				await test.step( 'Click "Save changes"', async () => {
					await page
						.getByRole( 'button', {
							name: 'Save changes',
						} )
						.click();
				} );

				await test.step( `Expect the variation ${ variationToCreate.join(
					', '
				) } to be successfully saved.`, async () => {
					let newlyAddedVariationRow;

					for ( const attributeValue of variationToCreate ) {
						newlyAddedVariationRow = (
							newlyAddedVariationRow || variationRows
						).filter( {
							has: page.locator( 'option[selected]', {
								hasText: attributeValue,
							} ),
						} );
					}

					await expect( newlyAddedVariationRow ).toBeVisible();
				} );
			}
		} );
	} );
} );
