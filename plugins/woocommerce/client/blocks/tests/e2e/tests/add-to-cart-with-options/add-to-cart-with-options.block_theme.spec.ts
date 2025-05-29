/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';

const test = base.extend< { pageObject: AddToCartWithOptionsPage } >( {
	pageObject: async ( { page, admin, editor, requestUtils }, use ) => {
		const pageObject = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
			requestUtils,
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
		await pageObject.setFeatureFlags();

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
		admin,
		requestUtils,
	} ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-custom-product-type'
		);

		await pageObject.setFeatureFlags();

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//single-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( { name: pageObject.BLOCK_SLUG } );

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
		await pageObject.setFeatureFlags();

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

		await addToCartButton.click();

		await expect( addToCartButton ).toHaveText( '6 in cart' );
	} );

	test( "'X in cart' text reflects the correct amount in variations", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.setFeatureFlags();

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/hoodie' );

		const logoNoOption = page.getByRole( 'radio', {
			name: 'No',
			exact: true,
		} );
		const colorBlueOption = page.getByRole( 'radio', {
			name: 'Blue',
			exact: true,
		} );
		const colorGreenOption = page.getByRole( 'radio', {
			name: 'Green',
			exact: true,
		} );
		const addToCartButton = page.getByText( 'Add to cart' ).first();

		await logoNoOption.click();
		await colorGreenOption.click();
		await addToCartButton.click();

		await expect( page.getByText( '1 in cart' ) ).toBeVisible();

		await colorBlueOption.click();

		await expect( page.getByText( '1 in cart' ) ).toBeHidden();

		await colorGreenOption.click();

		await expect( page.getByText( '1 in cart' ) ).toBeVisible();
	} );

	test( "doesn't allow selecting invalid variations in pills mode", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.setFeatureFlags();

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/hoodie' );

		const logoYesOption = page.getByRole( 'radio', {
			name: 'Yes',
			exact: true,
		} );
		const colorGreenOption = page.getByRole( 'radio', {
			name: 'Green',
			exact: true,
		} );

		await expect( colorGreenOption ).toBeEnabled();

		await logoYesOption.click();

		await expect( colorGreenOption ).toBeDisabled();
	} );

	test( "doesn't allow selecting invalid variations in dropdown mode", async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.setFeatureFlags();

		await pageObject.updateSingleProductTemplate();

		await pageObject.switchProductType( 'Variable Product' );

		const attributeOptionsBlock = await editor.getBlockByName(
			'woocommerce/add-to-cart-with-options-variation-selector-attribute-options'
		);
		await editor.selectBlocks( attributeOptionsBlock.first() );

		await page.getByRole( 'radio', { name: 'Dropdown' } ).click();

		await editor.saveSiteEditorEntities();

		await page.goto( '/hoodie' );

		const colorGreenOption = page.getByRole( 'option', {
			name: 'Green',
			exact: true,
		} );

		await expect( colorGreenOption ).toBeEnabled();

		await page.getByLabel( 'Logo', { exact: true } ).selectOption( 'Yes' );

		await expect( colorGreenOption ).toBeDisabled();
	} );
} );
