/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { BASE_URL, cli } from '@woocommerce/e2e-utils';

const blockData: BlockData< {
	urlSearchParamWhenFilterIsApplied: string;
	endpointAPI: string;
	placeholderUrl: string;
} > = {
	name: 'woocommerce/price-filter',
	mainClass: '.wc-block-price-filter',
	selectors: {
		frontend: {},
		editor: {},
	},
	urlSearchParamWhenFilterIsApplied: '?max_price=5',
	endpointAPI: 'max_price=500',
	placeholderUrl: `${ BASE_URL }/wp-content/plugins/woocommerce/assets/images/placeholder.png`,
};

test.describe( `${ blockData.name } Block - with All products Block`, () => {
	test.beforeEach( async ( { admin, page, editor } ) => {
		await admin.createNewPost( { legacyCanvas: true } );
		await editor.insertBlock( { name: 'woocommerce/all-products' } );
		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'price-filter',
				heading: 'Filter By Price',
			},
		} );
		await editor.publishPost();
		const url = new URL( page.url() );
		const postId = url.searchParams.get( 'post' );

		await page.goto( `/?p=${ postId }` );

		await page
			.waitForResponse(
				async ( response ) => {
					if (
						response.url().includes( 'products/collection-data' )
					) {
						const payload = await response.json();
						// Price range seems to be the last thing to be loaded.
						const containsPriceRange = !! payload.price_range;

						return containsPriceRange;
					}
					return false;
				},
				{ timeout: 3000 }
			)
			.catch( () => {
				// Do nothing. This is only to ensure the products are loaded.
				// There are multiple requests until the products are fully
				// loaded. We need to ensure the page is ready to be interacted
				// with, hence the extra check. Ideally, this should be signaled
				// by the UI (e.g., by a loading spinner), but we don't have
				// that yet.
			} );
	} );

	test( 'should show all products', async ( { frontendUtils } ) => {
		const allProductsBlock = await frontendUtils.getBlockByName(
			'woocommerce/all-products'
		);

		const img = allProductsBlock.locator( 'img' ).first();

		await expect( img ).not.toHaveAttribute(
			'src',
			blockData.placeholderUrl
		);

		const products = allProductsBlock.getByRole( 'listitem' );

		await expect( products ).toHaveCount( 9 );
	} );

	test( 'should show only products that match the filter', async ( {
		page,
		frontendUtils,
	} ) => {
		// The price filter input is initially enabled, but it becomes disabled
		// for the time it takes to fetch the data. To avoid setting the filter
		// value before the input is properly initialized, we wait for the input
		// to be disabled first. This is a safeguard to avoid flakiness which
		// should be addressed in the code, but All Products block will be
		// deprecated in the future, so we are not going to optimize it.
		await page
			.getByRole( 'textbox', {
				name: 'Filter products by maximum price',
				disabled: true,
			} )
			.waitFor( { timeout: 3000 } )
			.catch( () => {
				// Do not throw in case Playwright doesn't make it in time for the
				// initial (pre-request) render.
			} );

		const maxPriceInput = page.getByRole( 'textbox', {
			name: 'Filter products by maximum price',
		} );
		// await page.pause();

		await maxPriceInput.dblclick();
		await maxPriceInput.fill( '$5' );
		await maxPriceInput.press( 'Tab' );

		const allProductsBlock = await frontendUtils.getBlockByName(
			'woocommerce/all-products'
		);

		const img = allProductsBlock.locator( 'img' ).first();
		await expect( img ).not.toHaveAttribute(
			'src',
			blockData.placeholderUrl
		);

		const allProducts = allProductsBlock.getByRole( 'listitem' );

		await expect( allProducts ).toHaveCount( 1 );
		expect( page.url() ).toContain(
			blockData.urlSearchParamWhenFilterIsApplied
		);
	} );
} );
// These tests are disabled because there is an issue with the default contents of this page, possible caused by other tests.
test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test.beforeEach( async ( { admin, page, editor } ) => {
		await cli(
			'npm run wp-env run tests-cli -- wp option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );

		await editor.canvas.locator( 'body' ).click();

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'price-filter',
				heading: 'Filter By Price',
			},
		} );
		await editor.saveSiteEditorEntities();
		await page.goto( `/shop` );
	} );

	test( 'should show all products', async ( { frontendUtils } ) => {
		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 16 );
	} );

	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'should show only products that match the filter', async ( {
		page,
		frontendUtils,
	} ) => {
		const maxPriceInput = page.getByRole( 'textbox', {
			name: 'Filter products by maximum price',
		} );

		await frontendUtils.selectTextInput( maxPriceInput );
		await maxPriceInput.fill( '$5' );
		await maxPriceInput.press( 'Tab' );
		await page.waitForURL( ( url ) =>
			url
				.toString()
				.includes( blockData.urlSearchParamWhenFilterIsApplied )
		);

		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 1 );
	} );
} );
