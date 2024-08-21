/**
 * External dependencies
 */
import {
	test as base,
	expect,
	TemplateCompiler,
	BASE_URL,
	wpCLI,
} from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/price-filter',
	name: 'Filter by Price',
	mainClass: '.wc-block-price-filter',
	selectors: {
		frontend: {},
		editor: {},
	},
	urlSearchParamWhenFilterIsApplied: 'max_price=5',
	endpointAPI: 'max_price=500',
	placeholderUrl: `${ BASE_URL }/wp-content/plugins/woocommerce/assets/images/placeholder.png`,
};

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_filters-with-product-collection'
		);
		await use( compiler );
	},
} );

test.describe( `${ blockData.name } Block - editor side`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'price-filter',
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
		const priceFilterControls = await editor.getBlockByName(
			'woocommerce/price-filter'
		);
		await editor.selectBlocks( priceFilterControls );

		await expect(
			priceFilterControls.getByRole( 'textbox', {
				name: 'Filter products by minimum',
			} )
		).toBeVisible();

		await expect(
			priceFilterControls.getByRole( 'textbox', {
				name: 'Filter products by maximum',
			} )
		).toBeVisible();

		await page
			.getByLabel( 'Price Range Selector' )
			.getByText( 'Text' )
			.click();

		await expect(
			priceFilterControls.getByRole( 'textbox', {
				name: 'Filter products by minimum',
			} )
		).toBeHidden();

		await expect(
			priceFilterControls.getByRole( 'textbox', {
				name: 'Filter products by maximum',
			} )
		).toBeHidden();
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

test.describe( `${ blockData.name } Block - with All products Block`, () => {
	test.beforeEach( async ( { admin, page, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: 'woocommerce/all-products' } );
		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'price-filter',
				heading: 'Filter By Price',
			},
		} );

		const postId = await editor.publishPost();
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
				filterType: 'price-filter',
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

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 16 );
	} );

	test( 'should show only products that match the filter', async ( {
		page,
		frontendUtils,
	} ) => {
		const maxPriceInput = page.getByRole( 'textbox', {
			name: 'Filter products by maximum price',
		} );

		await frontendUtils.selectTextInput( maxPriceInput );
		await maxPriceInput.fill( '$5' );
		await maxPriceInput.press( 'Tab' );
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
		frontendUtils,
		templateCompiler,
	} ) => {
		await templateCompiler.compile();

		await page.goto( '/shop' );
		const maxPriceInput = page.getByRole( 'textbox', {
			name: 'Filter products by maximum price',
		} );

		await frontendUtils.selectTextInput( maxPriceInput );
		await maxPriceInput.fill( '$5' );
		await maxPriceInput.press( 'Tab' );
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

		const priceFilterControls = await editor.getBlockByName(
			blockData.slug
		);
		await expect( priceFilterControls ).toBeVisible();
		await editor.selectBlocks( priceFilterControls );
		await editor.openDocumentSettingsSidebar();
		await page.getByText( "Show 'Apply filters' button" ).click();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await page.goto( '/shop' );

		const maxPriceInput = page.getByRole( 'textbox', {
			name: 'Filter products by maximum price',
		} );

		await maxPriceInput.dblclick();
		await maxPriceInput.fill( '$5' );

		const resetPriceFilterButton = page.getByRole( 'button', {
			name: 'Reset price filter',
		} );
		await expect( resetPriceFilterButton ).toBeVisible();

		await page
			.getByRole( 'button', { name: 'Apply price filter' } )
			.click();

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);

		const products = page
			.locator( '.wp-block-woocommerce-product-template' )
			.getByRole( 'listitem' );

		await expect( products ).toHaveCount( 1 );
	} );
} );
