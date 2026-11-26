/**
 * External dependencies
 */
import { test, expect, flushMacrotask } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Filter by Stock',
	urlSearchParamWhenFilterIsApplied: 'filter_stock_status=outofstock',
};

test.describe( `${ blockData.name } Block - with Product Collection`, () => {
	test( 'filters Product Collection automatically and defers changes until Apply when configured', async ( {
		page,
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

		await page.getByText( 'Out of Stock' ).click();
		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'T-Shirt with Logo' ] );

		const deferredPost = await createPost(
			'filters-with-product-collection',
			{ showFilterButton: true }
		);
		await page.goto( deferredPost.link );
		await expect( productTitles.first() ).toBeVisible();
		const outOfStockFilter = page.getByRole( 'checkbox', {
			name: 'Out of Stock',
		} );
		await expect( outOfStockFilter ).toBeVisible();
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

		await outOfStockFilter.click();
		await page.clock.runFor( 501 );
		await flushMacrotask( page );
		await expect( outOfStockFilter ).toBeChecked();
		const applyButton = page.getByRole( 'button', {
			name: 'Apply stock filter',
		} );
		await expect( applyButton ).toBeVisible();
		await expect( applyButton ).toBeEnabled();
		await expect( page ).toHaveURL( deferredUrl );
		await expect( productTitles ).toHaveText( deferredBaseline );

		await page.clock.resume();
		await applyButton.click();
		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'T-Shirt with Logo' ] );
	} );
} );
