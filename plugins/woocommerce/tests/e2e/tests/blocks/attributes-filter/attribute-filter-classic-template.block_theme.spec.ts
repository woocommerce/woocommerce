/**
 * External dependencies
 */
import { test, expect, wpCLI, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Filter by Attribute',
	slug: 'woocommerce/attribute-filter',
	urlSearchParamWhenFilterIsApplied: 'filter_size=small&query_type_size=or',
};

const blockifiedTemplateOption =
	'wc_blocks_use_blockified_product_grid_block_as_template';

test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test( 'filters the PHP classic template by attribute', async ( {
		admin,
		editor,
		frontendUtils,
		page,
	} ) => {
		await wpCLI( `option update ${ blockifiedTemplateOption } false` );
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'attribute-filter',
				heading: 'Filter By Attribute',
			},
		} );
		const attributeFilter = await editor.getBlockByName( blockData.slug );

		await attributeFilter.getByText( 'Size' ).click();
		await attributeFilter.getByText( 'Done' ).click();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await page.goto( '/shop' );

		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);
		const productTitles = legacyTemplate.locator(
			'.woocommerce-loop-product__title'
		);

		await expect( productTitles.first() ).toBeVisible();
		expect( await productTitles.allTextContents() ).not.toHaveLength( 0 );
		for ( const name of [ 'Small', 'Medium', 'Large' ] ) {
			await expect(
				page.getByRole( 'checkbox', { name } )
			).toBeVisible();
		}

		await page.getByRole( 'checkbox', { name: 'Small' } ).click();
		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'V-Neck T-Shirt' ] );
	} );
} );
