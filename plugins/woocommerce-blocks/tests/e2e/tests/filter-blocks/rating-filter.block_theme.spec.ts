/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { Post } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';
import path from 'path';

const TEMPLATE_PATH = path.join( __dirname, './rating-filter.handlebars' );

const test = base.extend< {
	defaultBlockPostPage: Post;
} >( {
	defaultBlockPostPage: async ( { requestUtils }, use ) => {
		const testingPost = await requestUtils.createPostFromTemplate(
			{ title: 'Active Filters Block' },
			TEMPLATE_PATH,
			{}
		);

		await use( testingPost );
		await requestUtils.deletePost( testingPost.id );
	},
} );

test.describe( 'Product Filter: Rating Filter Block', async () => {
	test.describe( 'frontend', () => {
		test( 'Renders a checkbox list with the available ratings', async ( {
			page,
			defaultBlockPostPage,
		} ) => {
			await page.goto( defaultBlockPostPage.link );

			const ratingStars = page.getByLabel( /^Rated \d out of 5/ );
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
			defaultBlockPostPage,
		} ) => {
			await page.goto( defaultBlockPostPage.link );

			const ratingCheckboxes = page.getByLabel(
				/Checkbox: Rated \d out of 5/
			);

			ratingCheckboxes.nth( 0 ).check();

			// wait for navigation
			await page.waitForURL(
				( url ) =>
					url.searchParams.has( 'rating_filter' ) &&
					url.searchParams.get( 'rating_filter' ) === '1'
			);

			const products = page.locator( '.wc-block-product' );
			const productCount = await products.count();

			expect( productCount ).toBe( 1 );
		} );
	} );
} );
