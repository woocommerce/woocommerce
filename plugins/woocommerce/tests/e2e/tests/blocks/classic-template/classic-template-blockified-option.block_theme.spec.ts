/**
 * External dependencies
 */
import { test, expect, wpCLI, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const BLOCK_NAME = 'woocommerce/legacy-template';

test.describe( `${ BLOCK_NAME } Block `, () => {
	test( `is visible as default block when wc_blocks_use_blockified_product_grid_block_as_template is false`, async ( {
		admin,
		editor,
		page,
	} ) => {
		await wpCLI(
			'option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect( await editor.getBlockByName( BLOCK_NAME ) ).toBeVisible();

		await page.goto( '/shop/' );

		await expect( page.locator( 'div[data-template]' ) ).toBeVisible();
	} );
} );
