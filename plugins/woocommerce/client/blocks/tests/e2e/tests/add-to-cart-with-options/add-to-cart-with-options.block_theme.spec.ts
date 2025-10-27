/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';
import { ProductGalleryPage } from '../product-gallery/product-gallery.page';
import config from '../../../../../admin/config/core.json';

const test = base.extend< {
	pageObject: AddToCartWithOptionsPage;
	productGalleryPageObject: ProductGalleryPage;
} >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
	productGalleryPageObject: async (
		{ page, editor, frontendUtils },
		use
	) => {
		const pageObject = new ProductGalleryPage( {
			page,
			editor,
			frontendUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Add to Cart + Options Block', () => {
	test( 'allows switching to 3rd-party product types', async ( {
		pageObject,
		editor,
		requestUtils,
	} ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-custom-product-type'
		);

		await pageObject.updateSingleProductTemplate();
		await pageObject.switchProductType( 'Custom Product Type' );

		const block = editor.canvas.getByLabel(
			`Block: ${ pageObject.BLOCK_NAME }`
		);
		const skeleton = block.locator( '.wc-block-components-skeleton' );
		await expect( skeleton ).toBeVisible();
	} );

	test( 'allows adding simple products to cart', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/beanie' );

		const increaseQuantityButton = page.getByLabel(
			'Increase quantity of Beanie'
		);
		await increaseQuantityButton.click();
		await increaseQuantityButton.click();

		const addToCartButton = page.getByLabel( 'Add to cart: “Beanie”' );

		await addToCartButton.click();

		await expect( addToCartButton ).toHaveText( '3 in cart' );

		await page.getByLabel( 'Product quantity' ).fill( '1' );
		await addToCartButton.click();

		await expect( addToCartButton ).toHaveText( '4 in cart' );
	} );

	test( 'allows adding variable products to cart', async ( {
		page,
		pageObject,
		productGalleryPageObject,
		editor,
	} ) => {
		const variationDescription =
			'This is the output of the variation description';
		// Set a variable product as having 100 in stock and one of its variations as being out of stock.
		// This way we can test that sibling blocks update with the variation data.
		let cliOutput = await wpCLI(
			`post list --post_type=product --field=ID --name="Hoodie" --format=ids`
		);
		const hoodieProductId = cliOutput.stdout.match( /\d+/g )?.pop();
		cliOutput = await wpCLI(
			'post list --post_type=product_variation --field=ID --name="Hoodie - Blue, No" --format=ids'
		);
		const hoodieProductVariationId = cliOutput.stdout
			.match( /\d+/g )
			?.pop();
		await wpCLI(
			`wc product update ${ hoodieProductId } --manage_stock=true --stock_quantity=100 --user=1`
		);
		await wpCLI(
			`wc product_variation update ${ hoodieProductId } ${ hoodieProductVariationId } --manage_stock=true --in_stock=false --weight=2 --description="${ variationDescription }" --user=1`
		);

		await pageObject.updateSingleProductTemplate();

		// We update to the Product Gallery block to test that it scrolls to the
		// correct variation image.
		const productImageGalleryBlock = await editor.getBlockByName(
			'woocommerce/product-image-gallery'
		);
		await editor.selectBlocks( productImageGalleryBlock );
		await editor.transformBlockTo( 'woocommerce/product-gallery' );

		// We insert the blockified Product Details block to test that it updates
		// with the correct variation data.
		await editor.insertBlock( {
			name: 'woocommerce/product-details',
		} );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		await test.step( 'increase and reduce quantity buttons work even when no variation is selected', async () => {
			const increaseQuantityButton = page.getByLabel(
				'Increase quantity of Hoodie'
			);
			await increaseQuantityButton.click();

			const quantityInput = page.getByLabel( 'Product quantity' );

			await expect( quantityInput ).toHaveValue( '2' );

			const reduceQuantityButton = page.getByLabel(
				'Reduce quantity of Hoodie'
			);
			await reduceQuantityButton.click();

			await expect( quantityInput ).toHaveValue( '1' );
		} );

		// The radio input is visually hidden and, thus, not clickable. That's
		// why we need to select the <label> instead.
		const logoNoOption = page.locator( 'label:has-text("No")' );
		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const colorGreenOption = page.locator( 'label:has-text("Green")' );
		const colorRedOption = page.locator( 'label:has-text("Red")' );
		const addToCartButton = page
			.getByRole( 'button', { name: 'Add to cart' } )
			.first();
		const productPrice = page
			.locator( '.wp-block-woocommerce-product-price' )
			.first();

		await test.step( 'displays an error when attributes are not selected', async () => {
			await addToCartButton.click();

			await expect(
				page.getByText(
					'Please select product attributes before adding to cart.'
				)
			).toBeVisible();
		} );

		await test.step( 'updates blocks rendering variation data when attributes are selected', async () => {
			// Open additional information accordion so we can check the weight.
			await page
				.getByRole( 'button', { name: 'Additional Information' } )
				.click();
			await expect( productPrice ).toHaveText( /\$42.00 – \$45.00.*/ );
			await expect( page.getByText( '100 in stock' ) ).toBeVisible();
			await expect( page.getByText( 'SKU: woo-hoodie' ) ).toBeVisible();
			await expect(
				page
					.getByLabel( 'Additional Information', { exact: true } )
					.getByText( '1.5 lbs' )
			).toBeVisible();
			await expect( page.getByText( variationDescription ) ).toBeHidden();
			const visibleImage =
				await productGalleryPageObject.getVisibleLargeImageId();
			expect( visibleImage ).toBe( '34' );

			await colorBlueOption.click();
			await logoNoOption.click();

			await expect( productPrice ).toHaveText( '$45.00' );
			await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
			await expect(
				page.getByText( 'SKU: woo-hoodie-blue' )
			).toBeVisible();
			await expect(
				page
					.getByLabel( 'Additional Information', { exact: true } )
					.getByText( '2 lbs' )
			).toBeVisible();
			await expect(
				page.getByText( variationDescription )
			).toBeVisible();
			await expect( async () => {
				const newVisibleLargeImageId =
					await productGalleryPageObject.getVisibleLargeImageId();

				expect( newVisibleLargeImageId ).toBe( '35' );
			} ).toPass( { timeout: 1_000 } );
		} );

		await test.step( 'resets blocks rendering variation data when attributes are deselected', async () => {
			await colorBlueOption.click();

			await expect( productPrice ).toHaveText( /\$42.00 – \$45.00.*/ );
			await expect( page.getByText( '100 in stock' ) ).toBeVisible();
			await expect( page.getByText( 'SKU: woo-hoodie' ) ).toBeVisible();
			await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
			await expect(
				page
					.getByLabel( 'Additional Information', { exact: true } )
					.getByText( '1.5 lbs' )
			).toBeVisible();
			await expect( page.getByText( variationDescription ) ).toBeHidden();
			await expect( async () => {
				const newVisibleLargeImageId =
					await productGalleryPageObject.getVisibleLargeImageId();

				expect( newVisibleLargeImageId ).toBe( '34' );
			} ).toPass( { timeout: 1_000 } );
		} );

		await test.step( 'successfully adds to cart when attributes are selected', async () => {
			await colorGreenOption.click();

			// Note: The button is always enabled for accessibility reasons.
			// Instead, we check directly for the "disabled" class, which grays
			// out the button.
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			await addToCartButton.click();

			await expect( page.getByText( '1 in cart' ) ).toBeVisible();
		} );

		await test.step( '"X in cart" text reflects the correct amount in variations', async () => {
			await colorRedOption.click();

			await expect( page.getByText( '1 in cart' ) ).toBeHidden();

			await colorGreenOption.click();

			await expect( page.getByText( '1 in cart' ) ).toBeVisible();
		} );
	} );

	test( 'allows adding grouped products to cart', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		// Make Hoodie with Logo to be sold individually.
		const cliOutput = await wpCLI(
			`post list --post_type=product --field=ID --name="Hoodie with Logo" --format=ids`
		);
		const hoodieWithLogoProductId = cliOutput.stdout.match( /\d+/g )?.pop();
		await wpCLI(
			`wc product update ${ hoodieWithLogoProductId } --sold_individually=true --user=1`
		);

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/logo-collection' );

		const addToCartButton = page
			.getByRole( 'button', { name: 'Add to cart' } )
			.first();

		await test.step( 'displays an error when attempting to add grouped products with zero quantity', async () => {
			await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );

			// There is the chance the button might be clicked before the iAPI
			// stores have been loaded.
			await expect( async () => {
				await addToCartButton.click();
				await expect(
					page.getByText(
						'Please select some products to add to the cart.'
					)
				).toBeVisible();
			} ).toPass();
		} );

		await test.step( 'successfully adds to cart when child products are selected', async () => {
			const increaseQuantityButton = page
				.locator(
					'[data-block-name="woocommerce/add-to-cart-with-options"]'
				)
				.getByLabel( 'Increase quantity of Beanie' );
			await increaseQuantityButton.click();

			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			await increaseQuantityButton.click();

			await addToCartButton.click();

			await expect(
				page.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();

			await expect(
				page.getByLabel(
					config.features[ 'experimental-iapi-mini-cart' ]
						? 'Number of items in the cart: 2'
						: '2 items in cart'
				)
			).toBeVisible();
		} );

		await test.step( 'child simple product quantities can be decreased down to 0', async () => {
			const reduceQuantityButton = page
				.locator(
					'[data-block-name="woocommerce/add-to-cart-with-options-grouped-product-item-selector"]'
				)
				.getByLabel( 'Reduce quantity of Beanie' );
			await reduceQuantityButton.click();
			await reduceQuantityButton.click();

			const quantityInput = page.getByRole( 'spinbutton', {
				name: 'Beanie',
			} );

			await expect( quantityInput ).toHaveValue( '0' );

			await expect( reduceQuantityButton ).toBeDisabled();

			await expect(
				page.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toHaveClass( /\bdisabled\b/ );
		} );

		await test.step( 'products sold individually can be added to cart', async () => {
			await page.reload();

			const individuallySoldProductCheckbox = page.getByRole(
				'checkbox',
				{ name: 'Buy one of Hoodie with Logo' }
			);
			await individuallySoldProductCheckbox.click();

			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			await addToCartButton.click();

			// Wait for the add to cart request to complete before proceeding.
			// This prevents a race condition where the subsequent page.reload()
			// could execute before the product is fully added to the cart.
			const addToCartRequest = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);

			await expect(
				page.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();

			// Wait for the API response to ensure the DB has been updated.
			await page.waitForResponse( '**/wp-json/wc/store/v1/cart**' );

			await expect(
				page.getByLabel(
					config.features[ 'experimental-iapi-mini-cart' ]
						? 'Number of items in the cart: 3'
						: '3 items in cart'
				)
			).toBeVisible();

			await addToCartRequest;
		} );

		await test.step( 'if one product succeeds and another fails, optimistic updates are applied and an error is displayed', async () => {
			await page.reload();

			// Try to add the individually sold product to cart again (it will fail).
			const individuallySoldProductCheckbox = page.getByRole(
				'checkbox',
				{ name: 'Buy one of Hoodie with Logo' }
			);
			await individuallySoldProductCheckbox.click();

			// Try to add another product to cart again (it will succeed).
			const beanieIncreaseQuantityButton = page
				.locator(
					'[data-block-name="woocommerce/add-to-cart-with-options"]'
				)
				.getByLabel( 'Increase quantity of Beanie' );
			await beanieIncreaseQuantityButton.click();

			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );
			await addToCartButton.click();

			// Verify button updated successfully.
			await expect(
				page.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();
			// Verify error message is displayed.
			await expect(
				page.getByText(
					'The quantity of "Hoodie with Logo" cannot be changed'
				)
			).toBeVisible();
			// Verify optimistic updates were applied, so the product that was
			// successfully added to cart is counted.
			await expect(
				page.getByLabel(
					config.features[ 'experimental-iapi-mini-cart' ]
						? 'Number of items in the cart: 4'
						: '4 items in cart'
				)
			).toBeVisible();
		} );
	} );

	test( "doesn't allow selecting invalid variations in pills mode", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		// The radio input is visually hidden and, thus, not clickable. That's
		// why we need to select the <label> instead.
		const logoYesOption = page.locator( 'label:has-text("Yes")' );
		const colorGreenOption = page.locator( 'label:has-text("Green")' );

		await expect( colorGreenOption ).toBeEnabled();

		await logoYesOption.click();

		await expect( colorGreenOption ).toBeDisabled();
	} );

	test( "doesn't allow selecting invalid variations in dropdown mode", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await pageObject.switchProductType( 'Variable product' );

		await page.getByRole( 'tab', { name: 'Block' } ).click();

		// Verify inner blocks have loaded.
		await expect(
			editor.canvas
				.getByLabel(
					'Block: Variation Selector: Attribute Options (Beta)'
				)
				.first()
		).toBeVisible();

		const attributeOptionsBlock = await editor.getBlockByName(
			'woocommerce/add-to-cart-with-options-variation-selector-attribute-options'
		);
		await editor.selectBlocks( attributeOptionsBlock.first() );

		await page.getByRole( 'radio', { name: 'Dropdown' } ).click();

		await editor.saveSiteEditorEntities();

		await page.goto( '/product/hoodie/' );

		let colorGreenOption = page.getByRole( 'option', {
			name: 'Green',
			exact: true,
		} );

		// Workaround for the template not being updated on the first load.
		if ( ! ( await colorGreenOption.isVisible() ) ) {
			await page.reload();
			colorGreenOption = page.getByRole( 'option', {
				name: 'Green',
				exact: true,
			} );
		}

		await expect( colorGreenOption ).toBeEnabled();

		await page.getByLabel( 'Logo', { exact: true } ).selectOption( 'Yes' );

		await expect( colorGreenOption ).toBeDisabled();
	} );

	test( 'respects quantity constraints', async ( {
		page,
		pageObject,
		editor,
		requestUtils,
	} ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-quantity-constraints'
		);
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await test.step( 'in simple products', async () => {
			await page.goto( '/product/t-shirt/' );

			const quantityInput = page.getByLabel( 'Product quantity' );

			await expect( quantityInput ).toHaveValue( '4' );

			const reduceQuantityButton = page.getByLabel(
				'Reduce quantity of T-Shirt'
			);
			await expect( reduceQuantityButton ).toBeDisabled();

			const increaseQuantityButton = page.getByLabel(
				'Increase quantity of T-Shirt'
			);
			await increaseQuantityButton.click();

			await expect( quantityInput ).toHaveValue( '6' );

			await quantityInput.fill( '8' );
			await quantityInput.blur();

			await expect( increaseQuantityButton ).toBeDisabled();

			const addToCartButton = page.getByRole( 'button', {
				name: 'Add to cart: “T-Shirt”',
			} );

			await test.step( 'make sure quantities below min are not allowed even when manually filled but they persist in the input field', async () => {
				await quantityInput.fill( '3' );
				await quantityInput.blur();
				await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
				await expect( reduceQuantityButton ).toBeDisabled();
				await expect( increaseQuantityButton ).toBeEnabled();
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '3' );
			} );

			await test.step( 'verify 0 is reset in simple products', async () => {
				await quantityInput.fill( '0' );
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '4' );
				await expect( addToCartButton ).not.toHaveClass(
					/\bdisabled\b/
				);
			} );

			await test.step( 'verify setting the input to an empty string resets the value to the min', async () => {
				await quantityInput.fill( '' );
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '4' );
				await expect( addToCartButton ).not.toHaveClass(
					/\bdisabled\b/
				);
			} );

			await test.step( 'verify letters are reset to min value in simple products', async () => {
				// Playwright doesn't support filling a numeric input with a
				// string, but we still want to test this case as users are able
				// to type letters directly in the input field.
				await quantityInput.evaluate( ( element: HTMLInputElement ) => {
					element.value = 'abc';
					element.focus();
					requestAnimationFrame( () => {
						element.blur();
					} );
				} );
				await expect( quantityInput ).toHaveValue( '4' );
				await expect( addToCartButton ).not.toHaveClass(
					/\bdisabled\b/
				);
			} );
		} );

		await test.step( 'in variable products', async () => {
			await page.goto( '/product/hoodie/' );

			const quantityInput = page.getByRole( 'spinbutton', {
				name: 'Product quantity',
			} );

			await expect( quantityInput ).toHaveValue( '1' );

			const colorBlueOption = page.locator( 'label:has-text("Blue")' );
			const logoNoOption = page.locator( 'label:has-text("No")' );

			await colorBlueOption.click();
			await logoNoOption.click();

			await expect( quantityInput ).toHaveValue( '4' );

			const logoYesOption = page.locator( 'label:has-text("Yes")' );
			await logoYesOption.click();

			await expect( quantityInput ).toHaveValue( '4' );

			await quantityInput.fill( '10' );
			await quantityInput.blur();

			await expect( quantityInput ).toHaveValue( '10' );

			await logoNoOption.click();

			await expect( quantityInput ).toHaveValue( '8' );

			const addToCartButton = page.getByRole( 'button', {
				name: 'Add to cart',
				exact: true,
			} );

			await test.step( 'verify 0 is reset in variable products', async () => {
				await quantityInput.fill( '0' );
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '4' );
				await expect( addToCartButton ).not.toHaveClass(
					/\bdisabled\b/
				);
			} );

			await test.step( 'verify setting the input to an empty string resets the value to the min', async () => {
				await quantityInput.fill( '' );
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '4' );
				await expect( addToCartButton ).not.toHaveClass(
					/\bdisabled\b/
				);
			} );

			await test.step( 'verify letters are reset to min value in variable products', async () => {
				// Playwright doesn't support filling a numeric input with a
				// string, but we still want to test this case as users are able
				// to type letters directly in the input field.
				await quantityInput.evaluate( ( element: HTMLInputElement ) => {
					element.value = 'abc';
					element.focus();
					requestAnimationFrame( () => {
						element.blur();
					} );
				} );
				await expect( quantityInput ).toHaveValue( '4' );
				await expect( addToCartButton ).not.toHaveClass(
					/\bdisabled\b/
				);
			} );

			await test.step( 'hides Product Quantity input when the product is sold individually', async () => {
				await expect( quantityInput ).toBeVisible();

				const colorGreenOption = page.locator(
					'label:has-text("Green")'
				);
				await colorGreenOption.click();

				await expect( quantityInput ).toBeHidden();
			} );
		} );

		await test.step( 'in grouped products', async () => {
			await page.goto( '/product/logo-collection/' );

			const quantityInput = page.getByRole( 'spinbutton', {
				name: 'T-Shirt',
			} );

			await expect( quantityInput ).toHaveValue( '' );
			const increaseQuantityButton = page.getByLabel(
				'Increase quantity of T-Shirt'
			);
			await increaseQuantityButton.click();

			await expect( quantityInput ).toHaveValue( '4' );

			await increaseQuantityButton.click();

			await quantityInput.fill( '8' );
			await quantityInput.blur();

			await expect( increaseQuantityButton ).toBeDisabled();

			// Values can be decreased down to 0.
			const reduceQuantityButton = page.getByLabel(
				'Reduce quantity of T-Shirt'
			);

			await reduceQuantityButton.click();

			await expect( quantityInput ).toHaveValue( '6' );

			await quantityInput.fill( '5' );
			await quantityInput.blur();

			await reduceQuantityButton.click();

			await expect( quantityInput ).toHaveValue( '4' );

			await reduceQuantityButton.click();

			await expect( quantityInput ).toHaveValue( '0' );

			await expect( reduceQuantityButton ).toBeDisabled();

			const addToCartButton = page.getByRole( 'button', {
				name: 'Add to cart',
			} );

			await test.step( 'make sure quantities below min are not allowed even when manually filled but they persist in the input field', async () => {
				await quantityInput.fill( '3' );
				await quantityInput.blur();
				await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
				await expect( reduceQuantityButton ).toBeEnabled();
				await expect( increaseQuantityButton ).toBeEnabled();
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '3' );
			} );

			await test.step( 'verify 0 is not reset in grouped products', async () => {
				await quantityInput.fill( '0' );
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '0' );
				await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
			} );

			await test.step( 'verify empty strings are not reset in grouped products', async () => {
				await quantityInput.fill( '' );
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '' );
				await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
			} );

			await test.step( 'verify letters are reset to an empty string in grouped products', async () => {
				// Playwright doesn't support filling a numeric input with a
				// string, but we still want to test this case as users are able
				// to type letters directly in the input field.
				await quantityInput.evaluate( ( element: HTMLInputElement ) => {
					element.value = 'abc';
					element.focus();
					requestAnimationFrame( () => {
						element.blur();
					} );
				} );
				await expect( quantityInput ).toHaveValue( '' );
				await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
			} );
		} );
	} );

	test( "allows adding products to cart when the 'Enable AJAX add to cart buttons' setting is disabled", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await wpCLI( `option set woocommerce_enable_ajax_add_to_cart no` );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/t-shirt' );

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
		} );

		await addToCartButton.click();

		await expect( addToCartButton ).toHaveText( '1 in cart' );
	} );

	test( "allows adding simple products to cart when the 'Redirect to cart after successful addition' setting is enabled", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await wpCLI( `option set woocommerce_cart_redirect_after_add yes` );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/t-shirt' );

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
		} );

		await addToCartButton.click();

		await expect(
			page.getByLabel( 'Quantity of T-Shirt in your cart.' )
		).toHaveValue( '1' );
	} );

	test( "allows adding variable products to cart when the 'Redirect to cart after successful addition' setting is enabled", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await wpCLI( `option set woocommerce_cart_redirect_after_add yes` );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie' );

		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const logoYesOption = page.locator( 'label:has-text("Yes")' );

		await colorBlueOption.click();
		await logoYesOption.click();

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
		} );

		await addToCartButton.click();

		await expect(
			page.getByLabel( 'Quantity of Hoodie in your cart.' )
		).toHaveValue( '1' );
	} );

	test( "allows adding grouped products to cart when the 'Redirect to cart after successful addition' setting is enabled", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await wpCLI( `option set woocommerce_cart_redirect_after_add yes` );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/logo-collection' );

		const increaseQuantityButton = page.getByLabel(
			'Increase quantity of T-Shirt'
		);
		await increaseQuantityButton.click();

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
		} );

		await addToCartButton.click();

		await expect(
			page.getByLabel( 'Quantity of T-Shirt in your cart.' )
		).toHaveValue( '1' );
	} );

	test( 'allows adding simple products to cart when inside the Product block', async ( {
		page,
		pageObject,
	} ) => {
		await pageObject.createPostWithProductBlock( 't-shirt' );

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
		} );

		await addToCartButton.click();

		await expect( addToCartButton ).toHaveText( '1 in cart' );
	} );

	test( 'allows adding variable products to cart when inside the Product block', async ( {
		page,
		pageObject,
	} ) => {
		await pageObject.createPostWithProductBlock( 'hoodie' );

		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const logoYesOption = page.locator( 'label:has-text("Yes")' );

		await colorBlueOption.click();
		await logoYesOption.click();

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
			exact: true,
		} );

		await addToCartButton.click();

		await expect(
			page.getByRole( 'button', { name: '1 in cart', exact: true } )
		).toBeVisible();
	} );

	test( 'allows adding grouped products to cart when inside the Product block', async ( {
		page,
		pageObject,
	} ) => {
		await pageObject.createPostWithProductBlock( 'logo-collection' );

		const increaseQuantityButton = page.getByLabel(
			'Increase quantity of T-Shirt'
		);
		await increaseQuantityButton.click();

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
			exact: true,
		} );

		await addToCartButton.click();

		await expect(
			page.getByRole( 'button', { name: 'Added to cart', exact: true } )
		).toBeVisible();
	} );
} );

test.describe( 'Add to Cart + Options autoselect behavior', () => {
	const productName = `Cart product test ${ Date.now() }`;
	const productSlug = 'autoselect-t-shirt';
	const productPermalink = '/product/' + productSlug;
	const productPrice = '13.99';
	let productId;
	let commonSetupExecuted = false;
	const productAttributes = [ // These reflect the values in sample_products.xml
		{
			name: "Type",
			options: [
				"T-shirt",
			],
			variation: true,
			visible: true,
		},
		{
			name: "Color",
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
	const productVariations = [ // These reflect the values in sample_products.xml
		{
			regular_price: productPrice,
			attributes: [
				{
					name: "Type",
					option: "T-shirt"
				},
				{
					name: "Color",
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
					name: "Color",
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
					name: "Color",
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
					name: "Color",
					option: "Blue"
				},
				{
					name: "Size",
					option: "XL"
				},
			]
		},
	]

	async function goToProductTemplateEditor ( pageObject, editor ) {
		await pageObject.updateSingleProductTemplate();
		await editor.setPreferences( 'core/edit-site', {
			welcomeGuide: false,
		} );
		await editor.openDocumentSettingsSidebar();
	}

	async function setCartBlockAttributes(
		pageObject,
		editor,
		{ optionStyle, autoselect, disabledAttributesAction } = {
			optionStyle: undefined,
			autoselect: false,
			disabledAttributesAction: 'disable',
		},
	) {
		const page = editor.page;
		let isOnlyCurrentEntityDirty = true;

		await pageObject.switchProductType( 'Variable product' );
		await page.getByRole( 'tab', { name: 'Block' } ).click();
		const blockCart = await editor.canvas.getByLabel( 'Block: Add to Cart + Options' );
		blockCart.click();
		await blockCart.getByLabel( 'Block: Variation Selector: Attribute Options' ).first().click();

		const optionStyleInput = await page.getByRole( 'radio', { name: optionStyle, exact: true } )
		if ( ! await optionStyleInput.isChecked() ) {
			isOnlyCurrentEntityDirty = false;
			await optionStyleInput.click();
		}

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
		if (
			await page.getByRole('region', {
				name: 'Editor top bar',
			})
			.getByRole('button', {
				name: 'Save',
				exact: true,
			})
			.isEnabled()
		) {
			await editor.saveSiteEditorEntities( { isOnlyCurrentEntityDirty: isOnlyCurrentEntityDirty } );
		}
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

	test.beforeAll( async () => {
		let cliOutput = await wpCLI(
			`post list --post_type=product --field=ID --name="{ productSlug }" --format=ids`
		);
		productId = cliOutput.stdout.match( /\d+/g )?.pop();
	} );

	test.beforeEach( async ( {
		pageObject,
		editor,
	} ) => {
		// Tests expect to start in editor of product template
		await goToProductTemplateEditor( pageObject, editor );
	} );

	for ( const optionStyle of [ 'Pills', 'Dropdown' ] ) {
		test( `${ optionStyle }: Add to Cart + Options: Auto-select should work (on page load)`, async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await test.step( `${ optionStyle }: Set the autoselect setting to false`, async () => {
				await setCartBlockAttributes( pageObject, editor, { optionStyle: optionStyle, autoselect: false } );
			} );
			await test.step( `${ optionStyle }: Expect NOTHING to be auto-selected (on page load)`, async () => {
				await page.goto( productPermalink );

				await expectSelectedAttributes( page, { Type: '', Color: '', Size: '' }, optionStyle );
			} );

			await test.step( `${ optionStyle }: Set the autoselect setting to true`, async () => {
				await goToProductTemplateEditor( pageObject, editor );
				await setCartBlockAttributes( pageObject, editor, { optionStyle: optionStyle, autoselect: true } );
			} );
			await test.step( `${ optionStyle }: Expect only the Type to be auto-selected (on page load)`, async () => {
				await page.goto( productPermalink );

				// Expect Size to be auto-selected (on page load) to "T-shirt", the rest of the attributes should not be selected.
				await expectSelectedAttributes( page, { Type: 'T-shirt', Color: '', Size: '' }, optionStyle );
			} );
		} );
		test( `${ optionStyle }: Add to Cart + Options: Auto-select on user selection should work`, async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await test.step( `${ optionStyle }: Set the autoselect setting to false`, async () => {
				await setCartBlockAttributes( pageObject, editor, { optionStyle: optionStyle, autoselect: false } );
			} );
			await test.step( `${ optionStyle }: Expect attributes to NOT auto-select when user selects something`, async () => {
				await page.goto( productPermalink );

				// Expect nothing to be auto-selected
				await selectBlockAttribute( page, 'Color', 'Blue', optionStyle );

				await expectSelectedAttributes( page, { Type: '', Color: 'Blue', Size: '' }, optionStyle );
			} );

			await test.step( `${ optionStyle }: Set the autoselect setting to true`, async () => {
				await goToProductTemplateEditor( pageObject, editor );
				await setCartBlockAttributes( pageObject, editor, { optionStyle: optionStyle, autoselect: true } );
			} );
			await test.step( `${ optionStyle }: Expect attributes to auto-select when user selects something`, async () => {
				await page.goto( productPermalink );

				// By setting the Color to "Blue", we expect the Type to be auto-selected to "T-shirt", and the Size to "XL".
				await selectBlockAttribute( page, 'Color', 'Blue', optionStyle );

				await expectSelectedAttributes( page, { Type: 'T-shirt', Color: 'Blue', Size: 'XL' }, optionStyle );
			} );
		} );
		test( `${ optionStyle }: Add to Cart + Options: Test the multiple choices of the Values in conflict setting`, async ( {
			page,
			pageObject,
			editor,
		} ) => {
			async function setDisabledAttributesAction( value ) {
				await test.step( `${ optionStyle }: Set the unattached_attribute_action setting to "${ value }"`, async () => {
					await setCartBlockAttributes( pageObject, editor, { optionStyle: optionStyle, disabledAttributesAction: value } );
				} );
			}
			async function preselect() {
				await page.goto( productPermalink );

				// By setting the Color to "Blue", the only possible Size remaining is "XL".
				await selectBlockAttribute( page, 'Color', 'Blue', optionStyle );
			}

			await setDisabledAttributesAction( 'hide' );
			await test.step( `${ optionStyle }: Expect unattached options to be hidden (by attribute)`, async () => {
				await preselect();

				await expect(
					page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
				).not.toBeVisible();
			} );

			await goToProductTemplateEditor( pageObject, editor );
			await setDisabledAttributesAction( 'disable' );
			await test.step( `${ optionStyle }: Expect unattached options to be disabled (by prop)`, async () => {
				await preselect();

				await expect(
					page.getByLabel( 'Size' ).getByText( 'L', { exact: true } )
				).toBeDisabled();
			} );
		} );
		test( `${ optionStyle }: Add to Cart + Options: Combining Auto-select on user selection and Values in conflict settings should work`, async ( {
			page,
			pageObject,
			editor,
		} ) => {
			async function preselect() {
				await page.goto( productPermalink );

				// By setting the Color to "Blue", the only possible Size remaining is "XL".
				await selectBlockAttribute( page, 'Color', 'Blue', optionStyle );
				// Now, we deselect the Color.
				await selectBlockAttribute( page, 'Color', '', optionStyle );
				// Now, the options should look like this:
				// Type: T-shirt
				// Color: ''
				// Size: XL
				// Because the Size is XL, the only Colors possible are Red and Blue.
				// Now if we select Size: S, the Color should auto-select to Green.
				await selectBlockAttribute( page, 'Size', 'S', optionStyle );
				// Now, the options should look like this:
				// Type: T-shirt
				// Color: Green
				// Size: S
			}

			for ( const value of [ 'hide', 'disable' ] ) {
				await goToProductTemplateEditor( pageObject, editor );

				await test.step( `${ optionStyle }: Set the disabled_attribute_action setting to "${ value }"`, async () => {
					await setCartBlockAttributes( pageObject, editor, { autoselect: true, optionStyle: optionStyle, disabledAttributesAction: value } );
				} );
				await test.step( `unattachedAttributesAction === ${ value }: Expect options to be properly auto-selected`, async () => {
					await preselect();

					await expectSelectedAttributes( page, { Type: 'T-shirt', Color: 'Green', Size: 'S' }, optionStyle );
				} );
			}
		} );
	}
} );
