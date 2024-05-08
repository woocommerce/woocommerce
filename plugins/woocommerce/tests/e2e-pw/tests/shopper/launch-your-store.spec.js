const { test, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );

test.describe( 'LYS', () => {
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

	test( 'Options set correctly', async ( { page, baseURL } ) => {
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
				'Pardon our dust! We’re working on something amazing — check back soon!'
			)
		).toBeVisible();
	} );
} );
