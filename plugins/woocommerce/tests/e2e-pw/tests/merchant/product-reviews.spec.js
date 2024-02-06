const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const timestamp = Date.now().toString();
const productName = `Review me ${ timestamp }`;
const productReview = `Nice one, Playwright! ${ timestamp }`;
const randomRating = ( Math.random() * ( 5 - 1 ) + 1 ).toFixed( 0 );
const reviewerName = 'John Doe';
const reviewerEmail = `john.doe.${ timestamp }@example.com`;
const updatedReview = `(edited ${ timestamp })`;

test.describe( 'Products > Reviews', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	let productId, api;

	test.beforeAll( async ( { baseURL } ) => {
		api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api
			.post( 'products', {
				name: productName,
				type: 'simple',
				regular_price: '9.99',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		await api.post( 'products/reviews', {
			product_id: productId,
			review: productReview,
			reviewer: reviewerName,
			reviewer_email: reviewerEmail,
			rating: randomRating,
		} );
	} );

	test.afterAll( async ( {} ) => {
		await api.delete( `products/${ productId }`, {
			force: true,
		} );
	} );

	test( 'can view product review', async ( { page } ) => {
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: productReview } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: productName } )
		).toBeVisible();
		await expect(
			page.getByLabel( `${ randomRating } out of 5` )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: reviewerEmail } )
		).toBeVisible();
	} );

	test( 'can edit product review', async ( { page } ) => {
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: productReview } )
		).toBeVisible();

		//Hover over the product review to have 'Edit' displayed
		await page.hover( '.comment-text' );

		// Select Quick Edit, edit the review and save
		await page.getByRole( 'button', { name: 'Quick Edit' } ).click();
		await page.locator( '.wp-editor-area' ).first().fill( updatedReview ); //updatedReview
		await page.getByRole( 'button', { name: 'Update Comment' } ).click();
		await page.waitForTimeout( 2000 );

		// Verify that the edited comment is there
		const commentTextElement = page.locator( '.comment-text' );
		await commentTextElement.waitFor( { state: 'visible' } );
		const updatedComment = await commentTextElement.innerText(); //updatedComment
		await expect( updatedComment ).toBe( updatedReview ); //updatedComment updatedReview
	} );

	test( 'can delete product review', async ( { page } ) => {
		await page.goto(
			`wp-admin/edit.php?post_type=product&page=product-reviews`
		);
		await expect(
			page.getByRole( 'cell', { name: updatedReview } )
		).toBeVisible();

		//Hover over the product review to have 'Edit' displayed
		await page.hover( '.comment-text' );

		// Select Trash action, check confirmation prompt and undo
		await page.getByRole( 'button', { name: 'Trash' } ).click();
		await page.waitForTimeout( 2000 );
		await expect(
			page.getByText( `Comment by ${ reviewerName } moved to the Trash` )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Undo' } ).click();

		// Verify that the review has been restored
		await expect(
			page.getByRole( 'cell', { name: updatedReview } )
		).toBeVisible();

		// Select Trash action and delete it permanently
		await page.getByRole( 'button', { name: 'Trash' } ).click();
		await page.waitForTimeout( 2000 );
		await expect(
			page.getByText( `Comment by ${ reviewerName } moved to the Trash` )
		).toBeVisible();
		await page.reload();
		await page.waitForSelector( 'tr.no-items' );

		// Assert that the message is present, indicating an empty comment list
		const noReviewsFound = await page.$( 'tr.no-items' );
		expect( noReviewsFound ).not.toBeNull();
	} );
} );
