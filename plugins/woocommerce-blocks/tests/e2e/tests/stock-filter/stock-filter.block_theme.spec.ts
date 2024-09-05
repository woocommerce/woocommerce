/**
 * External dependencies
 */
import {
	test as base,
	expect,
	TemplateCompiler,
	wpCLI,
} from '@woocommerce/e2e-utils';

export const blockData = {
	name: 'Filter by Stock',
	slug: 'woocommerce/stock-filter',
	urlSearchParamWhenFilterIsApplied: 'filter_stock_status=outofstock',
};

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_filters-with-product-collection'
		);
		await use( compiler );
	},
} );

test.describe( `${ blockData.name } Block`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'stock-filter',
				heading: 'Filter By Price',
			},
		} );

		await editor.openDocumentSettingsSidebar();
	} );

	test( "should allow changing the block's title", async ( { editor } ) => {
		const textSelector =
			'.wp-block-woocommerce-filter-wrapper .wp-block-heading';

		const title = 'New Title';

		await editor.canvas.locator( textSelector ).fill( title );

		await expect( editor.canvas.locator( textSelector ) ).toHaveText(
			title
		);
	} );

	test( 'should allow changing the display style', async ( {
		page,
		editor,
	} ) => {
		const stockFilter = await editor.getBlockByName( blockData.slug );
		await editor.selectBlocks( stockFilter );

		await expect(
			stockFilter.getByRole( 'checkbox', {
				name: 'In Stock',
			} )
		).toBeVisible();

		await expect(
			stockFilter.getByRole( 'checkbox', {
				name: 'Out of Stock',
			} )
		).toBeVisible();

		await page.getByLabel( 'DropDown' ).click();

		await expect(
			stockFilter.getByRole( 'checkbox', {
				name: 'In Stock',
			} )
		).toBeHidden();

		await expect(
			stockFilter.getByRole( 'checkbox', {
				name: 'Out of Stock',
			} )
		).toBeHidden();

		await expect( editor.canvas.getByRole( 'combobox' ) ).toBeVisible();
	} );

	test( 'should allow toggling the visibility of the filter button', async ( {
		page,
		editor,
	} ) => {
		const priceFilterControls = await editor.getBlockByName(
			blockData.slug
		);
		await editor.selectBlocks( priceFilterControls );

		await expect(
			priceFilterControls.getByRole( 'button', {
				name: 'Apply',
			} )
		).toBeHidden();

		await page.getByText( "Show 'Apply filters' button" ).click();

		await expect(
			priceFilterControls.getByRole( 'button', {
				name: 'Apply',
			} )
		).toBeVisible();
	} );
} );

test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test.beforeEach( async ( { admin, page, editor } ) => {
		await wpCLI(
			'option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'stock-filter',
				heading: 'Filter By Price',
			},
		} );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await page.goto( '/shop' );
	} );

	test( 'should show all products', async ( { frontendUtils } ) => {
		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const stockFilter = await frontendUtils.getBlockByName(
			'woocommerce/filter-wrapper'
		);

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 16 );

		await expect( stockFilter.getByText( 'In Stock' ) ).toBeVisible();
		await expect( stockFilter.getByText( 'Out of Stock' ) ).toBeVisible();
	} );

	test( 'should show only products that match the filter', async ( {
		frontendUtils,
	} ) => {
		const stockFilter = await frontendUtils.getBlockByName(
			'woocommerce/filter-wrapper'
		);

		await stockFilter.getByText( 'Out of Stock' ).click();

		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 1 );
	} );
} );

test.describe( `${ blockData.name } Block - with Product Collection`, () => {
	test( 'should show all products', async ( { page, templateCompiler } ) => {
		await templateCompiler.compile();

		await page.goto( '/shop' );
		const products = page
			.locator( '.wp-block-woocommerce-product-template' )
			.getByRole( 'listitem' );

		await expect( products ).toHaveCount( 16 );
	} );

	test( 'should show only products that match the filter', async ( {
		page,
		templateCompiler,
	} ) => {
		await templateCompiler.compile();

		await page.goto( '/shop' );
		await page.getByText( 'Out of Stock' ).click();

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);

		const products = page
			.locator( '.wp-block-woocommerce-product-template' )
			.getByRole( 'listitem' );

		await expect( products ).toHaveCount( 1 );
	} );

	test( 'should refresh the page only if the user clicks on button', async ( {
		page,
		admin,
		editor,
		templateCompiler,
	} ) => {
		const template = await templateCompiler.compile();

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: template.type,
			canvas: 'edit',
		} );

		const stockFilterControls = await editor.getBlockByName(
			blockData.slug
		);
		await expect( stockFilterControls ).toBeVisible();
		await editor.selectBlocks( stockFilterControls );
		await editor.openDocumentSettingsSidebar();
		await page.getByText( "Show 'Apply filters' button" ).click();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await page.goto( '/shop' );

		await page.getByText( 'Out of Stock' ).click();
		await page.getByRole( 'button', { name: 'Apply' } ).click();

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);

		const products = page
			.locator( '.wp-block-woocommerce-product-template' )
			.getByRole( 'listitem' );

		await expect( products ).toHaveCount( 1 );
	} );
} );
