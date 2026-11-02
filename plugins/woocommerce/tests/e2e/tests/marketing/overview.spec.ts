/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'Marketing page', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test( 'Marketing Overview page has relevant content', async ( {
		page,
	} ) => {
		// See if this is a WPCOM site.
		const is_wpcom_site =
			process.env.IS_WPCOM &&
			process.env.IS_WPCOM.toLowerCase() === 'true';

		// Go to the Marketing page.
		await page.goto( 'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing' );

		// Heading should be overview
		await expect(
			page.getByRole( 'heading', { name: 'Overview' } )
		).toBeVisible();

		// Sections present
		await expect(
			page.getByText( 'Channels', { exact: true } )
		).toBeVisible();

		// Check the 'Discover more marketing tools' and 'Learn about marketing a store' cards only on non-WPCOM sites.
		if ( ! is_wpcom_site ) {
			await expect(
				page.getByText( 'Discover more marketing tools' )
			).toBeVisible();
			await expect(
				page.getByRole( 'tab', { name: 'Email' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'tab', { name: 'Automations' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'tab', { name: 'Conversion' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'tab', { name: 'CRM', exact: true } )
			).toBeVisible();
			await expect(
				page.getByText( 'Learn about marketing a store' )
			).toBeVisible();
		}
	} );

	test(
		'Dismissed introduction stays hidden after reload',
		{ tag: [ tags.SKIP_ON_PRESSABLE, tags.NOT_E2E, tags.NON_CRITICAL ] },
		async ( { page } ) => {
			// Auto-dismiss the introduction the first time it appears. The
			// banner is rendered by React after navigation and may already be
			// gone if dismissed in a previous run, so register a handler that
			// clicks it whenever it shows up. `times: 1` limits this to the
			// initial dismissal: after the reload below the handler is spent,
			// so a banner that wrongly reappears will fail the assertion
			// instead of being silently re-dismissed.
			await page.addLocatorHandler(
				page.locator(
					'.woocommerce-marketing-introduction-banner-close-button'
				),
				async ( locator ) => {
					await locator.click();
				},
				{ times: 1 }
			);

			// Go to the Marketing page.
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing'
			);

			// The introduction should be hidden (dismissed by the handler).
			await expect(
				page.getByText(
					'Reach new customers and increase sales without leaving WooCommerce'
				)
			).toBeHidden();

			// Refresh the page to make sure the state is saved.
			await page.reload();

			// The introduction should still be hidden.
			await expect(
				page.getByText(
					'Reach new customers and increase sales without leaving WooCommerce'
				)
			).toBeHidden();
		}
	);
} );
