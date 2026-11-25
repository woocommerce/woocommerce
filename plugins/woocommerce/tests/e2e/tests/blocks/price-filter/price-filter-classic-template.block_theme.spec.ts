/**
 * External dependencies
 */
import { test, expect, wpCLI, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Filter by Price',
	urlSearchParamWhenFilterIsApplied: 'max_price=5',
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
				filterType: 'price-filter',
				heading: 'Filter By Price',
			},
		} );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await page.goto( '/shop' );
	} );

	test( 'filters the classic product template by maximum price', async ( {
		page,
		frontendUtils,
	} ) => {
		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);
		const productTitles = legacyTemplate.locator(
			'.woocommerce-loop-product__title'
		);

		await expect( productTitles.first() ).toBeVisible();
		// More than one product, so the test cannot pass against a shop page that
		// was already filtered.
		expect(
			( await productTitles.allTextContents() ).length
		).toBeGreaterThan( 1 );

		const maxPriceInput = page.getByRole( 'textbox', {
			name: 'Filter products by maximum price',
		} );

		await frontendUtils.selectTextInput( maxPriceInput );
		await maxPriceInput.fill( '$5' );
		await maxPriceInput.press( 'Tab' );
		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);

		await expect( productTitles ).toHaveText( [ 'Single' ] );
	} );
} );
