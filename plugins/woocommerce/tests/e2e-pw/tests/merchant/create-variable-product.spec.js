const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productPageURL = 'wp-admin/post-new.php?post_type=product';

const variableProductName = 'Variable Product with Three Variations';
const manualVariableProduct = 'Manual Variable Product';
const variationOnePrice = '9.99';
const variationTwoPrice = '11.99';
const variationThreePrice = '20.00';
const productWeight = '200';
const productLength = '10';
const productWidth = '20';
const productHeight = '15';
const defaultAttributes = [ 'val2', 'val1', 'val2' ];
const stockAmount = '100';
const lowStockAmount = '10';

let variableProductId;

async function deleteProductsAddedByTests( baseURL ) {
	const api = new wcApi( {
		url: baseURL,
		consumerKey: process.env.CONSUMER_KEY,
		consumerSecret: process.env.CONSUMER_SECRET,
		version: 'wc/v3',
	} );

	const varProducts = await api
		.get( 'products', { per_page: 100, search: variableProductName } )
		.then( ( response ) => response.data );

	const manualProducts = await api
		.get( 'products', { per_page: 100, search: manualVariableProduct } )
		.then( ( response ) => response.data );

	const ids = varProducts
		.map( ( { id } ) => id )
		.concat( manualProducts.map( ( { id } ) => id ) );

	await api.post( 'products/batch', { delete: ids } );
}

async function resetVariableProductTour( baseURL, browser ) {
	// Go to the product page, so that the `window.wp.data` module is available
	const page = await browser.newPage( { baseURL: baseURL } );
	await page.goto( productPageURL );

	// Get the current user's ID and user preferences
	const { id: userId, woocommerce_meta } = await page.evaluate( () => {
		return window.wp.data.select( 'core' ).getCurrentUser();
	} );

	// Reset the variable product tour preference, so that it will be shown again
	const updatedWooCommerceMeta = {
		...woocommerce_meta,
		variable_product_tour_shown: '',
	};

	// Save the updated user preferences
	await page.evaluate(
		async ( { userId, updatedWooCommerceMeta } ) => {
			await window.wp.data.dispatch( 'core' ).saveUser( {
				id: userId,
				woocommerce_meta: updatedWooCommerceMeta,
			} );
		},
		{ userId, updatedWooCommerceMeta }
	);
}

test.describe( 'Add New Variable Product Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL, browser } ) => {
		await deleteProductsAddedByTests( baseURL );
		await resetVariableProductTour( baseURL, browser );
	} );

	test( 'can create product, attributes and variations', async ( {
		page,
	} ) => {
		await test.step( 'Go to "Products > Add New page".', async () => {
			await page.goto( productPageURL );
		} );

		await test.step(
			`Type "${ variableProductName }" into the "Product name" input field.`,
			async () => {
				await page.fill( '#title', variableProductName );
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
				await page
					.locator( '.attribute_tab' )
					.getByRole( 'link', { name: 'Attributes' } )
					.scrollIntoViewIfNeeded();

				await page
					.locator( '.attribute_tab' )
					.getByRole( 'link', { name: 'Attributes' } )
					.click();
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

		await test.step( 'Add 3 attributes.', async () => {
			for ( let i = 0; i < 3; i++ ) {
				if ( i > 0 ) {
					await test.step( "Click 'Add'.", async () => {
						await page
							.locator( '.add-attribute-container' )
							.getByRole( 'button', { name: 'Add' } )
							.click();
					} );
				}

				await test.step(
					`Add the attribute "attr #${
						i + 1
					}" with values "val1 | val2"`,
					async () => {
						await test.step(
							'Wait for the "Attribute name" input field to appear.',
							async () => {
								await page.waitForSelector(
									`input[name="attribute_names[${ i }]"]`
								);
							}
						);

						await test.step(
							`Type "attr #${
								i + 1
							}" in the "Attribute name" input field.`,
							async () => {
								await page
									.locator(
										`input[name="attribute_names[${ i }]"]`
									)
									.first()
									.type( `attr #${ i + 1 }` );
							}
						);

						await test.step(
							'Type the attribute values "val1 | val2".',
							async () => {
								await page
									.locator(
										`textarea[name="attribute_values[${ i }]"]`
									)
									.first()
									.type( 'val1 | val2' );
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
					}
				);
			}
		} );

		await test.step(
			'Save before going to the Variations tab to prevent variations from all attributes to be automatically created.',
			async () => {
				await page.locator( '#save-post' ).click();
			}
		);

		await test.step(
			'Expect the "Product draft updated." notice to appear.',
			async () => {
				await expect(
					page.getByText( 'Product draft updated. ' )
				).toBeVisible();
			}
		);

		await test.step( 'Save the product ID.', async () => {
			variableProductId = page
				.url()
				.match( /post=\d+/ )[ 0 ]
				.replace( 'post=', '' );
		} );

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
			'Expect variations to have the correct attribute values',
			async () => {
				for ( let i = 0; i < 8; i++ ) {
					const val1 = 'val1';
					const val2 = 'val2';
					const attr3 = !! ( i % 2 ); // 0-1,4-5 / 2-3,6-7
					const attr2 = i % 4 > 1; // 0-3 / 4-7
					const attr1 = i > 3;
					await expect(
						page.locator(
							`select[name="attribute_attr-1[${ i }]"]`
						)
					).toHaveValue( attr1 ? val2 : val1 );
					await expect(
						page.locator(
							`select[name="attribute_attr-2[${ i }]"]`
						)
					).toHaveValue( attr2 ? val2 : val1 );
					await expect(
						page.locator(
							`select[name="attribute_attr-3[${ i }]"]`
						)
					).toHaveValue( attr3 ? val2 : val1 );
				}
			}
		);

		await test.step( 'Click "Save Draft" button.', async () => {
			await page.locator( '#save-post' ).click();
		} );

		await test.step(
			'Expect the "Product draft updated." notice to appear.',
			async () => {
				await expect(
					page.locator( '#message.notice-success' )
				).toContainText( 'Product draft updated.' );
			}
		);
	} );

	test( 'can individually edit variations', async ( { page } ) => {
		// mytodo setup: create variable product with the same attributes and variations

		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ variableProductId }&action=edit`
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

		await test.step( 'Set the first variation to be virtual.', async () => {
			await page.check( 'input[name="variable_is_virtual[0]"]' );
		} );

		await test.step(
			`Set regular price of first variation to ${ variationOnePrice }.`,
			async () => {
				await page.fill(
					'input[name="variable_regular_price[0]"]',
					variationOnePrice
				);
			}
		);

		await test.step(
			'Set the second variation to be virtual.',
			async () => {
				await page.check( 'input[name="variable_is_virtual[1]"]' );
			}
		);

		await test.step(
			`Set regular price of second variation to ${ variationTwoPrice }.`,
			async () => {
				await page.fill(
					'input[name="variable_regular_price[1]"]',
					variationTwoPrice
				);
			}
		);

		await test.step(
			'Check "Manage stock?" in the third variation.',
			async () => {
				await page.check( 'input[name="variable_manage_stock[2]"]' );
			}
		);

		await test.step(
			`Set regular price of third variation to ${ variationThreePrice }.`,
			async () => {
				await page.fill(
					'input[name="variable_regular_price[2]"]',
					variationThreePrice
				);
			}
		);

		await test.step(
			'Set the weight and dimensions of the third variation.',
			async () => {
				await page.fill(
					'input[name="variable_weight[2]"]',
					productWeight
				);
				await page.fill(
					'input[name="variable_length[2]"]',
					productLength
				);
				await page.fill(
					'input[name="variable_width[2]"]',
					productWidth
				);
				await page.fill(
					'input[name="variable_height[2]"]',
					productHeight
				);
				await page.keyboard.press( 'ArrowUp' );
			}
		);

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
					page.locator( 'input[name="variable_is_virtual[0]"]' )
				).toBeChecked();
			}
		);

		await test.step(
			`Expect the price of the first variation to be ${ variationOnePrice }.`,
			async () => {
				await expect(
					page.locator( 'input[name="variable_regular_price[0]"]' )
				).toHaveValue( variationOnePrice );
			}
		);

		await test.step(
			'Expect the second variation to be virtual.',
			async () => {
				await expect(
					page.locator( 'input[name="variable_is_virtual[1]"]' )
				).toBeChecked();
			}
		);

		await test.step(
			`Expect the price of the second variation to be ${ variationTwoPrice }.`,
			async () => {
				await expect(
					page.locator( 'input[name="variable_regular_price[1]"]' )
				).toHaveValue( variationTwoPrice );
			}
		);

		await test.step(
			'Expect the "Manage stock?" checkbox of the third variation to be checked.',
			async () => {
				await expect(
					page.locator( 'input[name="variable_manage_stock[2]"]' )
				).toBeChecked();
			}
		);

		await test.step(
			`Expect the price of the third variation to be ${ variationThreePrice }.`,
			async () => {
				await expect(
					page.locator( 'input[name="variable_regular_price[2]"]' )
				).toHaveValue( variationThreePrice );
			}
		);

		await test.step(
			'Expect the weight and dimensions of the third variation to be correct.',
			async () => {
				await expect(
					page.locator( 'input[name="variable_weight[2]"]' )
				).toHaveValue( productWeight );

				await expect(
					page.locator( 'input[name="variable_length[2]"]' )
				).toHaveValue( productLength );

				await expect(
					page.locator( 'input[name="variable_width[2]"]' )
				).toHaveValue( productWidth );

				await expect(
					page.locator( 'input[name="variable_height[2]"]' )
				).toHaveValue( productHeight );
			}
		);
	} );

	test( 'can bulk edit variations', async ( { page } ) => {
		// mytodo setup: create variable product with the same attributes and variations

		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ variableProductId }&action=edit`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.click( 'a[href="#variable_product_options"]' );
		} );

		await test.step(
			'Select the \'Toggle "Downloadable"\' bulk action.',
			async () => {
				await page.selectOption(
					'#field_to_edit',
					'toggle_downloadable'
				);
			}
		);

		await test.step( 'Expand all variations.', async () => {
			await page.click(
				'#variable_product_options .toolbar-top a.expand_all'
			);
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
		// mytodo setup: create variable product with the same attributes and variations

		await test.step( 'Go to the "Edit product" page.', async () => {
			await page.goto(
				`/wp-admin/post.php?post=${ variableProductId }&action=edit`
			);
		} );

		await test.step( 'Click on the "Variations" tab.', async () => {
			await page.click( 'a[href="#variable_product_options"]' );
		} );

		await test.step(
			'Select the bulk action "Delete all variations".',
			async () => {
				page.on( 'dialog', ( dialog ) => dialog.accept() );
				await page.selectOption( '#field_to_edit', 'delete_all' );
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

	// mytodo remove skip
	test.skip( 'can manually add a variation, manage stock levels, set variation defaults and remove a variation', async ( {
		page,
	} ) => {
		await page.goto( productPageURL );
		await page.fill( '#title', manualVariableProduct );
		await page.selectOption( '#product-type', 'variable' );

		await page
			.locator( '.attribute_tab' )
			.getByRole( 'link', { name: 'Attributes' } )
			.scrollIntoViewIfNeeded();

		// the tour only seems to display when not running headless, so just make sure
		if (
			await page
				.locator( '.woocommerce-tour-kit-step__heading' )
				.isVisible()
		) {
			// dismiss the variable product tour
			await page.getByRole( 'button', { name: 'Close Tour' } ).click();

			// wait for the tour's dismissal to be saved
			await page.waitForResponse(
				( response ) =>
					response.url().includes( '/users/' ) &&
					response.status() === 200
			);
		}

		await page.click( 'a[href="#product_attributes"]' );
		// add 3 attributes
		for ( let i = 0; i < 3; i++ ) {
			if ( i > 0 ) {
				await page.click( 'button.add_attribute' );
			}
			await page.waitForSelector(
				`input[name="attribute_names[${ i }]"]`
			);

			await page
				.locator( `input[name="attribute_names[${ i }]"]` )
				.first()
				.type( `attr #${ i + 1 }` );
			await page
				.locator( `textarea[name="attribute_values[${ i }]"]` )
				.first()
				.type( 'val1 | val2' );
		}
		await page.click( 'text=Save attributes' );
		// wait for the attributes to be saved
		await page.waitForResponse(
			( response ) =>
				response.url().includes( '/post.php?post=' ) &&
				response.status() === 200
		);

		// Save before going to the Variations tab to prevent variations from all attributes to be automatically created
		await page.locator( '#save-post' ).click();
		await expect(
			page.getByText( 'Product draft updated. ' )
		).toBeVisible();
		await page.click( '.updated.notice .notice-dismiss' );

		// manually adds a variation
		await page.click( 'a[href="#variable_product_options"]' );
		await page.click( 'button.add_variation_manually' );
		await expect( page.locator( '.variation-needs-update' ) ).toBeVisible();
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await page.selectOption(
				`.variation-needs-update h3 select >> nth=${ i }`,
				defaultAttributes[ i ]
			);
		}
		await page.click( 'button.save-variation-changes' );
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await expect(
				page
					.locator( '.woocommerce_variation' )
					.first()
					.locator( 'select' )
					.nth( i )
					.locator( 'option[selected]' )
			).toHaveText( defaultAttributes[ i ] );
		}

		await page.locator( '#save-post' ).click();
		await expect(
			page.getByText( 'Product draft updated. ' )
		).toBeVisible();

		// manage stock at variation level
		await page.click( 'a[href="#variable_product_options"]' );
		await page.waitForLoadState( 'networkidle' );
		await page.click(
			'#variable_product_options .toolbar-top a.expand_all'
		);
		await page.check( 'input.checkbox.variable_manage_stock' );
		await page.fill( 'input#variable_regular_price_0', variationOnePrice );
		await expect(
			page.locator( 'p.variable_stock_status' )
		).not.toBeVisible();
		await page.fill( 'input#variable_stock0', stockAmount );
		await page.selectOption( '#variable_backorders0', 'notify', {
			force: true,
		} );
		await page.fill( 'input#variable_low_stock_amount0', lowStockAmount );
		await page.click( 'button.save-variation-changes' );
		await page.click(
			'#variable_product_options .toolbar-top a.expand_all'
		);
		await expect( page.locator( '#variable_stock0' ) ).toHaveValue(
			stockAmount
		);
		await expect(
			page.locator( '#variable_low_stock_amount0' )
		).toHaveValue( lowStockAmount );
		await expect(
			page.locator( '#variable_backorders0 > option[selected]' )
		).toHaveText( 'Allow, but notify customer' );

		// set variation defaults
		await page.click( 'a[href="#variable_product_options"]' );

		// set variation defaults
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await page.selectOption(
				`select[name="default_attribute_attr-${ i + 1 }"]`,
				defaultAttributes[ i ]
			);
		}
		await page.click( 'button.save-variation-changes' );
		await page.waitForSelector( 'input#variable_low_stock_amount0', {
			state: 'hidden',
		} );
		for ( let i = 0; i < defaultAttributes.length; i++ ) {
			await expect(
				page.locator(
					`select[name="default_attribute_attr-${ i + 1 }"]`
				)
			).toHaveValue( `${ defaultAttributes[ i ] }` );
		}

		// remove a variation
		await page.click( 'a[href="#variable_product_options"]' );

		// remove a variation
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page.hover( '.woocommerce_variation' );
		await page.click( '.remove_variation.delete' );
		await expect( page.locator( '.woocommerce_variation' ) ).toHaveCount(
			0
		);
	} );
} );
