const { test, expect } = require( '@playwright/test' );
const { variableProducts: utils } = require( '../../../../utils' );
const {
	createVariableProduct,
	showVariableProductTour,
	deleteProductsAddedByTests,
	productAttributes,
	sampleVariations,
	createVariations,
} = utils;
const variationOnePrice = '9.99';
const variationTwoPrice = '11.99';
const variationThreePrice = '20.00';
const productWeight = '200';
const productLength = '10';
const productWidth = '20';
const productHeight = '15';
const stockAmount = '100';
const lowStockAmount = '10';

let productId_indivEdit,
	productId_bulkEdit,
	productId_deleteAll,
	productId_manageStock,
	productId_variationDefaults,
	productId_removeVariation,
	defaultVariation,
	variationIds_indivEdit;

test.describe( 'Update variations', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		await test.step( 'Create variable product for individual edit test', async () => {
			productId_indivEdit = await createVariableProduct(
				productAttributes
			);

			variationIds_indivEdit = await createVariations(
				productId_indivEdit,
				sampleVariations
			);
		} );

		await test.step( 'Create variable product for bulk edit test', async () => {
			productId_bulkEdit = await createVariableProduct(
				productAttributes
			);

			await createVariations( productId_bulkEdit, sampleVariations );
		} );

		await test.step( 'Create variable product for "delete all" test', async () => {
			productId_deleteAll = await createVariableProduct(
				productAttributes
			);

			await createVariations( productId_deleteAll, sampleVariations );
		} );

		await test.step( 'Create variable product for "manage stock" test', async () => {
			productId_manageStock = await createVariableProduct(
				productAttributes
			);

			const variation = sampleVariations.slice( -1 );

			await createVariations( productId_manageStock, variation );
		} );

		await test.step( 'Create variable product for "variation defaults" test', async () => {
			productId_variationDefaults = await createVariableProduct(
				productAttributes
			);

			await createVariations(
				productId_variationDefaults,
				sampleVariations
			);

			defaultVariation = sampleVariations[ 1 ].attributes;
		} );

		await test.step( 'Create variable product with 1 variation for "remove variation" test', async () => {
			productId_removeVariation = await createVariableProduct(
				productAttributes
			);

			await createVariations(
				productId_removeVariation,
				sampleVariations.slice( -1 )
			);
		} );

		await test.step( 'Hide variable product tour', async () => {
			await showVariableProductTour( browser, false );
		} );
	} );

	test.afterAll( async () => {
		await deleteProductsAddedByTests();
	} );

	test( 'can individually edit variations', async ( { page } ) => {
		const variationRows = page.locator( '.woocommerce_variation' );
		const firstVariation = variationRows.filter( {
			hasText: `#${ variationIds_indivEdit[ 0 ] }`,
		} );
		const secondVariation = variationRows.filter( {
			hasText: `#${ variationIds_indivEdit[ 1 ] }`,
		} );
		const thirdVariation = variationRows.filter( {
			hasText: `#${ variationIds_indivEdit[ 2 ] }`,
		} );

		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_indivEdit }&action=edit#variable_product_options`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page
				.getByRole( 'link', { name: 'Variations' } )
				.last()
				.click();
		} );

		await test.step( 'Expand all variations.', async () => {
			await page.getByRole( 'link', { name: 'Expand' } ).first().click();
		} );

		await test.step( 'Edit the first variation.', async () => {
			await test.step( 'Check the "Virtual" checkbox.', async () => {
				await firstVariation
					.getByRole( 'checkbox', {
						name: 'Virtual',
					} )
					.check();
			} );

			await test.step( `Set regular price to "${ variationOnePrice }".`, async () => {
				await firstVariation
					.getByRole( 'textbox', { name: 'Regular price' } )
					.fill( variationOnePrice );
			} );
		} );

		await test.step( 'Edit the second variation.', async () => {
			await test.step( 'Check the "Virtual" checkbox.', async () => {
				await secondVariation
					.getByRole( 'checkbox', {
						name: 'Virtual',
					} )
					.check();
			} );

			await test.step( `Set regular price to "${ variationTwoPrice }".`, async () => {
				await secondVariation
					.getByRole( 'textbox', { name: 'Regular price' } )
					.fill( variationTwoPrice );
			} );
		} );

		await test.step( 'Edit the third variation.', async () => {
			await test.step( 'Check "Manage stock?"', async () => {
				await thirdVariation
					.getByRole( 'checkbox', { name: 'Manage stock?' } )
					.check();
			} );

			await test.step( `Set regular price to "${ variationThreePrice }".`, async () => {
				await thirdVariation
					.getByRole( 'textbox', { name: 'Regular price' } )
					.fill( variationThreePrice );
			} );

			await test.step( 'Set the weight and dimensions.', async () => {
				await thirdVariation
					.getByRole( 'textbox', { name: 'Weight' } )
					.type( productWeight );

				await thirdVariation
					.getByRole( 'textbox', { name: 'Length' } )
					.type( productLength );

				await thirdVariation
					.getByRole( 'textbox', { name: 'Width' } )
					.type( productWidth );

				await thirdVariation
					.getByRole( 'textbox', { name: 'Height' } )
					.type( productHeight );
			} );
		} );

		await test.step( 'Click "Save changes".', async () => {
			const saveButton = page.getByRole( 'button', {
				name: 'Save changes',
			} );
			await saveButton.click();
			await expect( saveButton ).toBeDisabled();
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page
				.getByRole( 'link', { name: 'Variations' } )
				.last()
				.click();
		} );

		await test.step( 'Expand all variations.', async () => {
			await page.getByRole( 'link', { name: 'Expand' } ).first().click();
		} );

		await test.step( 'Expect the first variation to be virtual.', async () => {
			await expect(
				firstVariation.getByRole( 'checkbox', {
					name: 'Virtual',
				} )
			).toBeChecked();
		} );

		await test.step( `Expect the regular price of the first variation to be "${ variationOnePrice }".`, async () => {
			await expect(
				firstVariation.getByRole( 'textbox', {
					name: 'Regular price',
				} )
			).toHaveValue( variationOnePrice );
		} );

		await test.step( 'Expect the second variation to be virtual.', async () => {
			await expect(
				secondVariation.getByRole( 'checkbox', {
					name: 'Virtual',
				} )
			).toBeChecked();
		} );

		await test.step( `Expect the regular price of the second variation to be "${ variationTwoPrice }".`, async () => {
			await expect(
				secondVariation.getByRole( 'textbox', {
					name: 'Regular price',
				} )
			).toHaveValue( variationTwoPrice );
		} );

		await test.step( 'Expect the "Manage stock?" checkbox of the third variation to be checked.', async () => {
			await expect(
				thirdVariation.getByRole( 'checkbox', {
					name: 'Manage stock?',
				} )
			).toBeChecked();
		} );

		await test.step( `Expect the regular price of the third variation to be "${ variationThreePrice }".`, async () => {
			await expect(
				thirdVariation.getByRole( 'textbox', {
					name: 'Regular price',
				} )
			).toHaveValue( variationThreePrice );
		} );

		await test.step( 'Expect the weight and dimensions of the third variation to be correct.', async () => {
			await expect(
				thirdVariation.getByRole( 'textbox', {
					name: 'Weight',
				} )
			).toHaveValue( productWeight );

			await expect(
				thirdVariation.getByRole( 'textbox', {
					name: 'Length',
				} )
			).toHaveValue( productLength );

			await expect(
				thirdVariation.getByRole( 'textbox', { name: 'Width' } )
			).toHaveValue( productWidth );

			await expect(
				thirdVariation.getByRole( 'textbox', {
					name: 'Height',
				} )
			).toHaveValue( productHeight );
		} );
	} );

	test( 'can bulk edit variations', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_bulkEdit }&action=edit#variable_product_options`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page
				.getByRole( 'link', { name: 'Variations' } )
				.last()
				.click();
		} );

		await test.step( 'Select the \'Toggle "Downloadable"\' bulk action.', async () => {
			await page
				.locator( '#field_to_edit' )
				.selectOption( 'toggle_downloadable' );
		} );

		await test.step( 'Expand all variations.', async () => {
			await page.getByRole( 'link', { name: 'Expand' } ).first().click();
		} );

		await test.step( 'Expect all "Downloadable" checkboxes to be checked.', async () => {
			const checkBoxes = page.locator(
				'input[name^="variable_is_downloadable"]'
			);
			const count = await checkBoxes.count();

			for ( let i = 0; i < count; i++ ) {
				await expect( checkBoxes.nth( i ) ).toBeChecked();
			}
		} );
	} );

	test( 'can delete all variations', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_deleteAll }&action=edit#variable_product_options`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page
				.getByRole( 'link', { name: 'Variations' } )
				.last()
				.click();
		} );

		await test.step( 'Select the bulk action "Delete all variations".', async () => {
			page.on( 'dialog', ( dialog ) => dialog.accept() );
			await page.locator( '#field_to_edit' ).selectOption( 'delete_all' );
		} );

		await test.step( 'Expect that there are no more variations.', async () => {
			await expect(
				page.locator( '.woocommerce_variation' )
			).toHaveCount( 0 );
		} );
	} );

	test( 'can manage stock levels', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_manageStock }&action=edit#variable_product_options`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.getByRole( 'link', { name: 'Variations' } ).click();
		} );

		await test.step( 'Expand all variations', async () => {
			await page.getByRole( 'link', { name: 'Expand' } ).first().click();
		} );

		const variationContainer = page.locator(
			'.woocommerce_variations .woocommerce_variation'
		);

		await test.step( 'Check the "Manage stock?" box', async () => {
			await variationContainer
				.locator( 'input.checkbox.variable_manage_stock' )
				.check();
		} );

		await test.step( `Expect the "Stock status" text box to disappear`, async () => {
			await expect(
				variationContainer.locator( 'p.variable_stock_status' )
			).toBeHidden();
		} );

		await test.step( `Enter "${ variationOnePrice }" as the regular price`, async () => {
			await variationContainer
				.getByPlaceholder( 'Variation price (required)' )
				.fill( variationOnePrice );
		} );

		await test.step( `Enter "${ stockAmount }" as the stock quantity`, async () => {
			await variationContainer
				.locator( 'input[name^="variable_stock"]' )
				.fill( stockAmount );
		} );

		await test.step( 'Select "Allow, but notify customer" from the "Allow backorders?" menu', async () => {
			await variationContainer
				.locator( 'select[name^="variable_backorders"]' )
				.selectOption( 'notify' );
		} );

		await test.step( `Enter "${ lowStockAmount }" in the "Low stock threshold" input field.`, async () => {
			await variationContainer
				.getByPlaceholder( 'Store-wide threshold' )
				.fill( lowStockAmount );
		} );

		await test.step( 'Click "Save changes"', async () => {
			await page.getByRole( 'button', { name: 'Save changes' } ).click();
		} );

		await test.step( 'Expand all variations', async () => {
			await page.getByRole( 'link', { name: 'Expand' } ).first().click();
		} );

		await test.step( 'Expect the stock quantity to be saved correctly', async () => {
			await expect(
				variationContainer.locator( 'input[name^="variable_stock"]' )
			).toHaveValue( stockAmount );
		} );

		await test.step( 'Expect the "Low stock threshold" value to be saved correctly', async () => {
			await expect(
				variationContainer.getByPlaceholder( 'Store-wide threshold' )
			).toHaveValue( lowStockAmount );
		} );

		await test.step( 'Expect the "Allow backorders?" value to be saved correctly', async () => {
			await expect(
				variationContainer.locator(
					'select[name^="variable_backorders"] > option[selected]'
				)
			).toHaveText( 'Allow, but notify customer' );
		} );
	} );

	test( 'can set variation defaults', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_variationDefaults }&action=edit#variable_product_options`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page
				.getByRole( 'link', { name: 'Variations' } )
				.last()
				.click();
		} );

		await test.step( 'Wait for block overlay to disappear.', async () => {
			await expect( page.locator( '.blockOverlay' ) ).toBeHidden();
		} );

		await test.step( 'Select variation defaults', async () => {
			for ( const attribute of defaultVariation ) {
				const defaultAttributeMenu = page.locator( 'select', {
					hasText: `No default ${ attribute.name }…`,
				} );

				await defaultAttributeMenu.selectOption( attribute.option );
			}
		} );

		await test.step( 'Click "Save changes"', async () => {
			const saveButton = page.getByRole( 'button', {
				name: 'Save changes',
			} );
			await saveButton.click();
			await expect( saveButton ).toBeDisabled();
		} );

		await test.step( 'View the product from the shop', async () => {
			const permalink = await page
				.locator( '#sample-permalink a' )
				.getAttribute( 'href' );

			await page.goto( permalink );
		} );

		await test.step( 'Expect the default attributes to be pre-selected', async () => {
			for ( const attribute of defaultVariation ) {
				await test.step( `Expect "${ attribute.option }" is selected as the default "${ attribute.name }"`, async () => {
					const defaultSelectedAttribute = page
						.getByRole( 'row', {
							name: attribute.name,
						} )
						.locator( 'option[selected]' );

					await expect( defaultSelectedAttribute ).toHaveText(
						attribute.option
					);
				} );
			}
		} );
	} );

	test( 'can remove a variation', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_removeVariation }&action=edit#variable_product_options`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page
				.getByRole( 'link', { name: 'Variations' } )
				.last()
				.click();
		} );

		await test.step( 'Click "Remove" on a variation', async () => {
			page.on( 'dialog', ( dialog ) => dialog.accept() );
			await page.locator( '.woocommerce_variation' ).hover();
			await page.locator( '.remove_variation.delete' ).click();
		} );

		await test.step( 'Expect the variation to be removed', async () => {
			await expect(
				page.locator( '.woocommerce_variation' )
			).toHaveCount( 0 );
		} );
	} );
} );
