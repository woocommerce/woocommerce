/**
 * External dependencies
 */
import { randomUUID } from 'crypto';
import {
	expect,
	getProductAttributeIds,
	PostCompiler,
	test as base,
} from '@woocommerce/e2e-utils';

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
		requestUtils,
		cleanup,
	} ) => {
		// WordPress uses `single-post-<slug>` for this post only. It has no
		// header, so the page has no Mini-Cart block.
		const postSlug = `all-products-no-header-${ randomUUID() }`;
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: `single-post-${ postSlug }`,
			title: 'All Products without header',
			content: '<!-- wp:post-content /-->',
		} );
		cleanup( () =>
			requestUtils.revertTemplate( 'wp_template', template.id )
		);

		const post = await requestUtils.rest< { id: number } >( {
			method: 'POST',
			path: 'wp/v2/posts',
			data: {
				title: 'All Products without Mini-Cart',
				slug: postSlug,
				status: 'draft',
			},
		} );
		cleanup( () =>
			requestUtils.rest( {
				method: 'DELETE',
				path: `wp/v2/posts/${ post.id }`,
				params: { force: true },
			} )
		);

		await admin.editPost( post.id );
		await editor.insertBlock( { name: BLOCK_NAME } );
		await editor.publishAndVisitPost();
		await page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wc/store/v1/products' ) &&
				response.status() === 200
		);

		await expect(
			page.locator( '[data-block-name="woocommerce/mini-cart"]' )
		).toHaveCount( 0 );
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
		const post = await postCompiler.compile(
			await getProductAttributeIds()
		);

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
		await expect(
			products.getByText( 'Album', { exact: true } )
		).toBeVisible();
		await expect( outOfStockCheckbox ).toBeVisible();
		await outOfStockCheckbox.click();

		await expect( page ).toHaveURL( /filter_stock_status=outofstock/ );
		// Other specs can add out-of-stock products, so check the sample one
		// is listed and an in-stock one is gone instead of an exact count.
		await expect(
			products.getByText( 'T-Shirt with Logo', { exact: true } )
		).toBeVisible();
		await expect(
			products.getByText( 'Album', { exact: true } )
		).toHaveCount( 0 );
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
