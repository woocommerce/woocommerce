/**
 * External dependencies
 */
import {
	test as base,
	expect,
	TemplateCompiler,
	BASE_URL,
	wpCLI,
	BLOCK_THEME_SLUG,
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
	placeholderUrl: `${ BASE_URL }/wp-content/plugins/woocommerce/assets/images/placeholder.webp`,
};

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, provideTemplateCompiler ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_filters-with-product-collection'
		);

		await provideTemplateCompiler( compiler );
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

	test( 'edits the title, display style, and Apply behavior', async ( {
		page,
		editor,
	} ) => {
		const textSelector =
			'.wp-block-woocommerce-filter-wrapper .wp-block-heading';
		const title = 'New Title';

		await editor.canvas.locator( textSelector ).fill( title );
		await expect( editor.canvas.locator( textSelector ) ).toHaveText(
			title
		);

		const priceFilterControls = await editor.getBlockByName(
			blockData.slug
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

	test( 'filters the All Products block by maximum price', async ( {
		page,
		frontendUtils,
	} ) => {
		const allProductsBlock = await frontendUtils.getBlockByName(
			'woocommerce/all-products'
		);
		const productTitles = allProductsBlock.locator(
			'.wc-block-grid__product-title'
		);

		await expect( productTitles.first() ).toBeVisible();
		expect( await productTitles.allTextContents() ).not.toHaveLength( 0 );

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

		const img = allProductsBlock.locator( 'img' ).first();
		await expect( img ).not.toHaveAttribute(
			'src',
			blockData.placeholderUrl
		);

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'Single' ] );
	} );
} );

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
		expect( await productTitles.allTextContents() ).not.toHaveLength( 0 );

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

test.describe( `${ blockData.name } Block - with Product Collection`, () => {
	test( 'filters Product Collection automatically and defers changes until Apply when configured', async ( {
		page,
		admin,
		editor,
		frontendUtils,
		templateCompiler,
	} ) => {
		await page.clock.install();
		const template = await templateCompiler.compile();
		const productTitles = page.locator(
			'.wp-block-woocommerce-product-template .wp-block-post-title'
		);

		await page.goto( '/shop' );
		await expect( productTitles.first() ).toBeVisible();
		const automaticBaseline = ( await productTitles.allTextContents() ).map(
			( title ) => title.trim()
		);
		expect( automaticBaseline ).not.toHaveLength( 0 );

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
		await expect( productTitles.first() ).toBeVisible();
		const deferredMaxPriceInput = page.getByRole( 'textbox', {
			name: 'Filter products by maximum price',
		} );
		await expect( deferredMaxPriceInput ).toBeVisible();
		await page.clock.pauseAt(
			( await page.evaluate( () => Date.now() ) ) + 1_000
		);
		const deferredBaseline = ( await productTitles.allTextContents() ).map(
			( title ) => title.trim()
		);
		expect( deferredBaseline ).not.toHaveLength( 0 );
		const deferredUrl = page.url();

		await deferredMaxPriceInput.dblclick();
		await deferredMaxPriceInput.fill( '$5' );
		await page.clock.runFor( 1_001 );
		await page.evaluate(
			() =>
				new Promise< void >( ( resolve ) => {
					const channel = new MessageChannel();
					channel.port1.addEventListener(
						'message',
						() => {
							channel.port1.close();
							channel.port2.close();
							resolve();
						},
						{ once: true }
					);
					channel.port1.start();
					channel.port2.postMessage( null );
				} )
		);
		await expect( deferredMaxPriceInput ).toHaveValue( '$5' );
		const resetPriceFilterButton = page.getByRole( 'button', {
			name: 'Reset price filter',
		} );
		await expect( resetPriceFilterButton ).toBeVisible();
		const applyButton = page.getByRole( 'button', {
			name: 'Apply price filter',
		} );
		await expect( applyButton ).toBeVisible();
		await expect( applyButton ).toBeEnabled();

		await page.clock.runFor( 501 );
		await page.evaluate(
			() =>
				new Promise< void >( ( resolve ) => {
					const channel = new MessageChannel();
					channel.port1.addEventListener(
						'message',
						() => {
							channel.port1.close();
							channel.port2.close();
							resolve();
						},
						{ once: true }
					);
					channel.port1.start();
					channel.port2.postMessage( null );
				} )
		);
		await expect( page ).toHaveURL( deferredUrl );
		await expect( productTitles ).toHaveText( deferredBaseline );

		await page.clock.resume();
		await applyButton.click();

		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'Single' ] );
	} );
} );
