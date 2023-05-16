const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const productPageURL = 'wp-admin/post-new.php?post_type=product';
const variableProductName = 'Variable Product with Three Variations';
const productAttributes = [
	{
		name: 'Colour',
		visible: true,
		variation: true,
		options: [ 'Red', 'Green' ],
	},
	{
		name: 'Size',
		visible: true,
		variation: true,
		options: [ 'Small', 'Medium' ],
	},
	{
		name: 'Logo',
		visible: true,
		variation: true,
		options: [ 'Woo', 'WordPress' ],
	},
];

let productId_noAttributes,
	productId_generateVariations,
	expectedGeneratedVariations,
	productId_addManually,
	variationsToManuallyCreate,
	productIds = [];

function generateVariationsFromAttributes( attributes ) {
	const combine = ( runningList, nextAttribute ) => {
		const variations = [];
		let newVar;

		if ( ! Array.isArray( runningList[ 0 ] ) ) {
			runningList = [ runningList ];
		}

		for ( const partialVariation of runningList ) {
			if ( runningList.length === 1 ) {
				for ( const startingAttribute of partialVariation ) {
					for ( const nextAttrValue of nextAttribute ) {
						newVar = [ startingAttribute, nextAttrValue ];
						variations.push( newVar );
					}
				}
			} else {
				for ( const nextAttrValue of nextAttribute ) {
					newVar = partialVariation.concat( [ nextAttrValue ] );
					variations.push( newVar );
				}
			}
		}

		return variations;
	};

	let allVariations = attributes[ 0 ].options;

	for ( let i = 1; i < attributes.length; i++ ) {
		const nextAttribute = attributes[ i ].options;

		allVariations = combine( allVariations, nextAttribute );
	}

	return allVariations;
}

function newWCApi( baseURL ) {
	return new wcApi( {
		url: baseURL,
		consumerKey: process.env.CONSUMER_KEY,
		consumerSecret: process.env.CONSUMER_SECRET,
		version: 'wc/v3',
	} );
}

async function deleteProductsAddedByTests( baseURL ) {
	const api = newWCApi( baseURL );

	await api.post( 'products/batch', { delete: productIds } );
}

/**
 * @param {import('@playwright/test').Page} page
 */
async function toggleVariableProductTour( page, enableTour = true ) {
	const { id: userId, woocommerce_meta } = await test.step(
		"Get the current user's ID and user preferences",
		async () => {
			return await page.evaluate( () => {
				return window.wp.data.select( 'core' ).getCurrentUser();
			} );
		}
	);

	const updatedWooCommerceMeta = await test.step(
		'Turn off the variable product tour.',
		() => {
			return {
				...woocommerce_meta,
				variable_product_tour_shown: enableTour ? '' : '"yes"',
			};
		}
	);

	await test.step( 'Save the updated user preferences', async () => {
		await page.evaluate(
			async ( { userId, updatedWooCommerceMeta } ) => {
				await window.wp.data.dispatch( 'core' ).saveUser( {
					id: userId,
					woocommerce_meta: updatedWooCommerceMeta,
				} );
			},
			{ userId, updatedWooCommerceMeta }
		);
	} );
}

async function createVariableProduct( baseURL, attributes = [] ) {
	const api = newWCApi( baseURL );
	const randomNum = Math.floor( Math.random() * 1000 );
	const payload = {
		name: `Unbranded Granite Shirt ${ randomNum }`,
		type: 'variable',
		attributes,
	};

	const productId = await api
		.post( 'products', payload )
		.then( ( response ) => response.data.id );

	productIds.push( productId );

	return productId;
}

test.describe.only( 'Variable product', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		const addProductPage = await browser.newPage();

		await test.step( 'Open the "Add product" page', async () => {
			await addProductPage.goto( productPageURL );
		} );

		await test.step( 'Disable the variable product tour.', async () => {
			await toggleVariableProductTour( addProductPage, false );
		} );

		await test.step( 'Close the page', async () => {
			await addProductPage.close();
		} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		await deleteProductsAddedByTests( baseURL );
	} );

	test.describe( 'Add variable product', () => {
		test( 'can create a variable product', async ( { page } ) => {
			await test.step( 'Go to the "Add new product" page', async () => {
				await page.goto( productPageURL );
			} );

			await test.step( 'Reset the variable product tour.', async () => {
				await toggleVariableProductTour( page );
			} );

			await test.step( 'Reload the "Add new product" page', async () => {
				await page.reload();
			} );

			await test.step(
				`Type "${ variableProductName }" into the "Product name" input field.`,
				async () => {
					await page
						.getByLabel( 'Product name' )
						.fill( variableProductName );
				}
			);

			await test.step(
				'Select the "Variable product" product type.',
				async () => {
					await page.selectOption( '#product-type', 'variable' );
				}
			);

			await test.step(
				'Scroll into the "Attributes" tab and click it.',
				async () => {
					const attributesTab = page
						.locator( '.attribute_tab' )
						.getByRole( 'link', { name: 'Attributes' } );

					await attributesTab.scrollIntoViewIfNeeded();

					await attributesTab.click();
				}
			);

			// the tour only seems to display when not running headless, so just make sure
			const tourWasDisplayed = await test.step(
				'See if the tour was displayed.',
				async () => {
					return await page
						.locator( '.woocommerce-tour-kit-step__heading' )
						.isVisible();
				}
			);

			if ( tourWasDisplayed ) {
				await test.step(
					'Tour was displayed, so dismiss it.',
					async () => {
						await page
							.getByRole( 'button', { name: 'Close Tour' } )
							.click();
					}
				);

				await test.step(
					"Wait for the tour's dismissal to be saved",
					async () => {
						await page.waitForResponse(
							( response ) =>
								response.url().includes( '/users/' ) &&
								response.status() === 200
						);
					}
				);
			}

			await test.step(
				`Expect the "Variations" tab to appear`,
				async () => {
					const variationsTab = page.locator( 'li.variations_tab' );

					await expect( variationsTab ).toBeVisible();
				}
			);

			await test.step( 'Save draft.', async () => {
				await page.locator( '#save-post' ).click();
			} );

			await test.step(
				'Expect the "Product draft updated." notice to appear.',
				async () => {
					await expect(
						page.getByText( 'Product draft updated.' )
					).toBeVisible();
				}
			);

			await test.step(
				'Expect the product type to be "Variable product"',
				async () => {
					const selectedProductType = page.locator(
						'select#product-type [selected]'
					);

					await expect( selectedProductType ).toHaveText(
						'Variable product'
					);
				}
			);

			await test.step(
				'Add product ID to list of products to be deleted after test.',
				async () => {
					const productId = page.url().match( /(?<=post=)\d+/ );
					productIds.push( productId );
				}
			);
		} );
	} );

	test.describe( 'Add product attributes', () => {
		test.beforeAll( async ( { baseURL } ) => {
			productId_noAttributes = await test.step(
				'Create variable product with no attributes',
				async () => {
					return await createVariableProduct( baseURL );
				}
			);
		} );

		test( 'can add custom product attributes', async ( { page } ) => {
			await test.step(
				`Open "Edit product" page of product id ${ productId_noAttributes }`,
				async () => {
					await page.goto(
						`/wp-admin/post.php?post=${ productId_noAttributes }&action=edit`
					);
				}
			);

			await test.step( 'Go to the "Attributes" tab.', async () => {
				const attributesTab = page
					.locator( '.attribute_tab' )
					.getByRole( 'link', { name: 'Attributes' } );

				await attributesTab.click();
			} );

			await test.step( 'Add 3 attributes.', async () => {
				for ( let i = 0; i < productAttributes.length; i++ ) {
					const attributeName = productAttributes[ i ].name;
					const attributeValues = productAttributes[ i ].options.join(
						'|'
					);

					if ( i > 0 ) {
						await test.step( "Click 'Add new'.", async () => {
							await page
								.locator( '#product_attributes .toolbar-top' )
								.getByRole( 'button', { name: 'Add new' } )
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
											'f.e. size or color'
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
											'Enter options for customers to choose from'
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
											name: 'Save attributes',
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
			} );
		} );
	} );

	test.describe( 'Add variations', () => {
		test.beforeAll( async ( { baseURL } ) => {
			productId_generateVariations = await test.step(
				'Create variable product with attributes',
				async () => {
					return await createVariableProduct(
						baseURL,
						productAttributes
					);
				}
			);

			productId_addManually = await test.step(
				'Create variable product with attributes',
				async () => {
					return await createVariableProduct(
						baseURL,
						productAttributes
					);
				}
			);

			expectedGeneratedVariations = generateVariationsFromAttributes(
				productAttributes
			);

			variationsToManuallyCreate = expectedGeneratedVariations.slice(
				0,
				3
			);
		} );

		test( 'can generate variations from product attributes', async ( {
			page,
		} ) => {
			await test.step(
				`Open "Edit product" page of product id ${ productId_generateVariations }`,
				async () => {
					await page.goto(
						`/wp-admin/post.php?post=${ productId_generateVariations }&action=edit`
					);
				}
			);

			await test.step( 'Click on the "Variations" tab.', async () => {
				await page.click( 'a[href="#variable_product_options"]' );
			} );

			await test.step(
				'Click on the "Generate variations" button.',
				async () => {
					// event listener for handling the link_all_variations confirmation dialog
					page.on( 'dialog', ( dialog ) => dialog.accept() );

					await page.click( 'button.generate_variations' );
				}
			);

			await test.step(
				`Expect the number of variations to be ${ expectedGeneratedVariations.length }`,
				async () => {
					const variations = page.locator( '.woocommerce_variation' );

					await expect( variations ).toHaveCount(
						expectedGeneratedVariations.length
					);
				}
			);

			for ( const variation of expectedGeneratedVariations ) {
				await test.step(
					`Expect the variation "${ variation.join(
						', '
					) }" to be generated.`,
					async () => {
						let variationRow = page.locator(
							'.woocommerce_variation h3'
						);

						for ( const attributeValue of variation ) {
							variationRow = variationRow.filter( {
								has: page.locator( 'option[selected]', {
									hasText: attributeValue,
								} ),
							} );
						}

						await expect( variationRow ).toBeVisible();
					}
				);
			}
		} );

		test( 'can manually add a variation', async ( { page } ) => {
			await test.step(
				`Open "Edit product" page of product id ${ productId_addManually }`,
				async () => {
					await page.goto(
						`/wp-admin/post.php?post=${ productId_addManually }&action=edit`
					);
				}
			);

			await test.step( 'Click on the "Variations" tab.', async () => {
				await page.click( 'a[href="#variable_product_options"]' );
			} );

			await test.step(
				`Manually add ${ variationsToManuallyCreate.length } variations`,
				async () => {
					const variationRows = page.locator(
						'.woocommerce_variation h3'
					);
					let variationRowsCount = await variationRows.count();

					for ( const variationToCreate of variationsToManuallyCreate ) {
						await test.step( 'Click "Add manually"', async () => {
							const addManuallyButton = page.getByRole(
								'button',
								{
									name: 'Add manually',
								}
							);

							await addManuallyButton.click();

							await expect( variationRows ).toHaveCount(
								++variationRowsCount
							);
						} );

						for ( const attributeValue of variationToCreate ) {
							const attributeName = productAttributes.find(
								( { options } ) =>
									options.includes( attributeValue )
							).name;
							const addAttributeMenu = variationRows
								.nth( 0 )
								.locator( 'select', {
									has: page.locator( 'option', {
										hasText: attributeValue,
									} ),
								} );

							await test.step(
								`Select "${ attributeValue }" from the "${ attributeName }" attribute menu`,
								async () => {
									await addAttributeMenu.selectOption(
										attributeValue
									);
								}
							);
						}

						await test.step( 'Click "Save changes"', async () => {
							await page
								.getByRole( 'button', {
									name: 'Save changes',
								} )
								.click();
						} );

						await test.step(
							`Expect the variation ${ variationToCreate.join(
								', '
							) } to be successfully saved.`,
							async () => {
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

								await expect(
									newlyAddedVariationRow
								).toBeVisible();
							}
						);
					}
				}
			);
		} );
	} );

	test.describe( 'Update variations', () => {
		// mytodo

		test.beforeAll( async ( { baseURL } ) => {} );

		test( 'can individually edit variations', async ( { page } ) => {
			const variationRows = page.locator( '.woocommerce_variation' );
			const firstVariation = variationRows.filter( {
				hasText: `#${ fixedVariationIds[ 0 ] }`,
			} );
			const secondVariation = variationRows.filter( {
				hasText: `#${ fixedVariationIds[ 1 ] }`,
			} );
			const thirdVariation = variationRows.filter( {
				hasText: `#${ fixedVariationIds[ 2 ] }`,
			} );

			await test.step( 'Go to the "Edit product" page.', async () => {
				await page.goto(
					`/wp-admin/post.php?post=${ fixedVariableProductId }&action=edit`
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
} );
