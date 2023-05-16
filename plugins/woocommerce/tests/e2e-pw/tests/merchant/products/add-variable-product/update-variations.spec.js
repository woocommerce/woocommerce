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

let productId_indivEdit, variationIds_indivEdit;

test.describe( 'Update variations', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL, browser } ) => {
		productId_indivEdit = await createVariableProduct(
			baseURL,
			productAttributes
		);

		variationIds_indivEdit = await createVariations(
			baseURL,
			productId_indivEdit,
			sampleVariations
		);

		await showVariableProductTour( browser, false );
	} );

	test.afterAll( async ( { baseURL } ) => {
		await deleteProductsAddedByTests( baseURL, [ productId_indivEdit ] );
	} );

	test.only( 'can individually edit variations', async ( { page } ) => {
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
			await page.click( 'a[href="#variable_product_options"]' );
		} );

		await test.step( 'Expand all variations.', async () => {
			await page.click(
				'#variable_product_options .toolbar-top a.expand_all'
			);
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
			await page.click( 'button.save-variation-changes' );
			await page.waitForLoadState( 'networkidle' );
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.click( 'a[href="#variable_product_options"]' );
		} );

		await test.step( 'Expand all variations.', async () => {
			await page.click(
				'#variable_product_options .toolbar-top a.expand_all'
			);
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
} );
