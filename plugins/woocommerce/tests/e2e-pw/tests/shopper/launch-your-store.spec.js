const { test, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );

test.describe( 'LYS', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { baseURL } ) => {
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
	} );

	test( 'Options set correctly', async ( { page, context } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=site-visibility'
		);

		await page.goto( '/wp-admin/admin.php?page=wc-admin' );

		await expect(
			page.getByRole( 'button', {
				name: 'Site coming soon',
				exact: true,
			} )
		).toBeVisible();
	} );
} );
