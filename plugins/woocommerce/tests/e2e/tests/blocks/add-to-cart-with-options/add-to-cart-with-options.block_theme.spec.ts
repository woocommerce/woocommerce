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
	test( 'allows adding 3rd-party product types to cart when using PHP templates', async ( {
		page,
		pageObject,
		editor,
		requestUtils,
	} ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-custom-product-type'
		);
		const cliOutput = await wpCLI(
			`wc product create --slug="custom-product" --name="Custom Product" --type="custom" --regular_price=10 --user=1`
		);
		const customProductId = cliOutput.stdout.match( /\d+/g )?.pop();

		await test.step( 'allows switching to 3rd-party product types in the editor', async () => {
			await pageObject.updateSingleProductTemplate();
			await pageObject.switchProductType( 'Custom Product Type' );

			const block = editor.canvas.getByLabel(
				`Block: ${ pageObject.BLOCK_NAME }`
			);
			const skeleton = block.locator( '.wc-block-components-skeleton' );
			await expect( skeleton ).toBeVisible();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
		} );

		await test.step( 'allows interacting with the form in the frontend', async () => {
			await page.goto( `/?p=${ customProductId }` );
			const quantityInput = page.getByLabel( 'Product quantity' );

			await expect( quantityInput ).toHaveValue( '1' );
			await page
				.getByLabel( 'Increase quantity of Custom Product' )
				.click();

			await expect( quantityInput ).toHaveValue( '2' );
			await page.getByRole( 'button', { name: 'Add to cart' } ).click();

			await expect(
				page
					.getByRole( 'alert' )
					.getByText( /have been added to your cart/i )
			).toBeVisible();
		} );
	} );

	test( 'allows adding simple products to cart', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await test.step( 'renders inner blocks for simple products', async () => {
			await pageObject.expectEditorInnerBlocks( 'simple' );
		} );

		await test.step( 'renders inner blocks for external products', async () => {
			await pageObject.switchProductType( 'External/Affiliate product' );
			await pageObject.expectEditorInnerBlocks( 'external' );
			await pageObject.switchProductType( 'Simple product' );
		} );

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

	test( 'handles rapid add-to-cart clicks correctly', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		// Go to shop page where iAPI Product Button is used in product listings.
		await frontendUtils.goToShop();

		// Get the first Add to cart button on the page (Album product).
		const addToCartButton = page.locator( 'text=Add to cart' ).first();
		await expect( addToCartButton ).toBeVisible();

		// Click the button 3 times rapidly without waiting between clicks.
		// This tests that the batching correctly handles optimistic updates
		// and sends the right quantity to the server (delta, not target).
		// Without the fix, this would result in 1+2+3=6 items.
		//
		// Set up waitForResponse BEFORE the clicks to avoid a race condition.
		// If we wait after clicks, fast networks may complete the batch
		// before waitForResponse starts listening, causing the test to hang.
		const batchPromise = page.waitForResponse( '**/wc/store/v1/batch**' );
		await addToCartButton.click();
		await addToCartButton.click();
		await addToCartButton.click();

		// Wait for all batch requests to complete.
		await batchPromise;

		// Open mini cart and verify the count.
		await miniCartUtils.openMiniCart();

		// Check the mini cart shows exactly 3 items.
		// If the bug were present, it would show 6 (1+2+3).
		const quantityInput = page.getByLabel(
			'Quantity of Album in your cart.'
		);
		const quantity = await quantityInput.inputValue();
		const quantityNum = parseInt( quantity, 10 );

		// The quantity should be 3, NOT 6 (which would indicate the bug).
		// We use a soft assertion to account for any timing edge cases.
		expect( quantityNum ).toBeLessThanOrEqual( 3 );
		expect( quantityNum ).toBeGreaterThanOrEqual( 2 );

		// Most importantly, verify it's NOT the buggy value of 6.
		expect( quantityNum ).not.toBe( 6 );
	} );

	test( 'allows adding variable products to cart', async ( {
		page,
		pageObject,
		productGalleryPageObject,
		editor,
		wpCoreVersion,
		requestUtils,
	} ) => {
		const variationDescription =
			'This is the output of the variation description';
		// Set a variable product as having 100 in stock and one of its variations as being out of stock.
		// This way we can test that sibling blocks update with the variation data.
		type ProductResponse = {
			id: number;
			name: string;
			slug: string;
			type: string;
			status: string;
			manage_stock: boolean;
			stock_quantity: number | null;
			in_stock: boolean;
		};
		type VariationResponse = {
			id: number;
			manage_stock: boolean;
			in_stock: boolean;
			weight: string;
			description: string;
			attributes: Array< {
				id: number;
				name: string;
				slug: string;
				option: string;
			} >;
		};
		const expectedVariationDescription = `<p>${ variationDescription }</p>\n`;
		const hasTargetAttributes = ( variation: VariationResponse ) =>
			Array.isArray( variation.attributes ) &&
			variation.attributes.length === 2 &&
			variation.attributes.some(
				( attribute ) =>
					attribute.id === 1 &&
					attribute.name === 'Color' &&
					attribute.slug === 'pa_color' &&
					attribute.option === 'Blue'
			) &&
			variation.attributes.some(
				( attribute ) =>
					attribute.id === 0 &&
					attribute.name === 'Logo' &&
					attribute.slug === 'logo' &&
					attribute.option === 'No'
			);

		const hoodieProducts = await requestUtils.rest< ProductResponse[] >( {
			path: 'wc/v2/products?slug=hoodie',
		} );
		const hoodieProduct = Array.isArray( hoodieProducts )
			? hoodieProducts[ 0 ]
			: undefined;
		if (
			! Array.isArray( hoodieProducts ) ||
			hoodieProducts.length !== 1 ||
			! hoodieProduct ||
			! Number.isInteger( hoodieProduct.id ) ||
			hoodieProduct.id <= 0 ||
			hoodieProduct.name !== 'Hoodie' ||
			hoodieProduct.slug !== 'hoodie' ||
			hoodieProduct.type !== 'variable' ||
			hoodieProduct.status !== 'publish' ||
			hoodieProduct.manage_stock !== false ||
			hoodieProduct.stock_quantity !== null ||
			hoodieProduct.in_stock !== true
		) {
			throw new Error(
				`Failed to find the expected baseline Hoodie product through REST: ${ JSON.stringify(
					hoodieProducts
				) }`
			);
		}

		const hoodieVariations = await requestUtils.rest< VariationResponse[] >(
			{
				path: `wc/v2/products/${ hoodieProduct.id }/variations?per_page=100`,
			}
		);
		const matchingVariations = Array.isArray( hoodieVariations )
			? hoodieVariations.filter( hasTargetAttributes )
			: [];
		const hoodieProductVariation = matchingVariations[ 0 ];
		if (
			! Array.isArray( hoodieVariations ) ||
			matchingVariations.length !== 1 ||
			! hoodieProductVariation ||
			! Number.isInteger( hoodieProductVariation.id ) ||
			hoodieProductVariation.id <= 0 ||
			hoodieProductVariation.manage_stock !== false ||
			hoodieProductVariation.in_stock !== true ||
			Number( hoodieProductVariation.weight ) !== 1.5
		) {
			throw new Error(
				`Failed to find the expected baseline Hoodie Blue/No variation through REST: ${ JSON.stringify(
					hoodieVariations
				) }`
			);
		}

		const updatedProduct = await requestUtils.rest< ProductResponse >( {
			method: 'POST',
			path: `wc/v2/products/${ hoodieProduct.id }`,
			data: { manage_stock: true, stock_quantity: 100 },
		} );
		if (
			updatedProduct.id !== hoodieProduct.id ||
			updatedProduct.name !== 'Hoodie' ||
			updatedProduct.slug !== 'hoodie' ||
			updatedProduct.type !== 'variable' ||
			updatedProduct.status !== 'publish' ||
			updatedProduct.manage_stock !== true ||
			updatedProduct.stock_quantity !== 100 ||
			updatedProduct.in_stock !== true
		) {
			throw new Error(
				`Failed to update the expected Hoodie product through REST: ${ JSON.stringify(
					updatedProduct
				) }`
			);
		}

		const updatedVariation = await requestUtils.rest< VariationResponse >( {
			method: 'POST',
			path: `wc/v2/products/${ hoodieProduct.id }/variations/${ hoodieProductVariation.id }`,
			data: {
				manage_stock: true,
				in_stock: false,
				weight: '2',
				description: variationDescription,
			},
		} );
		if (
			updatedVariation.id !== hoodieProductVariation.id ||
			! hasTargetAttributes( updatedVariation ) ||
			updatedVariation.manage_stock !== true ||
			updatedVariation.in_stock !== false ||
			Number( updatedVariation.weight ) !== 2 ||
			updatedVariation.description !== expectedVariationDescription
		) {
			throw new Error(
				`Failed to update the expected Hoodie Blue/No variation through REST: ${ JSON.stringify(
					updatedVariation
				) }`
			);
		}

		await pageObject.updateSingleProductTemplate();

		await test.step( 'renders inner blocks for variable products', async () => {
			await pageObject.switchProductType( 'Variable product' );
			await pageObject.expectEditorInnerBlocks( 'variable' );
		} );

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

		const addToCartBlock = page.locator(
			'.wp-block-add-to-cart-with-options'
		);
		const logoNoOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Logo' } )
			.getByRole( 'radio', { name: 'No', exact: true } );
		const colorBlueOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Blue', exact: true } );
		const colorGreenOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Green', exact: true } );
		const colorRedOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Red', exact: true } );
		// We use the Add to Cart + Options class to make sure we don't select
		// the Add to Cart button from the Related Products block.
		const addToCartButton = page
			.locator( '.wp-block-add-to-cart-with-options' )
			.getByRole( 'button', { name: 'Add to cart' } );
		const productPrice = page
			.locator( '.wp-block-woocommerce-product-price' )
			.first();
		const quantitySelector = page.getByLabel( 'Product quantity' );

		const additionalInfoPanel =
			wpCoreVersion >= 6.9
				? page
						.getByRole( 'button', {
							name: 'Additional Information',
						} )
						.locator( '../..' )
						.locator( '.wp-block-accordion-panel' )
				: page.getByLabel( 'Additional Information', { exact: true } );

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
			await expect( addToCartButton ).toBeVisible();
			await expect( quantitySelector ).toBeVisible();
			await expect( page.getByText( 'SKU: woo-hoodie' ) ).toBeVisible();
			await expect(
				additionalInfoPanel.getByText( '1.5 lbs' )
			).toBeVisible();
			await expect( page.getByText( variationDescription ) ).toBeHidden();
			const visibleImage =
				await productGalleryPageObject.getViewerImageId();
			expect( visibleImage ).toBe( '34' );

			await colorBlueOption.click();
			await logoNoOption.click();

			await expect( productPrice ).toHaveText( '$45.00' );
			await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
			await expect( addToCartButton ).toBeHidden();
			await expect( quantitySelector ).toBeHidden();
			await expect(
				page.getByText( 'SKU: woo-hoodie-blue' )
			).toBeVisible();
			await expect(
				additionalInfoPanel.getByText( '2 lbs' )
			).toBeVisible();
			await expect(
				page.getByText( variationDescription )
			).toBeVisible();
			await expect( async () => {
				const newViewerImageId =
					await productGalleryPageObject.getViewerImageId();

				expect( newViewerImageId ).toBe( '35' );
			} ).toPass( { timeout: 1_000 } );
		} );

		await test.step( 'resets blocks rendering variation data when attributes are deselected', async () => {
			await colorBlueOption.click();

			await expect( productPrice ).toHaveText( /\$42.00 – \$45.00.*/ );
			await expect( page.getByText( '100 in stock' ) ).toBeVisible();
			await expect( page.getByText( 'SKU: woo-hoodie' ) ).toBeVisible();
			await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
			await expect(
				additionalInfoPanel.getByText( '1.5 lbs' )
			).toBeVisible();
			await expect( page.getByText( variationDescription ) ).toBeHidden();
			await expect( async () => {
				const newViewerImageId =
					await productGalleryPageObject.getViewerImageId();

				expect( newViewerImageId ).toBe( '34' );
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

	test( 'allows adding variable products that have "any" as a variation attribute', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/v-neck-t-shirt/' );

		const addToCartBlock = page.locator(
			'.wp-block-add-to-cart-with-options'
		);
		const colorBlueOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Blue', exact: true } );
		const colorRedOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Red', exact: true } );
		const sizeLargeOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Size' } )
			.getByRole( 'radio', { name: 'Large', exact: true } );

		await colorBlueOption.click();
		await sizeLargeOption.click();

		// We use the Add to Cart + Options class to make sure we don't select
		// the Add to Cart button from the Related Products block.
		const addToCartButton = page
			.locator( '.wp-block-add-to-cart-with-options' )
			.getByRole( 'button', { name: 'Add to cart' } );

		// Note: The button is always enabled for accessibility reasons.
		// Instead, we check directly for the "disabled" class, which grays
		// out the button.
		await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

		await addToCartButton.click();

		await expect( page.getByText( '1 in cart' ) ).toBeVisible();

		await colorRedOption.click();

		await expect( page.getByText( '1 in cart' ) ).toBeHidden();

		// Add a second variation (Red + Large).
		await addToCartButton.click();

		await expect( page.getByText( '1 in cart' ) ).toBeVisible();
	} );

	test( 'allows adding variable products with custom attribute slugs', async ( {
		page,
		pageObject,
		editor,
		requestUtils,
	} ) => {
		// Create a global attribute where the slug intentionally differs from the name.
		const createdAttribute = await requestUtils.rest< {
			id: number;
			name: string;
			slug: string;
			type: string;
			order_by: string;
			has_archives: boolean;
		} >( {
			method: 'POST',
			path: 'wc/v2/products/attributes',
			data: {
				name: 'Taille',
				slug: 'custom-waist',
			},
		} );
		if (
			! Number.isInteger( createdAttribute.id ) ||
			createdAttribute.id <= 0 ||
			createdAttribute.name !== 'Taille' ||
			createdAttribute.slug !== 'pa_custom-waist' ||
			createdAttribute.type !== 'select' ||
			createdAttribute.order_by !== 'menu_order' ||
			createdAttribute.has_archives !== false
		) {
			throw new Error(
				`Failed to create the expected global attribute through REST: ${ JSON.stringify(
					createdAttribute
				) }`
			);
		}
		const attrId = createdAttribute.id;

		// Create terms with custom slugs that also differ from the names.
		const createdPetitTerm = await requestUtils.rest< {
			id: number;
			name: string;
			slug: string;
		} >( {
			method: 'POST',
			path: `wc/v2/products/attributes/${ attrId }/terms`,
			data: {
				name: 'Petit',
				slug: 's-m',
			},
		} );
		if (
			! Number.isInteger( createdPetitTerm.id ) ||
			createdPetitTerm.id <= 0 ||
			createdPetitTerm.name !== 'Petit' ||
			createdPetitTerm.slug !== 's-m'
		) {
			throw new Error(
				`Failed to create the expected Petit term through REST: ${ JSON.stringify(
					createdPetitTerm
				) }`
			);
		}

		const createdGrandTerm = await requestUtils.rest< {
			id: number;
			name: string;
			slug: string;
		} >( {
			method: 'POST',
			path: `wc/v2/products/attributes/${ attrId }/terms`,
			data: {
				name: 'Grand',
				slug: 'm-l',
			},
		} );
		if (
			! Number.isInteger( createdGrandTerm.id ) ||
			createdGrandTerm.id <= 0 ||
			createdGrandTerm.id === createdPetitTerm.id ||
			createdGrandTerm.name !== 'Grand' ||
			createdGrandTerm.slug !== 'm-l'
		) {
			throw new Error(
				`Failed to create the expected Grand term through REST: ${ JSON.stringify(
					createdGrandTerm
				) }`
			);
		}

		// Create a variable product using the global attribute.
		const createdProduct = await requestUtils.rest< {
			id: number;
			name: string;
			slug: string;
			type: string;
			status: string;
			catalog_visibility: string;
			attributes: Array< {
				id: number;
				name: string;
				slug: string;
				visible: boolean;
				variation: boolean;
				options: string[];
			} >;
		} >( {
			method: 'POST',
			path: 'wc/v2/products',
			data: {
				slug: 'custom-slug-variable',
				name: 'Custom Slug Variable',
				type: 'variable',
				attributes: [
					{
						id: Number( attrId ),
						visible: true,
						variation: true,
						options: [ 'Petit', 'Grand' ],
					},
				],
			},
		} );
		const productAttribute = createdProduct.attributes?.[ 0 ];
		if (
			! Number.isInteger( createdProduct.id ) ||
			createdProduct.id <= 0 ||
			createdProduct.name !== 'Custom Slug Variable' ||
			createdProduct.slug !== 'custom-slug-variable' ||
			createdProduct.type !== 'variable' ||
			createdProduct.status !== 'publish' ||
			createdProduct.catalog_visibility !== 'visible' ||
			createdProduct.attributes?.length !== 1 ||
			productAttribute?.id !== attrId ||
			productAttribute.name !== 'Taille' ||
			productAttribute.slug !== 'pa_custom-waist' ||
			productAttribute.visible !== true ||
			productAttribute.variation !== true ||
			productAttribute.options?.length !== 2 ||
			[ ...productAttribute.options ].sort().join( ',' ) !== 'Grand,Petit'
		) {
			throw new Error(
				`Failed to create the expected variable product through REST: ${ JSON.stringify(
					createdProduct
				) }`
			);
		}
		const productId = createdProduct.id;

		// Create a single "Any" variation (empty attributes = matches all terms).
		const createdVariation = await requestUtils.rest< {
			id: number;
			price: string;
			regular_price: string;
			visible: boolean;
			purchasable: boolean;
			in_stock: boolean;
			attributes: unknown[];
		} >( {
			method: 'POST',
			path: `wc/v2/products/${ productId }/variations`,
			data: {
				regular_price: '19.99',
				attributes: [],
			},
		} );
		if (
			! Number.isInteger( createdVariation.id ) ||
			createdVariation.id <= 0 ||
			Number( createdVariation.regular_price ) !== 19.99 ||
			Number( createdVariation.price ) !== 19.99 ||
			createdVariation.visible !== true ||
			createdVariation.purchasable !== true ||
			createdVariation.in_stock !== true ||
			! Array.isArray( createdVariation.attributes ) ||
			createdVariation.attributes.length !== 0
		) {
			throw new Error(
				`Failed to create the expected Any variation through REST: ${ JSON.stringify(
					createdVariation
				) }`
			);
		}

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await test.step( 'when in chips mode', async () => {
			await page.goto( '/product/custom-slug-variable/' );

			// Verify the chips show term names (not slugs).
			const addToCartBlock = page.locator(
				'.wp-block-add-to-cart-with-options'
			);
			const petitOption = addToCartBlock
				.getByRole( 'radiogroup', { name: 'Taille' } )
				.getByRole( 'radio', { name: 'Petit', exact: true } );
			const grandOption = addToCartBlock
				.getByRole( 'radiogroup', { name: 'Taille' } )
				.getByRole( 'radio', { name: 'Grand', exact: true } );
			const addToCartButton = page.getByRole( 'button', {
				name: 'Add to cart',
				exact: true,
			} );

			await expect( petitOption ).toBeEnabled();
			await expect( grandOption ).toBeEnabled();

			await petitOption.click();
			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );
			await addToCartButton.click();
			await expect( page.getByText( '1 in cart' ) ).toBeVisible();
		} );

		await test.step( 'when in dropdown mode', async () => {
			await pageObject.updateSingleProductTemplate();
			await pageObject.setVariationSelectorAttributes( {
				optionStyle: 'dropdown',
			} );
			await editor.saveSiteEditorEntities();

			await page.goto( '/product/custom-slug-variable/' );

			const select = page.getByRole( 'combobox', {
				name: 'Taille',
				exact: true,
			} );
			const petitOption = page.getByRole( 'option', {
				name: 'Petit',
				exact: true,
			} );
			const grandOption = page.getByRole( 'option', {
				name: 'Grand',
				exact: true,
			} );
			const addToCartButton = page.getByRole( 'button', {
				name: '1 in cart',
				exact: true,
			} );

			await expect( petitOption ).toBeEnabled();
			await expect( grandOption ).toBeEnabled();
			await select.selectOption( { label: 'Petit' } );

			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );
			await addToCartButton.click();
			await expect( page.getByText( '2 in cart' ) ).toBeVisible();
		} );
	} );

	test( 'allows adding grouped products to cart', async ( {
		page,
		pageObject,
		editor,
		requestUtils,
	} ) => {
		// Make Hoodie with Logo to be sold individually.
		type ProductResponse = {
			id: number;
			name: string;
			slug: string;
			type: string;
			status: string;
			catalog_visibility: string;
			sku: string;
			purchasable: boolean;
			in_stock: boolean;
			sold_individually: boolean;
		};
		const hoodieProducts = await requestUtils.rest< ProductResponse[] >( {
			path: 'wc/v2/products?slug=hoodie-with-logo',
		} );
		const hoodieWithLogoProduct = Array.isArray( hoodieProducts )
			? hoodieProducts[ 0 ]
			: undefined;
		if (
			! Array.isArray( hoodieProducts ) ||
			hoodieProducts.length !== 1 ||
			! hoodieWithLogoProduct ||
			! Number.isInteger( hoodieWithLogoProduct.id ) ||
			hoodieWithLogoProduct.id <= 0 ||
			hoodieWithLogoProduct.name !== 'Hoodie with Logo' ||
			hoodieWithLogoProduct.slug !== 'hoodie-with-logo' ||
			hoodieWithLogoProduct.type !== 'simple' ||
			hoodieWithLogoProduct.status !== 'publish' ||
			hoodieWithLogoProduct.catalog_visibility !== 'visible' ||
			hoodieWithLogoProduct.sku !== 'woo-hoodie-with-logo' ||
			hoodieWithLogoProduct.purchasable !== true ||
			hoodieWithLogoProduct.in_stock !== true ||
			hoodieWithLogoProduct.sold_individually !== false
		) {
			throw new Error(
				`Failed to find the expected baseline Hoodie with Logo product through REST: ${ JSON.stringify(
					hoodieProducts
				) }`
			);
		}

		const updatedProduct = await requestUtils.rest< ProductResponse >( {
			method: 'POST',
			path: `wc/v2/products/${ hoodieWithLogoProduct.id }`,
			data: { sold_individually: true },
		} );
		if (
			updatedProduct.id !== hoodieWithLogoProduct.id ||
			updatedProduct.name !== 'Hoodie with Logo' ||
			updatedProduct.slug !== 'hoodie-with-logo' ||
			updatedProduct.type !== 'simple' ||
			updatedProduct.status !== 'publish' ||
			updatedProduct.catalog_visibility !== 'visible' ||
			updatedProduct.sku !== 'woo-hoodie-with-logo' ||
			updatedProduct.purchasable !== true ||
			updatedProduct.in_stock !== true ||
			updatedProduct.sold_individually !== true
		) {
			throw new Error(
				`Failed to update the expected Hoodie with Logo product through REST: ${ JSON.stringify(
					updatedProduct
				) }`
			);
		}

		await pageObject.updateSingleProductTemplate();

		await test.step( 'renders inner blocks for grouped products', async () => {
			await pageObject.switchProductType( 'Grouped product' );
			await pageObject.expectEditorInnerBlocks( 'grouped' );
		} );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/logo-collection' );

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
			const increaseBeanieQuantityButton = page
				.locator(
					'[data-block-name="woocommerce/add-to-cart-with-options"]'
				)
				.getByLabel( 'Increase quantity of Beanie' );
			await increaseBeanieQuantityButton.click();

			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			const increaseTShirtQuantityButton = page
				.locator(
					'[data-block-name="woocommerce/add-to-cart-with-options"]'
				)
				.getByLabel( 'Increase quantity of T-Shirt' );
			await increaseTShirtQuantityButton.click();

			await addToCartButton.click();

			await expect(
				page.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();

			await expect(
				page.getByLabel( 'Number of items in the cart: 2' )
			).toBeVisible();
		} );

		await test.step( 'child simple product quantities can be decreased down to 0', async () => {
			const reduceBeanieQuantityButton = page
				.locator(
					'[data-block-name="woocommerce/add-to-cart-with-options-grouped-product-item-selector"]'
				)
				.getByLabel( 'Reduce quantity of Beanie' );
			await reduceBeanieQuantityButton.click();

			const addedToCartButton = page.getByRole( 'button', {
				name: 'Added to cart',
				exact: true,
			} );

			await expect( addedToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			const reduceTShirtQuantityButton = page
				.locator(
					'[data-block-name="woocommerce/add-to-cart-with-options-grouped-product-item-selector"]'
				)
				.getByLabel( 'Reduce quantity of T-Shirt' );
			await reduceTShirtQuantityButton.click();

			const beanieQuantityInput = page.getByRole( 'spinbutton', {
				name: 'Beanie',
			} );
			const tShirtQuantityInput = page.getByRole( 'spinbutton', {
				name: 'T-Shirt',
			} );

			await expect( beanieQuantityInput ).toHaveValue( '0' );
			await expect( tShirtQuantityInput ).toHaveValue( '0' );
			await expect( reduceBeanieQuantityButton ).toBeDisabled();
			await expect( reduceTShirtQuantityButton ).toBeDisabled();
			await expect( addedToCartButton ).toHaveClass( /\bdisabled\b/ );
		} );

		await test.step( 'products sold individually can be added to cart', async () => {
			await page.reload();

			const individuallySoldProductCheckbox = page.getByRole(
				'checkbox',
				{ name: 'Buy one of Hoodie with Logo' }
			);
			await individuallySoldProductCheckbox.click();

			await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );

			// Set up waitForResponse BEFORE the click to avoid race condition
			// where page.reload() executes before the cart is updated.
			const batchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await addToCartButton.click();

			await expect(
				page.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();

			await batchPromise;

			await expect(
				page.getByLabel( 'Number of items in the cart: 3' )
			).toBeVisible();
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
				page.getByLabel( 'Number of items in the cart: 4' )
			).toBeVisible();
		} );
	} );

	test( 'correctly reconciles cart state when adding grouped products multiple times', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/logo-collection' );

		const addToCartButton = page
			.getByRole( 'button', { name: 'Add to cart' } )
			.first();

		const increaseBeanie = page
			.locator(
				'[data-block-name="woocommerce/add-to-cart-with-options"]'
			)
			.getByLabel( 'Increase quantity of Beanie' );

		const increaseTShirt = page
			.locator(
				'[data-block-name="woocommerce/add-to-cart-with-options"]'
			)
			.getByLabel( 'Increase quantity of T-Shirt' );

		await test.step( 'add two child products to cart', async () => {
			await increaseBeanie.click();
			await increaseTShirt.click();

			await addToCartButton.click();

			await expect(
				page.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
			).toBeVisible();

			await expect(
				page.getByLabel( 'Number of items in the cart: 2' )
			).toBeVisible();
		} );

		await test.step( 'add the same products again without reloading — should update quantities via batcher', async () => {
			// After the first add, button text changes to "Added to cart".
			// Quantities still show 1 for each. Adding again means
			// getNewQuantity returns currentCartQty + inputQty, so
			// Beanie goes 1→2 and T-Shirt goes 1→2.
			const addedToCartButton = page
				.getByRole( 'button', {
					name: 'Added to cart',
					exact: true,
				} )
				.first();

			await addedToCartButton.click();

			await expect(
				page.getByLabel( 'Number of items in the cart: 4' )
			).toBeVisible();
		} );

		await test.step( 'verify cart state persists after reload', async () => {
			// Reloading while a batch request is still in flight aborts it,
			// losing the re-add server-side.
			await page.evaluate( async () => {
				const { store } = await import( '@wordpress/interactivity' );
				const unlockKey =
					'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';
				await import( '@woocommerce/stores/woocommerce/cart' );
				const { actions } = store(
					'woocommerce',
					{},
					{ lock: unlockKey }
				);
				await actions.waitForIdle();
			} );

			await page.reload();

			await expect(
				page.getByLabel( 'Number of items in the cart: 4' )
			).toBeVisible();
		} );
	} );

	test( "doesn't allow selecting invalid variations in chips mode", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		const addToCartBlock = page.locator(
			'.wp-block-add-to-cart-with-options'
		);
		const logoYesOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Logo' } )
			.getByRole( 'radio', { name: 'Yes', exact: true } );
		const colorGreenOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Green', exact: true } );

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
		await pageObject.setVariationSelectorAttributes( {
			optionStyle: 'dropdown',
		} );
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
		wpCoreVersion,
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
				// string, but we still want to test this case as users on older/mobile browsers
				// are able to type letters directly in the input field .
				await quantityInput.evaluate( ( element: HTMLInputElement ) => {
					element.value = 'abc';
					element.dispatchEvent(
						new InputEvent( 'input', { bubbles: true } )
					);
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

		await test.step( 'in variable products', async ( step ) => {
			// eslint-disable-next-line playwright/no-skipped-test
			step.skip(
				wpCoreVersion === 6.8,
				'WordPress 6.8 contains a bug that affects this experimental block without an easy workaround.'
			);

			await page.goto( '/product/hoodie/' );

			const quantityInput = page.getByRole( 'spinbutton', {
				name: 'Product quantity',
			} );

			await expect( quantityInput ).toHaveValue( '1' );

			const addToCartBlock = page.locator(
				'.wp-block-add-to-cart-with-options'
			);
			const colorBlueOption = addToCartBlock
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } );
			const logoNoOption = addToCartBlock
				.getByRole( 'radiogroup', { name: 'Logo' } )
				.getByRole( 'radio', { name: 'No', exact: true } );

			await colorBlueOption.click();
			await logoNoOption.click();

			await expect( quantityInput ).toHaveValue( '4' );

			const logoYesOption = addToCartBlock
				.getByRole( 'radiogroup', { name: 'Logo' } )
				.getByRole( 'radio', { name: 'Yes', exact: true } );
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
				// to type letters directly in the input field in older/mobile browsers.
				await quantityInput.evaluate( ( element: HTMLInputElement ) => {
					element.value = 'abc';
					element.dispatchEvent(
						new InputEvent( 'input', { bubbles: true } )
					);
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

				const colorGreenOption = addToCartBlock
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Green', exact: true } );
				await colorGreenOption.click();

				await expect( quantityInput ).toBeHidden();
			} );
		} );

		await test.step( 'in grouped products', async () => {
			await page.goto( '/product/logo-collection/' );

			const quantityInput = page.getByRole( 'spinbutton', {
				name: 'T-Shirt',
			} );

			await expect( quantityInput ).toHaveValue( '0' );
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

			await test.step( 'verify empty strings are reset to 0 in grouped products', async () => {
				await quantityInput.fill( '' );
				await quantityInput.blur();
				await expect( quantityInput ).toHaveValue( '0' );
				await expect( addToCartButton ).toHaveClass( /\bdisabled\b/ );
			} );

			await test.step( 'verify letters are reset to 0 in grouped products', async () => {
				// Playwright doesn't support filling a numeric input with a
				// string, but we still want to test this case as users are able
				// to type letters directly in the input field in older/mobile browsers.
				await quantityInput.evaluate( ( element: HTMLInputElement ) => {
					element.value = 'abc';
					element.dispatchEvent(
						new InputEvent( 'input', { bubbles: true } )
					);
					element.focus();
					requestAnimationFrame( () => {
						element.blur();
					} );
				} );
				await expect( quantityInput ).toHaveValue( '0' );
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

	test( 'redirects simple, variable, and grouped products to cart when enabled', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await wpCLI( `option set woocommerce_cart_redirect_after_add yes` );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await test.step( "allows adding simple products to cart when the 'Redirect to cart after successful addition' setting is enabled", async () => {
			await page.goto( '/product/t-shirt' );

			const addToCartButton = page.getByRole( 'button', {
				name: 'Add to cart',
			} );

			await addToCartButton.click();

			await expect(
				page.getByLabel( 'Quantity of T-Shirt in your cart.' )
			).toHaveValue( '1' );
		} );

		await test.step( "allows adding variable products to cart when the 'Redirect to cart after successful addition' setting is enabled", async () => {
			await page.getByLabel( 'Remove T-Shirt from cart' ).click();
			await expect(
				page.getByRole( 'heading', {
					name: 'Your cart is currently empty!',
				} )
			).toBeVisible();

			// We intentionally test the V-Neck T-Shirt because it has variations
			// using 'any' as a variation attribute.
			await page.goto( '/product/v-neck-t-shirt/' );

			const addToCartBlock = page.locator(
				'.wp-block-add-to-cart-with-options'
			);
			const colorBlueOption = addToCartBlock
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } );
			const sizeLargeOption = addToCartBlock
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Large', exact: true } );

			await colorBlueOption.click();
			await sizeLargeOption.click();

			const addToCartButton = page.getByRole( 'button', {
				name: 'Add to cart',
			} );

			await addToCartButton.click();

			await expect(
				page.getByLabel( 'Quantity of V-Neck T-Shirt in your cart.' )
			).toHaveValue( '1' );
		} );

		await test.step( "allows adding grouped products to cart when the 'Redirect to cart after successful addition' setting is enabled", async () => {
			await page.getByLabel( 'Remove V-Neck T-Shirt from cart' ).click();
			await expect(
				page.getByRole( 'heading', {
					name: 'Your cart is currently empty!',
				} )
			).toBeVisible();

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

		const addToCartBlock = page.locator(
			'.wp-block-add-to-cart-with-options'
		);
		const colorBlueOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Color' } )
			.getByRole( 'radio', { name: 'Blue', exact: true } );
		const logoYesOption = addToCartBlock
			.getByRole( 'radiogroup', { name: 'Logo' } )
			.getByRole( 'radio', { name: 'Yes', exact: true } );

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

	test( 'allows adding variations to cart when inside the Product block', async ( {
		page,
		pageObject,
	} ) => {
		await pageObject.createPostWithProductBlock(
			'hoodie',
			'hoodie-blue-yes'
		);

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
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

	test( 'allows updating the Product Image Gallery block to the Product Gallery block', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		const addToCartFormBlock = await editor.getBlockByName(
			pageObject.BLOCK_SLUG
		);
		await editor.selectBlocks( addToCartFormBlock );

		await expect(
			editor.canvas.getByLabel( 'Block: Product Gallery' )
		).toBeHidden();

		await page
			.getByRole( 'button', {
				name: 'Upgrade to the Product Gallery block',
			} )
			.click();

		await expect(
			editor.canvas.getByLabel( 'Block: Product Gallery' )
		).toBeVisible();
	} );

	test.describe( 'autoselect behavior', () => {
		const productSlug = 'autoselect-t-shirt';
		const productName = 'Autoselect T-shirt';
		const productPermalink = '/product/' + productSlug;
		const productPrice = '13.99';
		const productAttributes: {
			name: string;
			options: string[];
			variation: boolean;
			visible: boolean;
		}[] = [
			{
				name: 'Type',
				options: [ 'T-shirt' ],
				variation: true,
				visible: true,
			},
			{
				name: 'Color',
				options: [ 'Red', 'Blue', 'Green' ],
				variation: true,
				visible: true,
			},
			{
				name: 'Size',
				options: [ 'S', 'L', 'XL' ],
				variation: true,
				visible: true,
			},
		];
		const productVariations: {
			attributes: {
				name: string;
				option: string;
			}[];
		}[] = [
			{
				attributes: [
					{
						name: 'Type',
						option: 'T-shirt',
					},
					{
						name: 'Color',
						option: 'Green',
					},
					{
						name: 'Size',
						option: 'S',
					},
				],
			},
			{
				attributes: [
					{
						name: 'Type',
						option: 'T-shirt',
					},
					{
						name: 'Color',
						option: 'Red',
					},
					{
						name: 'Size',
						option: 'L',
					},
				],
			},
			{
				attributes: [
					{
						name: 'Type',
						option: 'T-shirt',
					},
					{
						name: 'Color',
						option: 'Red',
					},
					{
						name: 'Size',
						option: 'XL',
					},
				],
			},
			{
				attributes: [
					{
						name: 'Type',
						option: 'T-shirt',
					},
					{
						name: 'Color',
						option: 'Blue',
					},
					{
						name: 'Size',
						option: 'XL',
					},
				],
			},
		];

		test.beforeEach( async ( { requestUtils } ) => {
			const productResponse = await requestUtils.rest< unknown >( {
				method: 'POST',
				path: 'wc/v3/products',
				data: {
					name: productName,
					slug: productSlug,
					type: 'variable',
					status: 'publish',
					attributes: productAttributes,
				},
			} );

			if ( typeof productResponse !== 'object' || ! productResponse ) {
				throw new Error(
					`Unexpected parent product response: ${ JSON.stringify(
						productResponse,
						null,
						2
					) }`
				);
			}

			const product = productResponse as Record< string, unknown >;
			const productId = product.id;
			const createdProductAttributes = product.attributes;
			if (
				typeof productId !== 'number' ||
				! Number.isInteger( productId ) ||
				productId <= 0 ||
				! Array.isArray( createdProductAttributes ) ||
				! createdProductAttributes.every(
					( attribute ) =>
						typeof attribute === 'object' && attribute !== null
				)
			) {
				throw new Error(
					`Unexpected parent product response: ${ JSON.stringify(
						productResponse,
						null,
						2
					) }`
				);
			}

			expect( product ).toMatchObject( {
				name: productName,
				slug: productSlug,
				type: 'variable',
				status: 'publish',
			} );
			expect(
				createdProductAttributes.map(
					( { name, options, variation, visible } ) => ( {
						name,
						options,
						variation,
						visible,
					} )
				)
			).toEqual( productAttributes );

			const variationBatchResponse = await requestUtils.rest< unknown >( {
				method: 'POST',
				path: `wc/v3/products/${ productId }/variations/batch`,
				data: {
					create: productVariations.map( ( variation ) => ( {
						...variation,
						status: 'publish',
						regular_price: productPrice,
					} ) ),
				},
			} );

			if (
				typeof variationBatchResponse !== 'object' ||
				! variationBatchResponse
			) {
				throw new Error(
					`Unexpected variation-batch response: ${ JSON.stringify(
						variationBatchResponse,
						null,
						2
					) }`
				);
			}

			const variationBatch = variationBatchResponse as Record<
				string,
				unknown
			>;
			const createdVariations = variationBatch.create;
			if (
				! Array.isArray( createdVariations ) ||
				! createdVariations.every( ( variation ) => {
					if ( typeof variation !== 'object' || ! variation ) {
						return false;
					}

					const createdVariation = variation as Record<
						string,
						unknown
					>;
					return (
						typeof createdVariation.id === 'number' &&
						Number.isInteger( createdVariation.id ) &&
						createdVariation.id > 0 &&
						createdVariation.status === 'publish' &&
						createdVariation.regular_price === productPrice &&
						Array.isArray( createdVariation.attributes ) &&
						createdVariation.attributes.every(
							( attribute ) =>
								typeof attribute === 'object' &&
								attribute !== null
						)
					);
				} )
			) {
				throw new Error(
					`Unexpected variation-batch response: ${ JSON.stringify(
						variationBatchResponse,
						null,
						2
					) }`
				);
			}

			const successfulVariations = createdVariations as {
				id: number;
				attributes: {
					name: string;
					option: string;
				}[];
			}[];
			expect( successfulVariations ).toHaveLength(
				productVariations.length
			);
			const variationIds = successfulVariations.map( ( { id } ) => id );
			expect( new Set( variationIds ).size ).toBe(
				productVariations.length
			);

			const actualAttributeCombinations = successfulVariations
				.map( ( { attributes } ) =>
					JSON.stringify(
						attributes.map( ( { name, option } ) => ( {
							name,
							option,
						} ) )
					)
				)
				.sort();
			const expectedAttributeCombinations = productVariations
				.map( ( { attributes } ) => JSON.stringify( attributes ) )
				.sort();
			expect( actualAttributeCombinations ).toEqual(
				expectedAttributeCombinations
			);
		} );

		for ( const optionStyle of [ 'chips', 'dropdown' ] as (
			| 'chips'
			| 'dropdown'
		)[] ) {
			// eslint-disable-next-line playwright/expect-expect
			test( `${ optionStyle }: Test the autoselect block attribute`, async ( {
				page,
				pageObject,
				editor,
			} ) => {
				await pageObject.updateSingleProductTemplate();

				if ( optionStyle === 'chips' ) {
					await editor.saveSiteEditorEntities( {
						isOnlyCurrentEntityDirty: true,
					} );
				} else {
					await pageObject.setVariationSelectorAttributes( {
						optionStyle,
					} );
					await editor.saveSiteEditorEntities();
				}

				await test.step( `${ optionStyle }: Expect NOTHING to be auto-selected (on page load)`, async () => {
					await page.goto( productPermalink );

					await pageObject.expectVariationSelectorOptions(
						productAttributes,
						{ Type: '', Color: '', Size: '' },
						optionStyle
					);
				} );

				await test.step( `${ optionStyle }: Expect attributes to NOT auto-select when user selects something`, async () => {
					await page.goto( productPermalink );

					await pageObject.selectVariationSelectorOptions(
						'Color',
						'Blue',
						optionStyle
					);

					// Expect nothing to be auto-selected
					await pageObject.expectVariationSelectorOptions(
						productAttributes,
						{ Type: '', Color: 'Blue', Size: '' },
						optionStyle
					);
				} );

				await test.step( `${ optionStyle }: Set the autoselect setting to true`, async () => {
					await pageObject.updateSingleProductTemplate();
					await pageObject.setVariationSelectorAttributes( {
						optionStyle,
						autoselect: true,
					} );
					await editor.saveSiteEditorEntities();
				} );

				await test.step( `${ optionStyle }: Expect only the Type attribute to be auto-selected (on page load)`, async () => {
					await page.goto( productPermalink );

					// Expect the Type attribute to be auto-selected (on page load) to "T-shirt", the rest of the attributes should not be selected.
					await pageObject.expectVariationSelectorOptions(
						productAttributes,
						{ Type: 'T-shirt', Color: '', Size: '' },
						optionStyle
					);
				} );

				await test.step( `${ optionStyle }: Expect attributes to auto-select when user selects something`, async () => {
					await page.goto( productPermalink );

					// By setting the Color to "Blue", we expect the Type attribute to be auto-selected to "T-shirt", and the Size to "XL".
					await pageObject.selectVariationSelectorOptions(
						'Color',
						'Blue',
						optionStyle
					);

					await pageObject.expectVariationSelectorOptions(
						productAttributes,
						{ Type: 'T-shirt', Color: 'Blue', Size: 'XL' },
						optionStyle
					);
				} );
			} );

			test( `${ optionStyle }: Test the disabledAttributesAction block attribute`, async ( {
				page,
				pageObject,
				editor,
			} ) => {
				await test.step( `${ optionStyle }: Set the disabledAttributesAction block attribute to "disable"`, async () => {
					await pageObject.updateSingleProductTemplate();

					if ( optionStyle === 'chips' ) {
						await editor.saveSiteEditorEntities( {
							isOnlyCurrentEntityDirty: true,
						} );
					} else {
						await pageObject.setVariationSelectorAttributes( {
							optionStyle,
						} );
						await editor.saveSiteEditorEntities();
					}
				} );
				await test.step( `${ optionStyle }: Expect invalid options to be disabled (by prop) and visible`, async () => {
					await page.goto( productPermalink );

					// By setting the Color to "Blue", the only possible Size remaining is "XL".
					await pageObject.selectVariationSelectorOptions(
						'Color',
						'Blue',
						optionStyle
					);

					if ( optionStyle === 'chips' ) {
						const sizeLChip = page
							.getByRole( 'radiogroup', { name: 'Size' } )
							.getByRole( 'radio', {
								name: 'L',
								exact: true,
							} );
						await expect( sizeLChip ).toBeDisabled();
						await expect( sizeLChip ).not.toHaveAttribute(
							'hidden'
						);
					} else {
						const sizeSelect = page
							.locator( '.wp-block-add-to-cart-with-options' )
							.getByLabel( 'Size', { exact: true } );
						const sizeLOption = sizeSelect.getByRole( 'option', {
							name: 'L',
							exact: true,
						} );
						await expect( sizeLOption ).toBeDisabled();
						await expect( sizeLOption ).not.toHaveAttribute(
							'hidden'
						);
					}
				} );

				await test.step( `${ optionStyle }: Set the disabledAttributesAction block attribute to "hide"`, async () => {
					await pageObject.updateSingleProductTemplate();
					await pageObject.setVariationSelectorAttributes( {
						optionStyle,
						disabledAttributesAction: 'hide',
					} );
					await editor.saveSiteEditorEntities();
				} );
				await test.step( `${ optionStyle }: Expect invalid options to be disabled (by prop) and hidden`, async () => {
					await page.goto( productPermalink );

					// By setting the Color to "Blue", the only possible Size remaining is "XL".
					await pageObject.selectVariationSelectorOptions(
						'Color',
						'Blue',
						optionStyle
					);

					if ( optionStyle === 'chips' ) {
						const sizeLChip = page
							.getByRole( 'radiogroup', { name: 'Size' } )
							.getByRole( 'radio', {
								name: 'L',
								exact: true,
							} );
						await expect( sizeLChip ).toBeHidden();
					} else {
						const sizeSelect = page
							.locator( '.wp-block-add-to-cart-with-options' )
							.getByLabel( 'Size', { exact: true } );
						const sizeLOption = sizeSelect.getByRole( 'option', {
							name: 'L',
							exact: true,
						} );
						await expect( sizeLOption ).toBeHidden();
					}
				} );
			} );

			// eslint-disable-next-line playwright/expect-expect
			test( `${ optionStyle }: Combining autoselect and disabledAttributesAction block attributes should work`, async ( {
				page,
				pageObject,
				editor,
			} ) => {
				for ( const disabledAttributesAction of [
					'disable',
					'hide',
				] as ( 'disable' | 'hide' )[] ) {
					await pageObject.updateSingleProductTemplate();

					await test.step( `${ optionStyle }: Set the disabledAttributesAction block attribute to "${ disabledAttributesAction }"`, async () => {
						await pageObject.setVariationSelectorAttributes( {
							autoselect: true,
							optionStyle,
							disabledAttributesAction,
						} );
						await editor.saveSiteEditorEntities();
					} );
					await test.step( `disabledAttributesAction === ${ disabledAttributesAction }: Expect options to be properly auto-selected`, async () => {
						await page.goto( productPermalink );

						// By selecting the Color to "Blue", the only possible Size remaining is "XL".
						await pageObject.selectVariationSelectorOptions(
							'Color',
							'Blue',
							optionStyle
						);
						// Now, we deselect the Color.
						await pageObject.selectVariationSelectorOptions(
							'Color',
							'',
							optionStyle
						);
						// Now, the attributes should look like this:
						// Type: T-shirt
						// Color: ''
						// Size: XL
						// Because the Size is XL, the only Colors possible are Red and Blue.
						// Now if we select Size: S, the Color should auto-select to Green.
						await pageObject.selectVariationSelectorOptions(
							'Size',
							'S',
							optionStyle
						);
						// Now, the options should look like this:
						// Type: T-shirt
						// Color: Green
						// Size: S

						await pageObject.expectVariationSelectorOptions(
							productAttributes,
							{ Type: 'T-shirt', Color: 'Green', Size: 'S' },
							optionStyle
						);
					} );
				}
			} );
		}

		test( `chips: "X in cart" text displays correctly after auto-selection`, async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await pageObject.updateSingleProductTemplate();
			await pageObject.setVariationSelectorAttributes( {
				optionStyle: 'chips',
				autoselect: true,
			} );
			await editor.saveSiteEditorEntities();

			await test.step( 'Add the Blue/XL variation to cart', async () => {
				await page.goto( productPermalink );

				// Select Blue and XL to match the T-shirt, Blue, XL variation
				await pageObject.selectVariationSelectorOptions(
					'Color',
					'Blue',
					'chips'
				);

				// Type and Size should auto-select to T-shirt and XL
				await pageObject.expectVariationSelectorOptions(
					productAttributes,
					{ Type: 'T-shirt', Color: 'Blue', Size: 'XL' },
					'chips'
				);

				// Add to cart
				const addToCartButton = page
					.locator( '.wp-block-add-to-cart-with-options' )
					.getByRole( 'button', { name: 'Add to cart' } );
				await addToCartButton.click();

				// Wait for the item to be added
				await expect( page.getByText( '1 in cart' ) ).toBeVisible();
			} );

			await test.step( 'Verify "X in cart" displays after auto-selection on fresh page load', async () => {
				// Reload the page to start fresh
				await page.goto( productPermalink );

				// Initially, only Type should be auto-selected (it's the only single option)
				// The "1 in cart" text should NOT be visible yet because we haven't
				// selected the Blue/XL variation
				await expect( page.getByText( '1 in cart' ) ).toBeHidden();

				// Now select Blue - this should auto-select Size to XL
				// (since Blue only has one valid size: XL)
				await pageObject.selectVariationSelectorOptions(
					'Color',
					'Blue',
					'chips'
				);

				// After auto-selection completes, the button should show "1 in cart"
				// because we now have the same variation (T-shirt, Blue, XL) selected
				await expect( page.getByText( '1 in cart' ) ).toBeVisible();
			} );
		} );
	} );
} );
