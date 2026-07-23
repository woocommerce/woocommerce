/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { allReviews, hoodieReviews } from '../../../test-data/blocks/data/data';

const BLOCK_NAME = 'woocommerce/reviews-by-product';

const DEFAULT_BLOCK_CONTENT = `<!-- wp:woocommerce/reviews-by-product -->
<div class="wp-block-woocommerce-reviews-by-product wc-block-all-reviews has-image has-name has-date has-rating has-content" data-image-type="reviewer" data-orderby="most-recent" data-reviews-on-page-load="10" data-reviews-on-load-more="10" data-show-load-more="true" data-show-orderby="true"></div>
<!-- /wp:woocommerce/reviews-by-product -->`;

const latestReview = allReviews[ allReviews.length - 1 ];

const highestRating = [ ...allReviews ].sort(
	( a, b ) => b.rating - a.rating
)[ 0 ];

const lowestRating = [ ...allReviews ].sort(
	( a, b ) => a.rating - b.rating
)[ 0 ];

test.describe( `${ BLOCK_NAME } Block`, () => {
	test( 'block can be inserted and it successfully renders a review in the editor and the frontend', async ( {
		admin,
		page,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );

		const productCheckbox = editor.canvas.getByLabel(
			'Hoodie, has 2 reviews'
		);
		await productCheckbox.check();
		await expect( productCheckbox ).toBeChecked();

		const doneButton = editor.canvas.getByRole( 'button', {
			name: 'Done',
		} );
		await doneButton.click();

		await expect(
			editor.canvas.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();

		await editor.publishAndVisitPost();

		await expect(
			page.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();
	} );

	test( 'should sort reviews by most recent, highest, and lowest ratings', async ( {
		page,
		frontendUtils,
		requestUtils,
	} ) => {
		const cleanUrl =
			await test.step( 'sorts by most recent by default and can sort by highest rating', async () => {
				type PostResponse = {
					id: number;
					type: string;
					status: string;
					slug: string;
					link: string;
					title: { raw: string };
					content: { raw: string; rendered: string };
					generated_slug: string;
				};
				const post = await requestUtils.rest< PostResponse >( {
					method: 'POST',
					path: 'wp/v2/posts?context=edit',
					data: {
						status: 'publish',
						content: DEFAULT_BLOCK_CONTENT,
					},
				} );
				const configuredBaseUrl = process.env.BASE_URL;
				if ( ! configuredBaseUrl ) {
					throw new Error(
						'BASE_URL must be configured for this test'
					);
				}
				const expectedQueryPostUrl = new URL(
					`?p=${ post.id }`,
					configuredBaseUrl
				);
				const expectedCanonicalPostUrl = new URL(
					`/${ post.slug }/`,
					configuredBaseUrl
				);
				let postUrl: URL;
				try {
					postUrl = new URL( post.link );
				} catch {
					throw new Error(
						`REST created a post with an invalid link: ${ JSON.stringify(
							post
						) }`
					);
				}
				if (
					! Number.isInteger( post.id ) ||
					post.id <= 0 ||
					post.type !== 'post' ||
					post.status !== 'publish' ||
					post.slug !== post.generated_slug ||
					! new RegExp( `^${ post.id }(?:-\\d+)?$` ).test(
						post.slug
					) ||
					post.title.raw !== '' ||
					post.content.raw !== DEFAULT_BLOCK_CONTENT ||
					! post.content.rendered.includes(
						'data-block-name="woocommerce/reviews-by-product"'
					) ||
					! post.content.rendered.includes(
						'class="wp-block-woocommerce-reviews-by-product wc-block-all-reviews has-image has-name has-date has-rating has-content"'
					) ||
					postUrl.href !== expectedCanonicalPostUrl.href
				) {
					throw new Error(
						`REST did not create the expected Reviews by Product post: ${ JSON.stringify(
							post
						) }`
					);
				}

				await page.goto( expectedQueryPostUrl.href );
				const publishedPostUrl = page.url();
				const block = await frontendUtils.getBlockByName( BLOCK_NAME );

				const reviews = block.locator(
					'.wc-block-components-review-list-item__text'
				);

				await expect( reviews.first() ).toHaveText(
					latestReview.review
				);

				const select = page.getByLabel( 'Order by' );
				await select.selectOption( 'Highest rating' );

				await expect( reviews.first() ).toHaveText(
					highestRating.review
				);

				return publishedPostUrl;
			} );

		await test.step( 'can sort by lowest rating', async () => {
			await page.goto( cleanUrl );

			const block = await frontendUtils.getBlockByName( BLOCK_NAME );

			const reviews = block.locator(
				'.wc-block-components-review-list-item__text'
			);

			await expect( reviews.first() ).toHaveText( latestReview.review );

			const select = page.getByLabel( 'Order by' );
			await select.selectOption( 'Lowest rating' );

			await expect( reviews.first() ).toHaveText( lowestRating.review );
		} );
	} );
} );
