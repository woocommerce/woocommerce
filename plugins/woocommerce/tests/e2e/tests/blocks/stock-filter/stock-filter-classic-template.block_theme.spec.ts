/**
 * External dependencies
 */
import { test, expect, wpCLI, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Filter by Stock',
	urlSearchParamWhenFilterIsApplied: 'filter_stock_status=outofstock',
};

test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test.beforeEach( async ( { admin, page, editor } ) => {
		await wpCLI(
			'option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);

		await page.reload();

		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'stock-filter',
				heading: 'Filter By Stock',
			},
		} );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await page.goto( '/shop' );
	} );

	test( 'filters the classic product template by stock status', async ( {
		frontendUtils,
		page,
	} ) => {
		const stockFilter = await frontendUtils.getBlockByName(
			'woocommerce/filter-wrapper'
		);
		await stockFilter.getByText( 'Out of Stock' ).click();

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);

		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);
		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 1 );
		await expect(
			products.getByRole( 'heading', { name: 'T-Shirt with Logo' } )
		).toBeVisible();
	} );
} );
