/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Product Filter: Active Filters Block', async () => {
	test.describe( 'frontend', () => {
		test( 'Without any filters selected, only a wrapper block is rendered', async ( {
			page,
		} ) => {
			await page.goto( '/product-filters-active-block/' );

			const locator = page.locator(
				'.wp-block-woocommerce-product-filter'
			);

			const count = await locator.count();
			expect( count ).toBe( 1 );

			const html = await locator.innerHTML();
			expect( html.trim() ).toBe( '' );
		} );

		test( 'With rating filters applied it shows the correct active filters', async ( {
			page,
		} ) => {
			await page.goto(
				'/product-filters-active-block/?rating_filter=1,2,5'
			);

			const listLocator = page.locator( '.filter-list' );
			const hasTitle =
				( await listLocator.locator( 'text=Rating:' ).count() ) === 1;

			expect( hasTitle ).toBe( true );

			for ( const text of [
				'Rated 1 out of 5',
				'Rated 2 out of 5',
				'Rated 5 out of 5',
			] ) {
				const hasFilter =
					( await listLocator
						.locator( `text=${ text }` )
						.count() ) === 1;
				expect( hasFilter ).toBe( true );
			}
		} );

		test( 'With stock filters applied it shows the correct active filters', async ( {
			page,
		} ) => {
			await page.goto(
				'/product-filters-active-block/?filter_stock_status=instock,onbackorder'
			);

			const listLocator = page.locator( '.filter-list' );
			const hasTitle =
				( await listLocator
					.locator( 'text=Stock Status:' )
					.count() ) === 1;

			expect( hasTitle ).toBe( true );

			for ( const text of [ 'In stock', 'On backorder' ] ) {
				const hasFilter =
					( await listLocator
						.locator( `text=${ text }` )
						.count() ) === 1;
				expect( hasFilter ).toBe( true );
			}
		} );

		test( 'With attribute filters applied it shows the correct active filters', async ( {
			page,
		} ) => {
			await page.goto(
				'/product-filters-active-block/?filter_color=blue,gray&query_type_color=or'
			);

			const listLocator = page.locator( '.filter-list' );
			const hasTitle =
				( await listLocator.locator( 'text=Color:' ).count() ) === 1;

			expect( hasTitle ).toBe( true );

			for ( const text of [ 'Blue', 'Gray' ] ) {
				const hasFilter =
					( await listLocator
						.locator( `text=${ text }` )
						.count() ) === 1;
				expect( hasFilter ).toBe( true );
			}
		} );

		test( 'With price filters applied it shows the correct active filters', async ( {
			page,
		} ) => {
			await page.goto(
				'/product-filters-active-block/?min_price=17&max_price=71'
			);

			const listLocator = page.locator( '.filter-list' );
			const hasTitle =
				( await listLocator.locator( 'text=Price:' ).count() ) === 1;

			expect( hasTitle ).toBe( true );

			const hasFilter =
				( await listLocator
					.locator( `text=Between $17 and $71` )
					.count() ) === 1;
			expect( hasFilter ).toBe( true );
		} );
	} );
} );
