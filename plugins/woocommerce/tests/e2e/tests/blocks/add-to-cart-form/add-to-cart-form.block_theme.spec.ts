/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { test as base, expect, Editor, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Add to Cart with Options',
	slug: 'woocommerce/add-to-cart-form',
	mainClass: '.wc-block-add-to-cart-form',
	selectors: {
		editor: {
			stepperMinusButton:
				'.wc-block-components-quantity-selector__button--minus',
			stepperPlusButton:
				'.wc-block-components-quantity-selector__button--plus',
		},
	},
};

class BlockUtils {
	editor: Editor;
	page: Page;

	constructor( { editor, page }: { editor: Editor; page: Page } ) {
		this.editor = editor;
		this.page = page;
	}

	/**
	 * Configures the Single Product Block in the editor.
	 * If a product name is provided, it searches for the product by name and selects it.
	 * If no product name is provided, it selects the first product in the list by default.
	 */
	async configureSingleProductBlock( name?: string ) {
		const singleProductBlock = await this.editor.getBlockByName(
			'woocommerce/single-product'
		);

		if ( name ) {
			await singleProductBlock
				.locator( 'input[type="search"]' )
				.fill( name );
			await singleProductBlock.getByText( 'Search' ).click();
			await singleProductBlock.getByText( name ).click();
		} else {
			await singleProductBlock
				.locator( 'input[type="radio"]' )
				.nth( 0 )
				.click();
		}

		await singleProductBlock.getByText( 'Done' ).click();

		// Switch to the non-blockified Add to Cart with Options block
		const addToCartWithOptionsBlock = await this.editor.getBlockByName(
			'woocommerce/add-to-cart-with-options'
		);
		await this.editor.selectBlocks( addToCartWithOptionsBlock );

		await this.page
			.getByRole( 'button', {
				name: 'Switch back',
			} )
			.click();
	}

	async enableStepperMode() {
		await ( await this.editor.getBlockByName( blockData.slug ) ).click();
		await this.page.getByLabel( 'Stepper' ).click();
	}

	async createSoldIndividuallyProduct() {
		await wpCLI(
			'wc product create --name="Sold Individually" --regular_price=10 --sold_individually=true --user=admin'
		);
	}

	async createManagedStockProduct() {
		await wpCLI(
			'wc product create --name="Managed Stock" --regular_price=10 --manage_stock=true --stock_quantity=1 --user=admin'
		);
	}
}

const test = base.extend< { blockUtils: BlockUtils } >( {
	blockUtils: async ( { editor, page }, use ) => {
		await use( new BlockUtils( { editor, page } ) );
	},
} );

test.describe( `${ blockData.name } Block`, () => {
	test( 'can be added in the Post Editor only as inner block of the Single Product Block', async ( {
		admin,
		editor,
		blockUtils,
	} ) => {
		// Add to Cart with Options in the Post Editor is only available as inner block of the Single Product Block.
		await admin.createNewPost();
		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		await blockUtils.configureSingleProductBlock();

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();

		// When the block is registered as ancestor, the function doesn't throw an error, but the block is not added.
		// So we check that only one instance of the block is present.
		await editor.insertBlock( { name: blockData.slug } );
		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test( 'can be added in the Site Editor only as inner block of the Single Product Block - Product Catalog Template', async ( {
		admin,
		editor,
		requestUtils,
		blockUtils,
	} ) => {
		// Add to Cart with Options in the Site Editor is only available as
		// inner block of the Single Product Block except for the Single Product
		// Template
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'product-catalog',
			title: 'Custom Product Catalog',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect(
			editor.getCustomHtmlBlockContentLocator( 'placeholder' )
		).toBeVisible();

		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		await blockUtils.configureSingleProductBlock();

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();

		// When the block is registered as ancestor, the function doesn't throw an error, but the block is not added.
		// So we check that only one instance of the block is present.
		await editor.insertBlock( { name: blockData.slug } );
		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test( 'can be added in the Post Editor - Single Product Template', async ( {
		admin,
		editor,
		requestUtils,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'single-product',
			title: 'Custom Single Product',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect(
			editor.getCustomHtmlBlockContentLocator( 'placeholder' )
		).toBeVisible();

		await editor.insertBlock( { name: blockData.slug } );

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test.describe( 'Stepper Layout', () => {
		test( 'has the stepper option visible', async ( {
			admin,
			editor,
			blockUtils,
		} ) => {
			await admin.createNewPost();
			await editor.insertBlock( { name: 'woocommerce/single-product' } );

			await blockUtils.configureSingleProductBlock();

			await blockUtils.enableStepperMode();

			const minusButton = editor.canvas.locator(
				blockData.selectors.editor.stepperMinusButton
			);
			const plusButton = editor.canvas.locator(
				blockData.selectors.editor.stepperPlusButton
			);

			await expect( minusButton ).toBeVisible();
			await expect( plusButton ).toBeVisible();
		} );

		test( 'has the stepper mode working on the frontend', async ( {
			admin,
			editor,
			blockUtils,
			page,
		} ) => {
			await admin.createNewPost();
			await editor.insertBlock( { name: 'woocommerce/single-product' } );

			const productName = 'Hoodie with Logo';

			await blockUtils.configureSingleProductBlock( productName );

			await blockUtils.enableStepperMode();

			await editor.publishAndVisitPost();

			const minusButton = page.getByLabel( `Reduce quantity` );
			const plusButton = page.getByLabel( `Increase quantity` );

			await expect( minusButton ).toBeVisible();
			await expect( plusButton ).toBeVisible();

			const input = page.getByLabel( 'Product quantity' );

			await expect( input ).toHaveValue( '1' );
			await plusButton.click();
			await expect( input ).toHaveValue( '2' );
			await minusButton.click();
			await expect( input ).toHaveValue( '1' );
			// Ensure the quantity doesn't go below 1.
			await minusButton.click();
			await expect( input ).toHaveValue( '1' );
		} );

		test( "doesn't render stepper when the product is sold individually", async ( {
			admin,
			editor,
			blockUtils,
			page,
		} ) => {
			await blockUtils.createSoldIndividuallyProduct();
			await admin.createNewPost();
			await editor.insertBlock( { name: 'woocommerce/single-product' } );

			const productName = 'Sold Individually';

			await blockUtils.configureSingleProductBlock( productName );
			await blockUtils.enableStepperMode();

			await editor.publishAndVisitPost();

			const minusButton = page.getByLabel( `Reduce quantity` );
			const plusButton = page.getByLabel( `Increase quantity` );

			await expect( minusButton ).toBeHidden();
			await expect( plusButton ).toBeHidden();
		} );

		test( "doesn't render stepper when the product stock is managed and the stock quantity is 1", async ( {
			admin,
			editor,
			blockUtils,
			page,
		} ) => {
			await blockUtils.createManagedStockProduct();
			await admin.createNewPost();
			await editor.insertBlock( { name: 'woocommerce/single-product' } );

			const productName = 'Managed Stock';

			await blockUtils.configureSingleProductBlock( productName );
			await blockUtils.enableStepperMode();

			await editor.publishAndVisitPost();

			const minusButton = page.getByLabel( `Reduce quantity` );
			const plusButton = page.getByLabel( `Increase quantity` );

			await expect( minusButton ).toBeHidden();
			await expect( plusButton ).toBeHidden();
		} );
	} );

	test( 'can be migrated to the blockified Add to Cart + Options block', async ( {
		page,
		editor,
		blockUtils,
		admin,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		const productName = 'Hoodie with Logo';
		await blockUtils.configureSingleProductBlock( productName );

		const addToCartFormBlock = await editor.getBlockByName(
			blockData.slug
		);
		await editor.selectBlocks( addToCartFormBlock );

		await page
			.getByRole( 'button', {
				name: 'Use the Add to Cart + Options block',
			} )
			.click();

		await expect(
			editor.canvas.getByLabel( 'Block: Product Quantity (Beta)' )
		).toBeVisible();

		const addToCartWithOptionsBlock = await editor.getBlockByName(
			'woocommerce/add-to-cart-with-options'
		);
		await editor.selectBlocks( addToCartWithOptionsBlock );

		await page.getByRole( 'button', { name: 'Switch back' } ).click();

		await expect(
			editor.canvas.getByLabel( 'Block: Product Quantity (Beta)' )
		).toBeHidden();
	} );
} );
