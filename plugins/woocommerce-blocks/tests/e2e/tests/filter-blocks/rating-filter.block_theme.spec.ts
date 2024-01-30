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
			await page.evaluate( () =>
				window.scrollTo( 0, document.body.scrollHeight * 0.35 )
			);

			const ratingStars = page.locator(
				'.wc-block-components-product-rating__stars'
			);
			const count = await ratingStars.count();
			expect( count ).toBe( 1 );

			//  See bin/scripts/parallel/reviews.sh for reviews data.
			await expect( ratingStars.nth( 0 ) ).toHaveAttribute(
				'aria-label',
				'Rated 4 out of 5'
			);
			// await expect( ratingStars.nth( 1 ) ).toHaveAttribute(
			// 	'aria-label',
			// 	'Rated 5 out of 5'
			// );
		} );
	} );
} );
