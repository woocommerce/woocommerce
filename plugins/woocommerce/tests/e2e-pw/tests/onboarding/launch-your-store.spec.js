/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { expect, tags, test } from '../../fixtures/fixtures';
import { setOption } from '../../utils/options';
import { activateTheme, DEFAULT_THEME } from '../../utils/themes';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { WC_ADMIN_API_PATH } from '../../utils/api-client';

test.describe(
	'Launch Your Store - logged in',
	{ tag: [ tags.GUTENBERG, tags.SKIP_ON_WPCOM ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeEach( async ( { restApi } ) => {
			await restApi.post( `${ WC_ADMIN_API_PATH }/onboarding/profile`, {
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

			await page.goto( baseURL + 'shop/' );

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
				'wp-admin/admin.php?page=wc-settings&tab=site-visibility'
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

			await page.goto( 'wp-admin/admin.php?page=wc-admin' );

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

			await page.goto( 'wp-admin/admin.php?page=wc-admin' );

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

			await page.goto( 'wp-admin/admin.php?page=wc-admin' );

			await expect(
				page.getByRole( 'menuitem', {
					name: 'Live',
					exact: true,
				} )
			).toBeVisible();
		} );
	}
);

async function runComingSoonTests( themeContext = '' ) {
	const testSuffix = themeContext ? ` (${ themeContext })` : '';

	test( `Entire site coming soon mode${ testSuffix }`, async ( {
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

		await page.goto( './' );

		await page
			.locator( '.woocommerce-coming-soon-banner' )
			.waitFor( { state: 'visible' } );

		await expect(
			page.getByText(
				"Pardon our dust! We're working on something amazing — check back soon!"
			)
		).toBeVisible();
	} );

	test( `Store only coming soon mode${ testSuffix }`, async ( {
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
		await page.goto( 'shop/' );

		await expect(
			page.getByText( 'Great things are on the horizon' )
		).toBeVisible();
		await expect(
			page.getByText(
				'Something big is brewing! Our store is in the works and will be launching soon!'
			)
		).toBeVisible();
	} );
}

test.describe( 'Launch Your Store front end - logged out', () => {
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

	test.describe( 'Block Theme (Twenty Twenty Four)', async () => {
		test.beforeAll( async ( { baseURL } ) => {
			await activateTheme( baseURL, 'twentytwentyfour' );
		} );

		test.afterAll( async ( { baseURL } ) => {
			// Reset theme to the default.
			await activateTheme( baseURL, DEFAULT_THEME );
		} );

		await runComingSoonTests( 'Block Theme (Twenty Twenty Four)' );
	} );

	test.describe( 'Classic Theme (Storefront)', async () => {
		test.beforeAll( async ( { baseURL } ) => {
			await activateTheme( baseURL, 'storefront' );
		} );

		test.afterAll( async ( { baseURL } ) => {
			// Reset theme to the default.
			await activateTheme( baseURL, DEFAULT_THEME );
		} );

		await runComingSoonTests( 'Classic Theme (Storefront)' );
	} );
} );
