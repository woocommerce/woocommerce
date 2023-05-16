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

let productId,
	productIds = [];

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

// mytodo share commands with turnOffVariableProductTour
/**
 * @param {import('@playwright/test').Page} page
 */
async function resetVariableProductTour( page ) {
	const { id: userId, woocommerce_meta } = await test.step(
		"Get the current user's ID and user preferences",
		async () => {
			return await page.evaluate( () => {
				return window.wp.data.select( 'core' ).getCurrentUser();
			} );
		}
	);

	const updatedWooCommerceMeta = await test.step(
		'Reset the variable product tour preference, so that it will be shown again',
		async () => {
			return {
				...woocommerce_meta,
				variable_product_tour_shown: '',
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

// mytodo share commands with turnOffVariableProductTour
/**
 * @param {import('@playwright/test').Page} page
 */
async function turnOffVariableProductTour( page ) {
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
				variable_product_tour_shown: '"yes"',
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

	await test.step( 'Reload the page', async () => {
		await page.reload();
	} );
}

async function createVariableProduct( baseURL ) {
	const api = newWCApi( baseURL );
	const randomNum = Math.floor( Math.random() * 1000 );
	const payload = {
		name: `Unbranded Granite Shirt ${ randomNum }`,
		type: 'variable',
	};

	const productId = await api
		.post( 'products', payload )
		.then( ( response ) => response.data.id );

	productIds.push( productId );

	return productId;
}

test.describe.only( 'Add variable product', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async () => {
		productId = await test.step(
			'Create variable product with no attributes',
			async () => {
				return await createVariableProduct( baseURL );
			}
		);
	} );

	test.afterAll( async ( { baseURL } ) => {
		await deleteProductsAddedByTests( baseURL );
	} );

	test( 'can create a variable product', async ( { page } ) => {
		await test.step( 'Go to the "Add new product" page', async () => {
			await page.goto( productPageURL );
		} );

		await test.step( 'Reset the variable product tour.', async () => {
			await resetVariableProductTour( page );
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
			await test.step( 'Tour was displayed, so dismiss it.', async () => {
				await page
					.getByRole( 'button', { name: 'Close Tour' } )
					.click();
			} );

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

		await test.step( `Expect the "Variations" tab to appear`, async () => {
			const variationsTab = page.locator( 'li.variations_tab' );

			await expect( variationsTab ).toBeVisible();
		} );

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

	test( 'can add custom product attributes', async ( { page } ) => {
		await test.step(
			`Open "Edit product" page of product id ${ productId }`,
			async () => {
				await page.goto(
					`/wp-admin/post.php?post=${ productId }&action=edit`
				);
			}
		);

		await test.step( 'Turn off variable product tour.', async () => {
			await turnOffVariableProductTour( page );
		} );

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
									.getByPlaceholder( 'f.e. size or color' )
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
