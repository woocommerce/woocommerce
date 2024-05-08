const { test, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );

test.describe( 'Launch Your Store front end - logged in', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL } ) => {
		try {
			await setOption(
				request,
				baseURL,
				'woocommerce_coming_soon',
				'no'
			);
		} catch ( error ) {
			console.log( error );
		}
	} );

	test( 'Entire site coming soon mode', async ( { page, baseURL } ) => {
		try {
			await setOption(
				request,
				baseURL,
				'woocommerce_coming_soon',
				'yes'
			);

			await setOption(
				request,
				baseURL,
				'woocommerce_store_pages_only',
				'no'
			);
		} catch ( error ) {
			console.log( error );
		}

		await page.goto( baseURL );

		await expect(
			page.getByText(
				'This page is in "Coming soon" mode and is only visible to you and those who have permission. To make it public to everyone, change visibility settings'
			)
		).toBeVisible();
	} );

	test( 'Store only coming soon mode', async ( { page, baseURL } ) => {
		try {
			await setOption(
				request,
				baseURL,
				'woocommerce_coming_soon',
				'yes'
			);

			await setOption(
				request,
				baseURL,
				'woocommerce_store_pages_only',
				'yes'
			);
		} catch ( error ) {
			console.log( error );
		}

		await page.goto( baseURL + '/shop/' );

		await expect(
			page.getByText(
				'This page is in "Coming soon" mode and is only visible to you and those who have permission. To make it public to everyone, change visibility settings'
			)
		).toBeVisible();
	} );
} );
