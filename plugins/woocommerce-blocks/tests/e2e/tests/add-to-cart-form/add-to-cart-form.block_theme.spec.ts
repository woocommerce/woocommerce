/**
 * External dependencies
 */
import { expect, test, Editor, BlockData } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData: BlockData = {
	name: 'Add to Cart with Options',
	slug: 'woocommerce/add-to-cart-form',
	mainClass: '.wc-block-add-to-cart-form',
	selectors: {
		frontend: {},
		editor: {},
	},
};

const configureSingleProductBlock = async ( editor: Editor ) => {
	const singleProductBlock = await editor.getBlockByName(
		'woocommerce/single-product'
	);

	await singleProductBlock.locator( 'input[type="radio"]' ).nth( 0 ).click();

	await singleProductBlock.getByText( 'Done' ).click();
};

test.describe( `${ blockData.name } Block`, () => {
	test( 'can be added in the Post Editor only as inner block of the Single Product Block', async ( {
		admin,
		editor,
	} ) => {
		// Add to Cart with Options in the Post Editor is only available as inner block of the Single Product Block.
		await admin.createNewPost();
		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		await configureSingleProductBlock( editor );

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

		await expect( editor.canvas.getByText( 'placeholder' ) ).toBeVisible();

		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		await configureSingleProductBlock( editor );

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

		await expect( editor.canvas.getByText( 'placeholder' ) ).toBeVisible();

		await editor.insertBlock( { name: blockData.slug } );

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();
	} );
} );
