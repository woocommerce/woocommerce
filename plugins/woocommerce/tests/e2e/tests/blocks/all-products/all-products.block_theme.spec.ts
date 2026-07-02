/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { BLOCK_THEME_SLUG, expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const BLOCK_NAME = 'woocommerce/all-products';
const BLOCK_MARKUP = '<!-- wp:woocommerce/all-products /-->';
const PLACEHOLDER_TITLE = 'All Products Block is a soft-deprecated block';
const PLACEHOLDER_DESCRIPTION =
	'For better performance and more flexible layouts, use the Product Collection block. You can continue editing this block if needed.';
const SCRIPT_SELECTOR =
	'script#wc-all-products-block-js, script[src*="/all-products.js"]';
const EDIT_SCRIPT_SELECTOR = 'script[src*="all-products-edit"]';

async function expectAllProductsEditorScriptLoaded(
	page: Page,
	isLoaded: boolean
) {
	await expect( page.locator( SCRIPT_SELECTOR ) ).toHaveCount(
		isLoaded ? 1 : 0
	);
}

async function expectAllProductsEditorChunkLoaded(
	page: Page,
	isLoaded: boolean
) {
	await expect( page.locator( EDIT_SCRIPT_SELECTOR ) ).toHaveCount(
		isLoaded ? 1 : 0
	);
}

test.describe( `${ BLOCK_NAME } Block`, () => {
	test( 'editor assets load in the Site Editor', async ( {
		admin,
		page,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//header`,
			postType: 'wp_template_part',
			canvas: 'edit',
		} );

		await expect( page.locator( SCRIPT_SELECTOR ) ).toHaveCount( 1 );
		await expect( page.locator( EDIT_SCRIPT_SELECTOR ) ).toHaveCount( 0 );
	} );

	test( 'full editor assets load after choosing to edit a raw All Products block', async ( {
		admin,
		editor,
		requestUtils,
		page,
	} ) => {
		const post = await requestUtils.createPost( {
			title: 'All Products raw block asset loading',
			content:
				'<!-- wp:paragraph --><p>Initial content</p><!-- /wp:paragraph -->',
			status: 'draft',
			date_gmt: new Date().toISOString(),
		} );

		await admin.editPost( post.id );
		await expectAllProductsEditorScriptLoaded( page, true );
		await expectAllProductsEditorChunkLoaded( page, false );

		await editor.setContent( BLOCK_MARKUP );

		const allProductsBlock = await editor.getBlockByName( BLOCK_NAME );
		await expect( allProductsBlock ).toContainText( PLACEHOLDER_TITLE );
		await expect( allProductsBlock ).toContainText(
			PLACEHOLDER_DESCRIPTION
		);
		await expectAllProductsEditorChunkLoaded( page, false );

		await allProductsBlock
			.getByRole( 'button', { name: 'Edit All Products' } )
			.click();

		await expectAllProductsEditorChunkLoaded( page, true );
		await expect( allProductsBlock ).toBeVisible();
	} );

	test( 'block can be inserted and it is rendered on the frontend', async ( {
		editor,
		admin,
		page,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
		await editor.publishAndVisitPost();

		await page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wc/store/v1/products' ) &&
				response.status() === 200
		);

		await expect(
			page.locator( '.wc-block-grid__product.wc-block-layout' )
		).toHaveCount( 9 );
	} );

	// Check this regression: hhttps://github.com/woocommerce/woocommerce/pull/58741.
	// The block has a dependency on the Mini Cart block/Checkout/Cart blocks.
	// This test checks that the block can be inserted and it is rendered on the frontend without the mini cart block.
	test( 'block can be inserted and it is rendered on the frontend without the mini cart block', async ( {
		editor,
		admin,
		page,
	} ) => {
		const templatePath = 'header';
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//${ templatePath }`,
			postType: 'wp_template_part',
			canvas: 'edit',
		} );

		await editor.setContent( '' );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
		await editor.publishAndVisitPost();
		await page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wc/store/v1/products' ) &&
				response.status() === 200
		);

		await expect(
			page.locator( '.wc-block-grid__product.wc-block-layout' )
		).toHaveCount( 9 );
	} );
} );
