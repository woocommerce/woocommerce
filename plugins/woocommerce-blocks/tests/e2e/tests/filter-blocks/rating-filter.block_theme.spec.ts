/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Product Filter: Rating Filter Block', async () => {
	test.describe( 'frontend', () => {
		test( 'Renders a checkbox list with the available ratings', async ( {
			page,
		} ) => {
			await page.goto( '/product-filters-rating-block/' );
			const ratingStars = page.locator(
				'.wc-block-components-product-rating__stars'
			);
			const count = await ratingStars.count();
			expect( count ).toBe( 2 );

			//  See bin/scripts/parallel/reviews.sh for reviews data.
			await expect( ratingStars.nth( 0 ) ).toHaveAttribute(
				'aria-label',
				'Rated 1 out of 5'
			);
			await expect( ratingStars.nth( 1 ) ).toHaveAttribute(
				'aria-label',
				'Rated 5 out of 5'
			);
		} );

		test( 'Selecting a checkbox filters down the products', async ( {
			page,
		} ) => {
			await page.goto( '/product-filters-rating-block/' );

			const ratingCheckboxes = page.locator(
				'.wc-block-components-checkbox__input'
			);

			ratingCheckboxes.nth( 0 ).check();

			// wait for navigation
			await page.waitForURL(
				'/product-filters-rating-block/?rating_filter=1'
			);

			const products = page.locator( '.wc-block-product' );
			const productCount = await products.count();

			expect( productCount ).toBe( 1 );
		} );
	} );
} );
