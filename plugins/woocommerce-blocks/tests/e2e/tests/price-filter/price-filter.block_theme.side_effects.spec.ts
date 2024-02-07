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
	urlSearchParamWhenFilterIsApplied: '?max_price=10',
	endpointAPI: 'max_price=1000',
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
		await page.goto( `/?p=${ postId }`, { waitUntil: 'commit' } );
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

		const products = await allProductsBlock.getByRole( 'listitem' ).all();

		expect( products ).toHaveLength( 9 );
	} );

	test( 'should show only products that match the filter', async ( {
		page,
		frontendUtils,
	} ) => {
		const maxPriceInput = page.getByRole( 'textbox', {
			name: 'Filter products by maximum price',
		} );

		await frontendUtils.selectTextInput( maxPriceInput );
		await maxPriceInput.fill( '$10' );
		await maxPriceInput.press( 'Tab' );
		await page.waitForResponse( ( response ) =>
			response.url().includes( blockData.endpointAPI )
		);

		const allProductsBlock = await frontendUtils.getBlockByName(
			'woocommerce/all-products'
		);

		const img = allProductsBlock.locator( 'img' ).first();

		await expect( img ).not.toHaveAttribute(
			'src',
			blockData.placeholderUrl
		);

		const products = await allProductsBlock.getByRole( 'listitem' ).all();

		expect( products ).toHaveLength( 2 );
		expect( page.url() ).toContain(
			blockData.urlSearchParamWhenFilterIsApplied
		);
	} );
} );
// These tests are disabled because there is an issue with the default contents of this page, possible caused by other tests.
test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test.beforeAll( async () => {
		await cli(
			'npm run wp-env run tests-cli -- wp option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);
	} );
	test.beforeEach( async ( { admin, page, editor } ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );

		await editor.canvas.click( 'body' );

		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'price-filter',
				heading: 'Filter By Price',
			},
		} );
		await editor.saveSiteEditorEntities();
		await page.goto( `/shop`, { waitUntil: 'commit' } );
	} );

	test.afterEach( async ( { templateApiUtils } ) => {
		await templateApiUtils.revertTemplate(
			'woocommerce/woocommerce//archive-product'
		);
	} );

	test( 'should show all products', async ( { frontendUtils } ) => {
		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		legacyTemplate.waitFor();

		const products = await legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' )
			.all();

		expect( products ).toHaveLength( 16 );
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
		await maxPriceInput.fill( '$10' );
		await maxPriceInput.press( 'Tab' );
		await page.waitForURL( ( url ) =>
			url
				.toString()
				.includes( blockData.urlSearchParamWhenFilterIsApplied )
		);

		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const products = await legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' )
			.all();

		expect( products ).toHaveLength( 1 );
	} );

	test.afterAll( async () => {
		await cli(
			'npm run wp-env run tests-cli -- wp option delete wc_blocks_use_blockified_product_grid_block_as_template'
		);
	} );
} );
