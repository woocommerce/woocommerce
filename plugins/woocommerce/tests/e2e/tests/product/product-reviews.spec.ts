/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test as baseTest, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH, CUSTOMER_STATE_PATH } from '../../playwright.config';
import { getFakeProduct } from '../../utils/data';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	products: async ( { restApi }, use ) => {
		const products = [];

		// Create the products
		for ( let i = 0; i < 2; i++ ) {
			await restApi
				.post( `${ WC_API_PATH }/products`, getFakeProduct() )
				.then( ( response ) => {
					products.push( response.data );
				} );
		}

		await use( products );

		// Cleanup
		await restApi.post( `${ WC_API_PATH }/products/batch`, {
			delete: products.map( ( product ) => product.id ),
		} );
	},
	reviews: async ( { restApi, products }, use ) => {
		const timestamp = Date.now().toString();
		const reviews = [];

		for ( const product of products ) {
			await restApi
				.post( `${ WC_API_PATH }/products/reviews`, {
					product_id: product.id,
					review: `Nice product ${ product.name }, at ${ timestamp }`,
					reviewer: faker.person.fullName(),
					reviewer_email: faker.internet.email( {
						provider: 'example.fakerjs.dev',
					} ),
					rating: ( Math.random() * ( 5 - 1 ) + 1 ).toFixed( 0 ),
				} )
				.then( ( response ) => {
					reviews.push( response.data );
				} );
		}

		await use( reviews );

		for ( const review of reviews ) {
			try {
				await restApi.delete(
					`${ WC_API_PATH }/products/reviews/${ review.id }`
				);
			} catch ( error ) {
				// Ignore 410 error - which is expected if the review was already trashed
				if ( error.status !== 410 ) {
					throw error;
				}
			}
		}
	},
} );

test.describe( 'Product Reviews', () => {
	test.describe( 'Merchant manages reviews', () => {
		test( 'can filter the reviews by product', async ( {
			page,
			reviews,
		} ) => {
			await page.goto(
				`wp-admin/edit.php?post_type=product&page=product-reviews`
			);

			const review = reviews[ 0 ];

			await page.getByText( 'Search for a product' ).click();
			await page.locator( '.select2-search__field' ).click();
			await page
				.locator( '.select2-search__field' )
				.fill( review.product_name );
			await page
				.getByRole( 'option', { name: review.product_name } )
				.first()
				.click();
			await page.getByRole( 'button', { name: 'Filter' } ).click();

			await expect( page.locator( '#the-comment-list tr' ) ).toHaveCount(
				1
			);

			const reviewRow = page.locator( `#comment-${ review.id }` );
			await expect(
				reviewRow.locator( '[data-colname="Author"]' )
			).toContainText( review.reviewer_email );
			await expect(
				reviewRow
					.locator( '[data-colname="Rating"]' )
					.getByLabel( `${ review.rating } out of 5` )
			).toBeVisible();
			await expect(
				reviewRow.locator( '[data-colname="Review"]' )
			).toContainText( review.review );
			await expect(
				reviewRow
					.locator( '[data-colname="Product"]' )
					.getByRole( 'link' )
					.first()
			).toContainText( review.product_name );
		} );

		// Canary for the moderation actions. This batch moved review edit and reply
		// to ReviewsAjaxTest, but approve, spam and trash have no PHP coverage of
		// their state transition: ReviewsListTableTest::test_handle_row_actions
		// asserts only that the row renders those links. Nothing else in the suite
		// moderates a review.
		//
		// Every step checks the review's new status by loading the matching
		// filtered view rather than by looking at the row's own buttons. The list
		// renders both Approve and Unapprove in the all view regardless of status,
		// so asserting on them proves nothing -- an earlier version of this test
		// did exactly that and survived two mutations of the approve path.
		//
		// Spam and trash use different rows on purpose: once a review is spammed
		// its actions become Not Spam and Delete Permanently, so Trash is only
		// reachable from the main list.
		test( 'can approve, spam and trash a product review', async ( {
			page,
			reviews,
		} ) => {
			const reviewsUrl =
				'wp-admin/edit.php?post_type=product&page=product-reviews';
			const moderated = reviews[ 0 ];
			const trashed = reviews[ 1 ];

			const rowFor = ( id: number ) => page.locator( `#comment-${ id }` );

			await test.step( 'unapprove, then approve again', async () => {
				await page.goto( reviewsUrl );
				await rowFor( moderated.id ).hover();
				await rowFor( moderated.id )
					.getByRole( 'button', { name: 'Unapprove' } )
					.click();

				await page.goto( `${ reviewsUrl }&comment_status=moderated` );
				await expect( rowFor( moderated.id ) ).toBeVisible();

				await rowFor( moderated.id ).hover();
				await rowFor( moderated.id )
					.getByRole( 'button', { name: 'Approve' } )
					.click();

				await page.goto( `${ reviewsUrl }&comment_status=approved` );
				await expect( rowFor( moderated.id ) ).toBeVisible();
			} );

			await test.step( 'mark as spam', async () => {
				await page.goto( reviewsUrl );
				await rowFor( moderated.id ).hover();
				await rowFor( moderated.id )
					.getByRole( 'button', { name: 'Spam' } )
					.click();
				await expect( rowFor( moderated.id ) ).toBeHidden();

				await page.goto( `${ reviewsUrl }&comment_status=spam` );
				await expect( rowFor( moderated.id ) ).toBeVisible();
			} );

			await test.step( 'trash', async () => {
				await page.goto( reviewsUrl );
				await rowFor( trashed.id ).hover();
				await rowFor( trashed.id )
					.getByRole( 'button', { name: 'Trash' } )
					.click();
				await expect( rowFor( trashed.id ) ).toBeHidden();

				await page.goto( `${ reviewsUrl }&comment_status=trash` );
				await expect( rowFor( trashed.id ) ).toBeVisible();
			} );
		} );

		test( 'can quick edit a product review', async ( {
			page,
			reviews,
		} ) => {
			const review = reviews[ 0 ];

			await page.goto(
				`wp-admin/edit.php?post_type=product&page=product-reviews`
			);
			const reviewRow = page.locator( `#comment-${ review.id }` );
			await reviewRow.hover();
			await reviewRow
				.getByRole( 'button', { name: 'Quick Edit' } )
				.click();

			// Create new review, Quick Edit it and save
			const updatedQuickReview = `(quickly edited ${ Date.now() })`;
			await page
				.locator( '.wp-editor-area' )
				.first()
				.fill( updatedQuickReview );
			await page
				.getByRole( 'button', { name: 'Update Comment' } )
				.click();

			await expect(
				reviewRow.getByText( updatedQuickReview )
			).toBeVisible();
		} );

		test( 'can reply to a product review', async ( { page, reviews } ) => {
			const review = reviews[ 0 ];

			await page.goto(
				'wp-admin/edit.php?post_type=product&page=product-reviews'
			);

			// Handle notice if present
			await page.addLocatorHandler(
				page.getByRole( 'link', { name: 'Dismiss' } ),
				async () => {
					await page.getByRole( 'link', { name: 'Dismiss' } ).click();
				}
			);

			const reviewRow = page.locator( `#comment-${ review.id }` );
			await reviewRow.hover();
			await reviewRow.getByRole( 'button', { name: 'Reply' } ).click();
			const replyTextArea = page.locator( 'textarea#replycontent' );

			await expect( replyTextArea ).toBeVisible();

			const replyText = `Thank you for your feedback! (replied ${ Date.now() })`;
			await replyTextArea.fill( replyText );

			await page
				.getByRole( 'cell', { name: 'Reply to Comment' } )
				.getByRole( 'button', { name: 'Reply', exact: true } )
				.click();

			await expect( replyTextArea ).toBeHidden();

			const productLink = await reviewRow
				.locator( 'a.comments-view-item-link' )
				.getAttribute( 'href' );
			await page.goto( productLink );
			await page.getByRole( 'tab', { name: 'Reviews' } ).click();

			const replyReviews = page.locator(
				`div.comment_container:has-text("${ replyText }")`
			);
			await expect( replyReviews ).toBeVisible();
		} );

		test( 'can edit a product review', async ( { page, reviews } ) => {
			const review = reviews[ 0 ];

			await page.goto(
				`wp-admin/comment.php?action=editcomment&c=${ review.id }`
			);
			await expect( page.getByText( 'Edit Comment' ) ).toBeVisible();

			const updatedReview = `(edited ${ Date.now() })`;
			await page
				.locator( '.wp-editor-area' )
				.first()
				.fill( updatedReview );

			await page.click( '#rating' );
			const updatedRating = ( Math.random() * ( 5 - 1 ) + 1 ).toFixed(
				0
			);
			await page.selectOption( '#rating', {
				value: updatedRating.toString(),
			} );
			await page.getByRole( 'button', { name: 'Update' } ).click();
			await page.waitForURL( '**/edit-comments.php?**' );

			await page.goto(
				`wp-admin/edit.php?post_type=product&page=product-reviews`
			);

			const reviewRow = page.locator( `#comment-${ review.id }` );

			// WordPress 7.1 renders primary list-table cells as row headers.
			await expect(
				reviewRow.getByRole( 'cell', { name: updatedReview } ).or(
					reviewRow.getByRole( 'rowheader', {
						name: updatedReview,
					} )
				)
			).toBeVisible();
			await expect(
				reviewRow.getByLabel( `${ updatedRating } out of 5` )
			).toBeVisible();

			await reviewRow.locator( 'a.comments-view-item-link' ).click();
			await page.getByRole( 'tab', { name: 'Reviews' } ).click();
			await expect(
				page.locator( '.comment_container' ).first()
			).toContainText( updatedReview );
			await expect(
				page.getByLabel( `${ updatedRating } out of 5` )
			).toBeVisible();
		} );
	} );

	test.describe( 'Shopper adds reviews', () => {
		test.use( { storageState: CUSTOMER_STATE_PATH } );

		test( 'shopper can post a review', async ( { page, products } ) => {
			const product = products[ 0 ];
			const reviewText = faker.lorem.sentence();

			await page.goto( product.permalink );

			await page.getByRole( 'tab', { name: 'Reviews (0)' } ).click();
			await page.locator( '.star-4' ).click();
			await page.getByLabel( 'Your review' ).fill( reviewText );
			await page.getByRole( 'button', { name: 'Submit' } ).click();

			await expect(
				page.getByText( 'Your review is awaiting' )
			).toBeVisible();
			await expect( page.getByText( reviewText ) ).toBeVisible();
			await expect( page.getByLabel( 'Rated 4 out of 5' ) ).toBeVisible();
		} );
	} );
} );
