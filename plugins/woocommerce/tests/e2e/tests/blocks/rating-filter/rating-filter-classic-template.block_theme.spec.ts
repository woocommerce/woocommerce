/**
 * External dependencies
 */
import { test, expect, wpCLI, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

// The Cap is the only seeded product in this bucket: its reviews are rated 1
// and 2, averaging 1.5.
const blockData = {
	name: 'Filter by Rating',
	urlSearchParamWhenFilterIsApplied: 'rating_filter=2',
};

test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test.beforeEach( async ( { admin, page, editor } ) => {
		await wpCLI(
			'option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);

		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'rating-filter',
				heading: 'Filter By Rating',
			},
		} );

		await page.keyboard.press( 'Escape' );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/shop' );
	} );

	test( 'filters the classic product template by rating', async ( {
		frontendUtils,
		page,
	} ) => {
		await page
			.getByRole( 'checkbox', { name: 'Rated 2 out of 5' } )
			.click();

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
			products.getByRole( 'heading', { name: 'Cap' } )
		).toBeVisible();
	} );
} );
