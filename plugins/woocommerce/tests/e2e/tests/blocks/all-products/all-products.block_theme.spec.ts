/**
 * External dependencies
 */
import {
	BLOCK_THEME_SLUG,
	expect,
	PostCompiler,
	test as base,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const BLOCK_NAME = 'woocommerce/all-products';

const test = base.extend< { postCompiler: PostCompiler } >( {
	postCompiler: async ( { requestUtils }, use ) => {
		const post = await requestUtils.createPostFromFile(
			'legacy-filters-with-all-products'
		);

		await use( post );
	},
} );

test.describe( `${ BLOCK_NAME } Block`, () => {
	// Check this regression: hhttps://github.com/woocommerce/woocommerce/pull/58741.
	// The block has a dependency on the Mini Cart block/Checkout/Cart blocks.
	// This test checks that the block can be inserted and it is rendered on the frontend without the mini cart block.
	test( 'block can be inserted and it is rendered on the frontend without the Mini-Cart block', async ( {
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

	// Regression coverage for legacy filter markup:
	// https://github.com/woocommerce/woocommerce-blocks/pull/9954
	test( 'legacy filter blocks render and filter All Products', async ( {
		page,
		postCompiler,
	} ) => {
		const post = await postCompiler.compile();

		const productsResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wc/store/v1/products' ) &&
				response.status() === 200
		);
		await page.goto( post.link );
		await productsResponse;

		const products = page.locator(
			'.wc-block-grid__product.wc-block-layout'
		);

		await expect( products ).toHaveCount( 9 );
		await expect(
			page.getByRole( 'textbox', {
				name: 'Filter products by minimum price',
			} )
		).toBeVisible();
		await expect( page.getByPlaceholder( 'Select Color' ) ).toBeVisible();
		await expect(
			page.getByRole( 'checkbox', { name: 'Small' } )
		).toBeVisible();

		const outOfStockCheckbox = page.getByRole( 'checkbox', {
			name: 'Out of Stock',
		} );
		await expect( outOfStockCheckbox ).toBeVisible();
		await outOfStockCheckbox.click();

		await expect( page ).toHaveURL( /filter_stock_status=outofstock/ );
		await expect( products ).toHaveCount( 1 );
		await expect(
			page.getByRole( 'heading', { name: 'Active filters' } )
		).toBeVisible();
		await expect( page.getByText( 'Stock Status:' ) ).toBeVisible();

		const clearAllFiltersButton = page.getByRole( 'button', {
			name: 'Clear All Filters',
		} );
		await expect( clearAllFiltersButton ).toBeVisible();
		await clearAllFiltersButton.click();

		await expect( page ).not.toHaveURL( /filter_stock_status/ );
		await expect( products ).toHaveCount( 9 );
	} );
} );
