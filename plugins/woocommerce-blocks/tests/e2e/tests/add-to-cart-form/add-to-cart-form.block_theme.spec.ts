/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { expect, test } from '@woocommerce/e2e-playwright-utils';
import { WC_TEMPLATES_SLUG, EditorUtils } from '@woocommerce/e2e-utils';

const blockData: BlockData = {
	name: 'Add to Cart with Options',
	slug: 'woocommerce/add-to-cart-form',
	mainClass: '.wc-block-add-to-cart-form',
	selectors: {
		frontend: {},
		editor: {},
	},
};

const configureSingleProductBlock = async ( editorUtils: EditorUtils ) => {
	const singleProductBlock = await editorUtils.getBlockByName(
		'woocommerce/single-product'
	);

	await singleProductBlock.locator( 'input[type="radio"]' ).nth( 0 ).click();

	await singleProductBlock.getByText( 'Done' ).click();
};

test.describe( `${ blockData.name } Block`, () => {
	test( 'can be added in the Post Editor only as inner block of the Single Product Block', async ( {
		admin,
		editor,
		editorUtils,
	} ) => {
		// Add to Cart with Options in the Post Editor is only available as inner block of the Single Product Block.
		await admin.createNewPost( { legacyCanvas: true } );
		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		await configureSingleProductBlock( editorUtils );

		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).toBeVisible();

		// When the block is registered as ancestor, the function doesn't throw an error, but the block is not added.
		// So we check that only one instance of the block is present.
		await editor.insertBlock( { name: blockData.slug } );
		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test( 'can be added in the Site Editor only as inner block of the Single Product Block - Product Catalog Template', async ( {
		admin,
		editor,
		editorUtils,
	} ) => {
		// Add to Cart with Options in the Site Editor is only available as inner block of the Single Product Block except for the Single Product Template
		await admin.visitSiteEditor( {
			postId: `${ WC_TEMPLATES_SLUG }//archive-product`,
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();

		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		await configureSingleProductBlock( editorUtils );

		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).toBeVisible();

		// When the block is registered as ancestor, the function doesn't throw an error, but the block is not added.
		// So we check that only one instance of the block is present.
		await editor.insertBlock( { name: blockData.slug } );
		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test( 'can be added in the Post Editor - Single Product Template', async ( {
		admin,
		editor,
		editorUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ WC_TEMPLATES_SLUG }//single-product`,
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();

		await editor.setContent( '' );

		await editor.insertBlock( { name: blockData.slug } );

		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).toBeVisible();
	} );
} );
