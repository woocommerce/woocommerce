/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';
import path from 'path';

const PRODUCT_CATALOG_LINK = '/shop';
const TEMPLATE_PATH = path.join(
	__dirname,
	'../shared/filters-with-product-collection.handlebars'
);

const blockData = {
	name: 'Filter by Rating',
	slug: 'woocommerce/rating-filter',
	urlSearchParamWhenFilterIsApplied: 'rating_filter=1',
};

test.describe( `${ blockData.name } Block`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'rating-filter',
				heading: 'Filter By Rating',
			},
		} );
		await editor.openDocumentSettingsSidebar();
	} );

	test( "should allow changing the block's title", async ( { page } ) => {
		const textSelector =
			'.wp-block-woocommerce-filter-wrapper .wp-block-heading';

		const title = 'New Title';

		await page.locator( textSelector ).fill( title );

		await expect( page.locator( textSelector ) ).toHaveText( title );
	} );

	test( 'should allow changing the display style', async ( {
		page,
		editorUtils,
		editor,
	} ) => {
		const stockFilter = await editorUtils.getBlockByName( blockData.slug );
		await editor.selectBlocks( stockFilter );

		await expect(
			page.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
		).toBeVisible();

		await page.getByLabel( 'DropDown' ).click();

		await expect(
			stockFilter.getByRole( 'checkbox', {
				name: 'In Stock',
			} )
		).toBeHidden();

		await expect(
			page.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
		).toBeHidden();

		await expect( page.getByRole( 'combobox' ) ).toBeVisible();
	} );

	test( 'should allow toggling the visibility of the filter button', async ( {
		page,
		editorUtils,
		editor,
	} ) => {
		const priceFilterControls = await editorUtils.getBlockByName(
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
	test.beforeEach( async ( { admin, page, editor, editorUtils } ) => {
		await cli(
			'npm run wp-env run tests-cli -- wp option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );

		await editorUtils.enterEditMode();

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'rating-filter',
				heading: 'Filter By Rating',
			},
		} );

		await editor.saveSiteEditorEntities();
		await page.goto( `/shop` );
	} );

	test( 'should show all products', async ( { frontendUtils, page } ) => {
		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 16 );

		await expect(
			page.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
		).toBeVisible();
	} );

	test( 'should show only products that match the filter', async ( {
		frontendUtils,
		page,
	} ) => {
		await page
			.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
			.click();

		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);

		await expect( products ).toHaveCount( 1 );
	} );
} );

test.describe( `${ blockData.name } Block - with Product Collection`, () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.updateTemplateContents(
			'woocommerce/woocommerce//archive-product',
			TEMPLATE_PATH,
			{}
		);
	} );

	test( 'should show all products', async ( { page } ) => {
		await page.goto( PRODUCT_CATALOG_LINK );
		const products = page
			.locator( '.wp-block-woocommerce-product-template' )
			.getByRole( 'listitem' );

		await expect( products ).toHaveCount( 16 );
	} );

	test( 'should show only products that match the filter', async ( {
		page,
	} ) => {
		await page.goto( PRODUCT_CATALOG_LINK );
		await page
			.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
			.click();

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
		editorUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );

		await editorUtils.enterEditMode();

		const ratingFilterControls = await editorUtils.getBlockByName(
			'woocommerce/rating-filter'
		);
		await expect( ratingFilterControls ).toBeVisible();
		await editor.selectBlocks( ratingFilterControls );
		await editor.openDocumentSettingsSidebar();
		await page.getByText( "Show 'Apply filters' button" ).click();

		await editor.saveSiteEditorEntities();
		await page.goto( PRODUCT_CATALOG_LINK );

		await page
			.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
			.click();
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
