const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	reviews: async ( { api }, use ) => {
		const timestamp = Date.now().toString();
		const products = [];
		const reviews = [];

		// Create the products
		for ( let i = 0; i < 2; i++ ) {
			await api
				.post( 'products', {
					name: `Product ${ i } ${ timestamp }`,
					type: 'simple',
					regular_price: '9.99',
				} )
				.then( ( response ) => {
					products.push( response.data );
				} );
		}

		// Create the product reviews
		for ( const product of products ) {
			await api
				.post( 'products/reviews', {
					product_id: product.id,
					review: `Nice product ${ product.name }, at ${ timestamp }`,
					reviewer: 'John Doe',
					reviewer_email: `john.doe.${ timestamp }@example.com`,
					rating: ( Math.random() * ( 5 - 1 ) + 1 ).toFixed( 0 ),
				} )
				.then( ( response ) => {
					reviews.push( response.data );
				} );
		}

		await use( reviews );

		// Cleanup
		await api.delete( `products/reviews/batch`, {
			delete: reviews.map( ( review ) => review.id ),
		} );
		await api.post( `products/batch`, {
			delete: products.map( ( product ) => product.id ),
		} );
	},
} );

test.describe(
	'Product Reviews > Edit Product Review',
	{ tag: [ '@gutenberg', '@services' ] },
	() => {
		test( 'can view products reviews list', async ( { page, reviews } ) => {
			await page.goto(
				`wp-admin/edit.php?post_type=product&page=product-reviews`
			);

			for ( const review of reviews ) {
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
			}
		} );

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
				.click();
			await page.getByRole( 'button', { name: 'Filter' } ).click();

			// Flakiness warning: if the filtering is too slow, some other reviews might still be displayed
			// We need to find something to assess filtering is ready
			const rows = await page.locator( '#the-comment-list tr' ).all();

			for ( const reviewRow of rows ) {
				await expect(
					reviewRow
						.locator( '[data-colname="Product"]' )
						.getByRole( 'link' )
						.first()
				).toContainText( review.product_name );
			}
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

			// Verify that the edited comment is there
			await expect(
				reviewRow.getByText( updatedQuickReview )
			).toBeVisible();
		} );

		test( 'can edit a product review', async ( { page, reviews } ) => {
			const review = reviews[ 0 ];

			// Go to the Edit page of the review
			await page.goto(
				`wp-admin/comment.php?action=editcomment&c=${ review.id }`
			);
			await expect( page.getByText( 'Edit Comment' ) ).toBeVisible();

			// Create new comment and edit the review with it
			const updatedReview = `(edited ${ Date.now() })`;
			await page
				.locator( '.wp-editor-area' )
				.first()
				.fill( updatedReview );

			// Generate another random rating and edit the review with it
			await page.click( '#rating' );
			const updatedRating = ( Math.random() * ( 5 - 1 ) + 1 ).toFixed(
				0
			);
			await page.selectOption( '#rating', {
				value: updatedRating.toString(),
			} );
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify that the edited comment is in the product reviews list
			await page.goto(
				`wp-admin/edit.php?post_type=product&page=product-reviews`
			);

			const reviewRow = page.locator( `#comment-${ review.id }` );

			await expect(
				reviewRow.getByRole( 'cell', { name: updatedReview } )
			).toBeVisible();
			await expect(
				reviewRow.getByLabel( `${ updatedRating } out of 5` )
			).toBeVisible();

			// Verify that the edited comment is in the shop's product page
			await reviewRow.locator( 'a.comments-view-item-link' ).click();
			await page.click( '#tab-reviews' );
			await expect(
				page.locator( '.comment_container' ).first()
			).toContainText( updatedReview );
			await expect(
				page.getByLabel( `${ updatedRating } out of 5` )
			).toBeVisible();
		} );

		test( 'can approve a product review', async ( { page, reviews } ) => {
			const review = reviews[ 0 ]; // Select the first review for approval

			// Go to the Product Reviews page
			await page.goto(
				`wp-admin/edit.php?post_type=product&page=product-reviews`
			);

			// Locate the review to be approved
			const reviewRow = page.locator( `#comment-${ review.id }` );

			// Ensure the review is not already approved
			const approveButton = reviewRow.getByRole( 'button', {
				name: 'Approve',
			} );
			const isApproveButtonVisible = await approveButton.isVisible();

			if ( isApproveButtonVisible ) {
				// Scroll to the review row and hover to reveal action buttons
				await reviewRow.scrollIntoViewIfNeeded();
				await reviewRow.hover();

				// Click the "Approve" button and wait for the state to become 'networkidle'
				await approveButton.click();
				await page.waitForLoadState( 'networkidle' );

				// Verify the review is approved by checking the presence of "Unapprove" button
				const unapproveButton = reviewRow.getByRole( 'button', {
					name: 'Unapprove',
				} );
				await expect( unapproveButton ).toBeVisible;
			}
		} );

		test( 'can mark a product review as spam', async ( {
			page,
			reviews,
		} ) => {
			const review = reviews[ 0 ]; // Select the first review to mark as spam

			// Go to the Product Reviews page
			await page.goto(
				`wp-admin/edit.php?post_type=product&page=product-reviews`
			);

			// Locate the review to be marked as spam
			const reviewRow = page.locator( `#comment-${ review.id }` );
			await reviewRow.hover();

			// Select Spam action and verify the review is marked as spam
			await reviewRow.getByRole( 'button', { name: 'Spam' } ).click();

			// Navigate to the "Spam" tab to verify the review is marked as spam
			await page.click( 'a[href*="comment_status=spam"]' );

			// Check if the spammed review row is located and verify the review is
			// marked as spam by confirming "Not Spam" button is visible
			const spammedReviewRow = page.locator( `#comment-${ review.id }` );
			await spammedReviewRow.isVisible();
			await reviewRow.hover();
			await spammedReviewRow.getByRole( 'button', { name: 'Not Spam' } )
				.toBeVisible;
		} );

		test('can reply to a product review', async ({ page, reviews }) => {
			const review = reviews[0]; // Select the first review to reply to
		
			// Go to the Product Reviews page
			await page.goto('wp-admin/edit.php?post_type=product&page=product-reviews');
		
			// Locate the review to be replied to
			const reviewRow = page.locator(`#comment-${review.id}`);
			await reviewRow.hover();
		
			// Click the Reply button within the review row
			await reviewRow.getByRole('button', { name: 'Reply' }).click();
		
			// Wait for the reply textarea to be visible
			const replyTextArea = page.locator('textarea#replycontent');
			await expect(replyTextArea).toBeVisible();
		
			// Fill in the reply and submit it
			const replyText = 'Thank you for your feedback!';
			await replyTextArea.fill(replyText);
		
			// Ensure the "Submit Reply" button within the reply area is visible and clickable
			const submitReplyButton = page.locator('button.save.button.button-primary');
			await expect(submitReplyButton).toBeVisible();
			await submitReplyButton.click();
		
			// Verify that the reply is visible in the product reviews list
			const replyRow = page.locator(`tr.comment.byuser:has(div.comment-text:has-text("${replyText}"))`);
			await expect(replyRow).toContainText(replyText);
		
			// Get the product link from the review row
			await reviewRow.locator('a.comments-view-item-link').click();
			await page.click('#tab-reviews');
		
			// Verify that the reply is visible in the shop reviews section
			const reviewContainer = page.locator(`div.comment_container:has-text("${replyText}")`);
    		await expect(reviewContainer).toContainText(replyText);
		});		
		
		test( 'can delete a product review', async ( { page, reviews } ) => {
			const review = reviews[ 0 ];

			await page.goto(
				`wp-admin/edit.php?post_type=product&page=product-reviews`
			);

			const reviewRow = page.locator( `#comment-${ review.id }` );
			await reviewRow.hover();

			// Select Trash action, check confirmation prompt and undo
			await reviewRow.getByRole( 'button', { name: 'Trash' } ).click();
			await expect(
				page.getByText(
					`Comment by ${ review.reviewer } moved to the Trash`
				)
			).toBeVisible();
			await page.getByRole( 'button', { name: 'Undo' } ).click();

			// Verify that the review has been restored
			await expect(
				reviewRow.getByRole( 'cell', { name: review.review } )
			).toBeVisible();

			// Select Trash action and delete it permanently
			await reviewRow.getByRole( 'button', { name: 'Trash' } ).click();
			await expect(
				page.getByText(
					`Comment by ${ review.reviewer } moved to the Trash`
				)
			).toBeVisible();

			// Verify that the review in the trash
			await page.click( 'a[href*="comment_status=trash"]' );
			await expect(
				reviewRow.getByRole( 'cell', { name: review.review } )
			).toBeVisible();

			// Check that the review is in the trash via URL
			await page.goto(
				`wp-admin/comment.php?action=editcomment&c=${ review.id }`
			);
			await expect(
				page.getByText(
					`This comment is in the Trash. Please move it out of the Trash if you want to edit it.`
				)
			).toBeVisible();
		} );
	}
);
