const { test: baseTest, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

baseTest.describe( 'Product Reviews > Edit Product Review', () => {
	baseTest.use( { storageState: process.env.ADMINSTATE } );

	const test = baseTest.extend( {
		api: async ( { baseURL }, use ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			await use( api );
		},
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

	test( 'can edit product review', async ( { page, testData } ) => {
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: testData.review.review } )
		).toBeVisible();

		//Hover over the product review to have 'Edit' displayed
		await page.hover( `#comment-${ testData.review.id }` );

		// Create new review, Quick Edit it and save
		const updatedReview = `(edited ${ Date.now() })`;
		await page
			.locator( `#comment-${ testData.review.id }` )
			.getByRole( 'button', { name: 'Quick Edit' } )
			.click();
		await page.locator( '.wp-editor-area' ).first().fill( updatedReview );
		await page.getByRole( 'button', { name: 'Update Comment' } ).click();

		// Verify that the edited comment is there
		await expect( page.getByText( updatedReview ) ).toBeVisible();
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
		await page.hover( '.comment-text' );

		// Select Trash action, check confirmation prompt and undo
		await page.getByRole( 'button', { name: 'Trash' } ).click();
		await expect(
			page.getByText(
				`Comment by ${ reviewerName } moved to the Trash`
			)
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Undo' } ).click();

		// Verify that the review has been restored
		await expect(
			page.getByRole( 'cell', { name: testData.review.review } )
		).toBeVisible();

		// Select Trash action and delete it permanently
		await page.getByRole( 'button', { name: 'Trash' } ).click();
		await expect(
			page.getByText(
				`Comment by ${ reviewerName } moved to the Trash`
			)
		).toBeVisible();
		await page.reload();
		await page.waitForSelector( 'tr.no-items' );

		// Assert that the message is present, indicating an empty comment list
		const noReviewsFound = await page.$( 'tr.no-items' );
		expect( noReviewsFound ).not.toBeNull();
	} );
} );
