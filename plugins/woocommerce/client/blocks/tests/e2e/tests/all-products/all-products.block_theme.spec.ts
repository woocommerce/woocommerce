/**
 * External dependencies
 */
import { BLOCK_THEME_SLUG, expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { getDeprecatedBlockWarning } from '../../utils/deprecated-php-product-grid';

const BLOCK_NAME = 'woocommerce/all-products';
const BLOCK_TITLE = 'All Products';

test.describe( `${ BLOCK_NAME } Block`, () => {
	test( 'shows a deprecation warning in the editor and renders on the frontend', async ( {
		editor,
		admin,
		page,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
		const blockLocator = await editor.getBlockByName( BLOCK_NAME );
		await expect(
			blockLocator.getByText( getDeprecatedBlockWarning( BLOCK_TITLE ) )
		).toBeVisible();
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
	test( 'renders on the frontend without the mini cart block', async ( {
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
