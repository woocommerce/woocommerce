const { test: baseTest, expect } = require( '../../fixtures' );

baseTest.describe( 'Product Reviews > Edit Product Review', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		testData: async ( { api }, use ) => {
			const timestamp = Date.now().toString();
			let product = {};
			let review = {};

			// Create the product
			await api
				.post( 'products', {
					name: `Review me ${ timestamp }`,
					type: 'simple',
					regular_price: '9.99',
				} )
				.then( ( response ) => {
					product = response.data;
				} );

			// Create the product review
			await api
				.post( 'products/reviews', {
					product_id: product.id,
					review: `Nice one, Playwright! ${ timestamp }`,
					reviewer: 'John Doe',
					reviewer_email: `john.doe.${ timestamp }@example.com`,
					rating: ( Math.random() * ( 5 - 1 ) + 1 ).toFixed( 0 ),
				} )
				.then( ( response ) => {
					review = response.data;
				} );

			await use( { product, review } );

			// Cleanup
			await api.delete( `products/reviews/${ review.id }`, {
				force: true,
			} );
			await api.delete( `products/${ product.id }`, {
				force: true,
			} );
		},
	} );

	test( 'can view product review', async ( { page, testData } ) => {
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: testData.review.review } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: testData.product.name } )
		).toBeVisible();
		await expect(
			page.getByLabel( `${ testData.review.rating } out of 5` )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: testData.review.reviewer_email } )
		).toBeVisible();
	} );

	test( 'can quick edit product review', async ( { page, testData } ) => {
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: testData.review.review } )
		).toBeVisible();

		//Hover over the product review to have 'Quick Edit' displayed
		await page.hover( `#comment-${ testData.review.id }` );
		await page
			.locator( `#comment-${ testData.review.id }` )
			.getByRole( 'button', { name: 'Quick Edit' } )
			.click();

		// Create new review, Quick Edit it and save
		const updatedQuickReview = `(quickly edited ${ Date.now() })`;
		await page
			.locator( '.wp-editor-area' )
			.first()
			.fill( updatedQuickReview );
		await page.getByRole( 'button', { name: 'Update Comment' } ).click();

		// Verify that the edited comment is there
		await expect( page.getByText( updatedQuickReview ) ).toBeVisible();
	} );

	test( 'can edit product review', async ( { page, testData } ) => {
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: testData.review.review } )
		).toBeVisible();

		//Go to the Edit page of the review
		await page.goto(
			`wp-admin/comment.php?action=editcomment&c=${ testData.review.id }`
		);
		await expect( page.getByText( 'Edit Review' ) ).toBeVisible();

		// Create new comment and edit the review with it
		const updatedReview = `(edited ${ Date.now() })`;
		await page.locator( '.wp-editor-area' ).first().fill( updatedReview );

		// Generate another random rating and edit the review with it
		await page.click( '#rating' );
		const updatedRating = ( Math.random() * ( 5 - 1 ) + 1 ).toFixed( 0 );
		await page.selectOption( '#rating', {
			value: updatedRating.toString(),
		} );
		await page.getByRole( 'button', { name: 'Update' } ).click();

		// Verify that the edited comment is in the product reviews list
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: updatedReview } )
		).toBeVisible();
		await expect(
			page.getByLabel( `${ updatedRating } out of 5` )
		).toBeVisible();

		// Verify that the edited comment is in the shop's product page
		await page.locator( 'a.comments-view-item-link' ).click();
		await page.click( '#tab-reviews' );
		await expect(
			page.locator( '.comment_container' ).first()
		).toContainText( updatedReview );
		await expect(
			page.getByLabel( `${ updatedRating } out of 5` )
		).toBeVisible();
	} );

	test( 'can delete product review', async ( { page, testData } ) => {
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: testData.review.review } )
		).toBeVisible();
		const reviewerName = testData.review.reviewer;

		//Hover over the product review to have 'Edit' displayed
		await page.hover( `#comment-${ testData.review.id }` );

		// Select Trash action, check confirmation prompt and undo
		await page
			.locator( `#comment-${ testData.review.id }` )
			.getByRole( 'button', { name: 'Trash' } )
			.click();
		await expect(
			page.getByText( `Comment by ${ reviewerName } moved to the Trash` )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Undo' } ).click();

		// Verify that the review has been restored
		await expect(
			page.getByRole( 'cell', { name: testData.review.review } )
		).toBeVisible();

		// Select Trash action and delete it permanently
		await page
			.locator( `#comment-${ testData.review.id }` )
			.getByRole( 'button', { name: 'Trash' } )
			.click();
		await expect(
			page.getByText( `Comment by ${ reviewerName } moved to the Trash` )
		).toBeVisible();

		// Verify that the review in the trash
		await page.click( 'a[href*="comment_status=trash"]' );
		await expect(
			page.getByRole( 'cell', { name: testData.review.review } )
		).toBeVisible();

		// Check that the review is in the trash via URL
		await page.goto(
			`wp-admin/comment.php?action=editcomment&c=${ testData.review.id }`
		);
		await expect(
			page.getByText(
				`This comment is in the Trash. Please move it out of the Trash if you want to edit it.`
			)
		).toBeVisible();
	} );
} );
