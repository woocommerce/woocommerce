const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const timestamp = Date.now().toString();
const productName = `Review me ${ timestamp }`;
const productReview = `Nice one, Playwright! ${ timestamp }`;
const randomRating = ( Math.random() * ( 5 - 1 ) + 1 ).toFixed( 0 );
const reviewerEmail = `john.doe.${ timestamp }@example.com`;

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
			reviewer: 'John Doe',
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
} );
