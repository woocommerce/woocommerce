/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Editor, Admin } from '@woocommerce/e2e-utils';

class AddToCartWithOptionsPage {
	private page: Page;
	private admin: Admin;
	private editor: Editor;
	BLOCK_SLUG = 'woocommerce/add-to-cart-with-options';
	BLOCK_NAME = 'Add to Cart + Options (Beta)';

	constructor( {
		page,
		admin,
		editor,
	}: {
		page: Page;
		admin: Admin;
		editor: Editor;
	} ) {
		this.page = page;
		this.admin = admin;
		this.editor = editor;
	}

	async switchProductType( productType: string ) {
		const addToCartWithOptionsBlock = await this.editor.getBlockByName(
			this.BLOCK_SLUG
		);
		await this.editor.selectBlocks( addToCartWithOptionsBlock );

		const productTypeSwitcher = this.page.getByRole( 'button', {
			name: 'Switch product type',
		} );
		await productTypeSwitcher.click();

		// Wait for the menu to open.
		const menu = this.page.getByRole( 'menu', {
			name: 'Switch product type',
		} );
		await menu.waitFor( { state: 'visible' } );

		const customProductTypeButton = this.page.getByRole( 'menuitem', {
			name: productType,
		} );
		await customProductTypeButton.click();

		await addToCartWithOptionsBlock
			.locator( '.components-spinner' )
			.waitFor( {
				state: 'hidden',
			} );
	}

	async insertParagraphInTemplatePart( content: string ) {
		const parentBlock = await this.editor.getBlockByName( this.BLOCK_SLUG );
		const parentClientId =
			( await parentBlock.getAttribute( 'data-block' ) ) ?? '';

		// Add to Cart is a dynamic block, so we need to wait for it to be
		// ready. If we don't do that, it might clear the paragraph we're
		// inserting below (depending on the test execution speed).
		await parentBlock.getByText( /^(Add to cart|Buy product)$/ ).waitFor();

		await this.editor.insertBlock(
			{
				name: 'core/paragraph',
				attributes: {
					content,
				},
			},
			{ clientId: parentClientId }
		);
	}

	async updateSingleProductTemplate() {
		await this.admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//single-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );

		const addToCartFormBlock = await this.editor.getBlockByName(
			'woocommerce/add-to-cart-form'
		);
		await this.editor.selectBlocks( addToCartFormBlock );

		await this.page
			.getByRole( 'button', {
				name: 'Upgrade to the Add to Cart + Options block',
			} )
			.click();
	}
}

export default AddToCartWithOptionsPage;
