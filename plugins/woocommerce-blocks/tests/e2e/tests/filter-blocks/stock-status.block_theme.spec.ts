/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Product Filter: Stock Status Block', async () => {
	test.describe( 'frontend', () => {
		test( 'Renders a checkbox list with the available stock statuses', async ( {
			page,
		} ) => {
			await page.goto( '/product-filters-stock-status-block/' );

			const stockStatuses = page.locator(
				'.wc-block-components-checkbox__label'
			);

			await expect( stockStatuses ).toHaveCount( 2 );

			await expect( stockStatuses.nth( 0 ) ).toHaveText( 'In stock' );
			await expect( stockStatuses.nth( 1 ) ).toHaveText( 'Out of stock' );
		} );

		test( 'Selecting a stock status filters the list of products', async ( {
			page,
		} ) => {
			await page.goto( '/product-filters-stock-status-block/' );

			const stockStatusCheckboxes = page.locator(
				'.wc-block-components-checkbox__input'
			);

			// Out of stock
			stockStatusCheckboxes.nth( 1 ).check();

			// wait for navigation
			await page.waitForURL(
				'/product-filters-stock-status-block/?filter_stock_status=outofstock'
			);

			const products = page.locator( '.wc-block-product' );
			const productCount = await products.count();

			expect( productCount ).toBe( 1 );
		} );
	} );
} );
