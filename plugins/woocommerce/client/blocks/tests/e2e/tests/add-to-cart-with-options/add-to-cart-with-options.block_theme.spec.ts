/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';
import { ProductGalleryPage } from '../product-gallery/product-gallery.page';

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
			`wc product_variation update ${ hoodieProductId } ${ hoodieProductVariationId } --manage_stock=true --in_stock=false --weight=2 --user=1`
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

		// The radio input is visually hidden and, thus, not clickable. That's
		// why we need to select the <label> instead.
		const logoNoOption = page.locator( 'label:has-text("No")' );
		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const colorGreenOption = page.locator( 'label:has-text("Green")' );
		const colorRedOption = page.locator( 'label:has-text("Red")' );
		const addToCartButton = page.getByText( 'Add to cart' ).first();
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

			await expect( async () => {
				const newVisibleLargeImageId =
					await productGalleryPageObject.getVisibleLargeImageId();

				expect( newVisibleLargeImageId ).toBe( '35' );
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
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/logo-collection' );

		const addToCartButton = page.getByText( 'Add to cart' ).first();

		await test.step( 'displays an error when attempting to add grouped products with zero quantity', async () => {
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
			const increaseQuantityButton = page.getByLabel(
				'Increase quantity of Beanie'
			);
			await increaseQuantityButton.click();
			await increaseQuantityButton.click();

			await addToCartButton.click();

			await expect( page.getByText( 'Added to cart' ) ).toBeVisible();

			await expect( page.getByLabel( '2 items in cart' ) ).toBeVisible();
		} );

		await test.step( 'child simple product quantities can be decreased down to 0', async () => {
			const reduceQuantityButton = page.getByLabel(
				'Reduce quantity of Beanie'
			);
			await reduceQuantityButton.click();
			await reduceQuantityButton.click();

			const quantityInput = page.getByRole( 'spinbutton', {
				name: 'Beanie',
			} );

			await expect( quantityInput ).toHaveValue( '0' );

			await expect( reduceQuantityButton ).toBeDisabled();
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
} );
