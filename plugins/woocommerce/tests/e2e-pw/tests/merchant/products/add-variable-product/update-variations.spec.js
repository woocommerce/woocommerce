const { test, expect } = require( '@playwright/test' );
const {
	createVariableProduct,
	showVariableProductTour,
	deleteProductsAddedByTests,
	productAttributes,
	sampleVariations,
	createVariations,
} = require( './utils' );
const variationOnePrice = '9.99';
const variationTwoPrice = '11.99';
const variationThreePrice = '20.00';
const productWeight = '200';
const productLength = '10';
const productWidth = '20';
const productHeight = '15';

let productId_indivEdit,
	productId_bulkEdit,
	productId_deleteAll,
	variationIds_indivEdit;

test.describe.only( 'Update variations', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL, browser } ) => {
		await test.step(
			'Create variable product for individual edit test',
			async () => {
				productId_indivEdit = await createVariableProduct(
					baseURL,
					productAttributes
				);

				variationIds_indivEdit = await createVariations(
					baseURL,
					productId_indivEdit,
					sampleVariations
				);
			}
		);

		await test.step(
			'Create variable product for bulk edit test',
			async () => {
				productId_bulkEdit = await createVariableProduct(
					baseURL,
					productAttributes
				);

				await createVariations(
					baseURL,
					productId_bulkEdit,
					sampleVariations
				);
			}
		);

		await test.step(
			'Create variable product for "delete all" test',
			async () => {
				productId_deleteAll = await createVariableProduct(
					baseURL,
					productAttributes
				);

				await createVariations(
					baseURL,
					productId_deleteAll,
					sampleVariations
				);
			}
		);

		await test.step( 'Hide variable product tour', async () => {
			await showVariableProductTour( browser, false );
		} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const productIds = [ productId_indivEdit, productId_bulkEdit ];

		await deleteProductsAddedByTests( baseURL, productIds );
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
				`/wp-admin/post.php?post=${ productId_indivEdit }&action=edit`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.locator( 'a[href="#variable_product_options"]' ).click();
		} );

		await test.step( 'Expand all variations.', async () => {
			await page
				.locator(
					'#variable_product_options .toolbar-top a.expand_all'
				)
				.click();
		} );

		await test.step( 'Edit the first variation.', async () => {
			await test.step( 'Check the "Virtual" checkbox.', async () => {
				await firstVariation
					.getByRole( 'checkbox', {
						name: 'Virtual',
					} )
					.check();
			} );

			await test.step(
				`Set regular price to "${ variationOnePrice }".`,
				async () => {
					await firstVariation
						.getByRole( 'textbox', { name: 'Regular price' } )
						.fill( variationOnePrice );
				}
			);
		} );

		await test.step( 'Edit the second variation.', async () => {
			await test.step( 'Check the "Virtual" checkbox.', async () => {
				await secondVariation
					.getByRole( 'checkbox', {
						name: 'Virtual',
					} )
					.check();
			} );

			await test.step(
				`Set regular price to "${ variationTwoPrice }".`,
				async () => {
					await secondVariation
						.getByRole( 'textbox', { name: 'Regular price' } )
						.fill( variationTwoPrice );
				}
			);
		} );

		await test.step( 'Edit the third variation.', async () => {
			await test.step( 'Check "Manage stock?"', async () => {
				await thirdVariation
					.getByRole( 'checkbox', { name: 'Manage stock?' } )
					.check();
			} );

			await test.step(
				`Set regular price to "${ variationThreePrice }".`,
				async () => {
					await thirdVariation
						.getByRole( 'textbox', { name: 'Regular price' } )
						.fill( variationThreePrice );
				}
			);

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
			await page.locator( 'button.save-variation-changes' ).click();
			await page.waitForLoadState( 'networkidle' );
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.locator( 'a[href="#variable_product_options"]' ).click();
		} );

		await test.step( 'Expand all variations.', async () => {
			await page
				.locator(
					'#variable_product_options .toolbar-top a.expand_all'
				)
				.click();
		} );

		await test.step(
			'Expect the first variation to be virtual.',
			async () => {
				await expect(
					firstVariation.getByRole( 'checkbox', {
						name: 'Virtual',
					} )
				).toBeChecked();
			}
		);

		await test.step(
			`Expect the regular price of the first variation to be "${ variationOnePrice }".`,
			async () => {
				await expect(
					firstVariation.getByRole( 'textbox', {
						name: 'Regular price',
					} )
				).toHaveValue( variationOnePrice );
			}
		);

		await test.step(
			'Expect the second variation to be virtual.',
			async () => {
				await expect(
					secondVariation.getByRole( 'checkbox', {
						name: 'Virtual',
					} )
				).toBeChecked();
			}
		);

		await test.step(
			`Expect the regular price of the second variation to be "${ variationTwoPrice }".`,
			async () => {
				await expect(
					secondVariation.getByRole( 'textbox', {
						name: 'Regular price',
					} )
				).toHaveValue( variationTwoPrice );
			}
		);

		await test.step(
			'Expect the "Manage stock?" checkbox of the third variation to be checked.',
			async () => {
				await expect(
					thirdVariation.getByRole( 'checkbox', {
						name: 'Manage stock?',
					} )
				).toBeChecked();
			}
		);

		await test.step(
			`Expect the regular price of the third variation to be "${ variationThreePrice }".`,
			async () => {
				await expect(
					thirdVariation.getByRole( 'textbox', {
						name: 'Regular price',
					} )
				).toHaveValue( variationThreePrice );
			}
		);

		await test.step(
			'Expect the weight and dimensions of the third variation to be correct.',
			async () => {
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
			}
		);
	} );

	test( 'can bulk edit variations', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_bulkEdit }&action=edit`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.locator( 'a[href="#variable_product_options"]' ).click();
		} );

		await test.step(
			'Select the \'Toggle "Downloadable"\' bulk action.',
			async () => {
				await page
					.locator( '#field_to_edit' )
					.selectOption( 'toggle_downloadable' );
			}
		);

		await test.step( 'Expand all variations.', async () => {
			await page
				.locator(
					'#variable_product_options .toolbar-top a.expand_all'
				)
				.click();
		} );

		await test.step(
			'Expect all "Downloadable" checkboxes to be checked.',
			async () => {
				const checkBoxes = page.locator(
					'input[name^="variable_is_downloadable"]'
				);
				const count = await checkBoxes.count();

				for ( let i = 0; i < count; i++ ) {
					await expect( checkBoxes.nth( i ) ).toBeChecked();
				}
			}
		);
	} );

	test( 'can delete all variations', async ( { page } ) => {
		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ productId_deleteAll }&action=edit`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.locator( 'a[href="#variable_product_options"]' ).click();
		} );

		await test.step(
			'Select the bulk action "Delete all variations".',
			async () => {
				page.on( 'dialog', ( dialog ) => dialog.accept() );
				await page
					.locator( '#field_to_edit' )
					.selectOption( 'delete_all' );
			}
		);

		await test.step(
			'Expect that there are no more variations.',
			async () => {
				await expect(
					page.locator( '.woocommerce_variation' )
				).toHaveCount( 0 );
			}
		);
	} );
} );
