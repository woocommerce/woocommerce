const { test, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe(
	'Launch Your Store - logged in',
	{ tag: [ '@gutenberg', '@services' ] },
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeEach( async ( { baseURL } ) => {
			await new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc-admin',
			} ).post( 'onboarding/profile', {
				skipped: true,
			} );
		} );

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

		test( 'Entire site coming soon mode frontend', async ( {
			page,
			baseURL,
		} ) => {
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

		test( 'Store only coming soon mode frontend', async ( {
			page,
			baseURL,
		} ) => {
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

		test( 'Site visibility settings', async ( { page, baseURL } ) => {
			try {
				await setOption(
					request,
					baseURL,
					'woocommerce_coming_soon',
					'no'
				);

				await setOption(
					request,
					baseURL,
					'woocommerce_store_pages_only',
					'no'
				);

				await setOption(
					request,
					baseURL,
					'woocommerce_private_link',
					'no'
				);
			} catch ( error ) {
				console.log( error );
			}

			await page.goto(
				'/wp-admin/admin.php?page=wc-settings&tab=site-visibility'
			);

			// The Coming soon radio should not be checked.
			await expect(
				page.getByRole( 'radio', { name: 'Coming soon', exact: true } )
			).not.toBeChecked();

			// The store only checkbox should not be on the page.
			await expect(
				page.getByRole( 'checkbox', {
					name: 'Apply to store pages only',
				} )
			).toHaveCount( 0 );

			// The private link should not be on the page.
			await expect(
				page.getByRole( 'checkbox', {
					name: 'Share your site with a private link',
				} )
			).toHaveCount( 0 );

			// The Live radio should be checked.
			await expect(
				page.getByRole( 'radio', { name: 'Live', exact: true } )
			).toBeChecked();

			// Check the Coming soon radio button.
			await page
				.getByRole( 'radio', { name: 'Coming soon', exact: true } )
				.check();

			// The store only checkbox should be visible.
			await expect(
				page.getByRole( 'checkbox', {
					name: 'Apply to store pages only',
				} )
			).toBeVisible();

			// The store only checkbox should not be checked.
			await expect(
				page.getByRole( 'checkbox', {
					name: 'Apply to store pages only',
				} )
			).not.toBeChecked();

			// The private link should not be checked.
			await expect(
				page.getByRole( 'checkbox', {
					name: 'Share your site with a private link',
				} )
			).not.toBeChecked();

			// Check the private link checkbox.
			await page
				.getByRole( 'checkbox', {
					name: 'Share your site with a private link',
				} )
				.check();

			// The private link input should be visible.
			await expect(
				page.getByRole( 'button', { name: 'Copy link' } )
			).toBeVisible();
		} );

		test( 'Homescreen badge coming soon store only', async ( {
			page,
			baseURL,
		} ) => {
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

			await page.goto( '/wp-admin/admin.php?page=wc-admin' );

			await expect(
				page.getByRole( 'menuitem', {
					name: 'Store coming soon',
					exact: true,
				} )
			).toBeVisible();
		} );

		test( 'Homescreen badge coming soon entire store', async ( {
			page,
			baseURL,
		} ) => {
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

			await page.goto( '/wp-admin/admin.php?page=wc-admin' );

			await expect(
				page.getByRole( 'menuitem', {
					name: 'Coming soon',
					exact: true,
				} )
			).toBeVisible();
		} );

		test( 'Homescreen badge live', async ( { page, baseURL } ) => {
			try {
				await setOption(
					request,
					baseURL,
					'woocommerce_coming_soon',
					'no'
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

			await page.goto( '/wp-admin/admin.php?page=wc-admin' );

			await expect(
				page.getByRole( 'menuitem', {
					name: 'Live',
					exact: true,
				} )
			).toBeVisible();
		} );
	}
);
