/**
 * External dependencies
 */
import { test, expect, BASE_URL, flushMacrotask } from '@woocommerce/e2e-utils';

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
	test.beforeEach( async ( { page, createPost } ) => {
		const post = await createPost( 'price-filter-with-all-products' );
		await page.goto( post.link );

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
		// More than one product, so the test cannot pass against a page that was
		// already filtered.
		expect(
			( await productTitles.allTextContents() ).length
		).toBeGreaterThan( 1 );

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

	test( 'restores decimal URL prices without rejecting the Store API request', async ( {
		page,
	} ) => {
		const filteredPageUrl = new URL( page.url() );
		filteredPageUrl.searchParams.set( 'min_price', '1.1' );

		const productsResponse = page.waitForResponse( ( response ) => {
			const responseUrl = new URL( response.url() );

			return (
				responseUrl.pathname.endsWith( '/wc/store/v1/products' ) &&
				responseUrl.searchParams.get( 'min_price' ) ===
					'110.00000000000001'
			);
		} );

		await page.goto( filteredPageUrl.toString() );

		expect( ( await productsResponse ).status() ).toBe( 200 );
	} );
} );

test.describe( `${ blockData.name } Block - with Product Collection`, () => {
	test( 'filters Product Collection automatically and defers changes until Apply when configured', async ( {
		page,
		frontendUtils,
		createPost,
	} ) => {
		await page.clock.install();
		const post = await createPost( 'filters-with-product-collection' );
		const productTitles = page.locator(
			'.wp-block-woocommerce-product-template .wp-block-post-title'
		);

		await page.goto( post.link );
		await expect( productTitles.first() ).toBeVisible();
		const automaticBaseline = ( await productTitles.allTextContents() ).map(
			( title ) => title.trim()
		);
		// Greater than one, not merely non-empty: the unfiltered post must show more
		// than the single product the filter is about to leave behind. Asserting only
		// that it is non-empty passes on a page that arrived already filtered,
		// and then the post-filter assertion passes too, for the wrong reason.
		expect( automaticBaseline.length ).toBeGreaterThan( 1 );

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

		const deferredPost = await createPost(
			'filters-with-product-collection',
			{ showFilterButton: true }
		);
		await page.goto( deferredPost.link );
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
		// Greater than one, not merely non-empty: the unfiltered post must show more
		// than the single product the filter is about to leave behind. Asserting only
		// that it is non-empty passes on a page that arrived already filtered,
		// and then the post-filter assertion passes too, for the wrong reason.
		expect( deferredBaseline.length ).toBeGreaterThan( 1 );
		const deferredUrl = page.url();

		await deferredMaxPriceInput.dblclick();
		await deferredMaxPriceInput.fill( '$5' );
		await page.clock.runFor( 1_001 );
		await flushMacrotask( page );
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
		await flushMacrotask( page );
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
