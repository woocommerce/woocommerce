/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';

const test = base.extend< { pageObject: AddToCartWithOptionsPage } >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Add to Cart + Options Block', () => {
	test( 'allows modifying the template parts', async ( {
		page,
		pageObject,
		editor,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//single-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( { name: pageObject.BLOCK_SLUG } );

		await pageObject.insertParagraphInTemplatePart(
			'This is a test paragraph added to the Add to Cart + Options template part.'
		);

		await editor.saveSiteEditorEntities();

		await page.goto( '/product/cap' );

		await expect(
			page.getByText(
				'This is a test paragraph added to the Add to Cart + Options template part.'
			)
		).toBeVisible();
	} );

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
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/hoodie' );

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

		await test.step( 'updates stock indicator and product price when attributes are selected', async () => {
			await expect( productPrice ).toHaveText( /\$42.00 – \$45.00.*/ );
			await expect( page.getByText( 'Out of stock' ) ).toBeHidden();

			await colorBlueOption.click();
			await logoNoOption.click();

			await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
			await expect( productPrice ).toHaveText( '$45.00' );
		} );

		await test.step( 'successfully adds to cart when attributes are selected', async () => {
			await colorGreenOption.click();

			// Note: The button is always enabled for accessibility reasons.
			// Instead, we check directly for the "disabled" class, which grays
			// out the button.
			await expect( addToCartButton ).not.toHaveClass( /disabled/ );

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

		await page.goto( '/hoodie' );

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

		// We need to make sure the block updated before saving.
		// @see https://github.com/woocommerce/woocommerce/issues/57718
		// Verify that `.editor-post-publish-button__button` has an attribute
		// `aria-haspopup="dialog"`. When https://github.com/woocommerce/woocommerce/issues/48936
		// is fixed, we can simply check that the Save button becomes enabled.
		await expect(
			page.getByRole( 'button', { name: 'Save', exact: true } )
		).toHaveAttribute( 'aria-haspopup', 'dialog' );

		await editor.saveSiteEditorEntities();

		await page.goto( '/hoodie' );

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
} );
