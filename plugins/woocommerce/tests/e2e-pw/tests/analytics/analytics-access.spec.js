const { test, expect } = require( '@playwright/test' );
const { tags } = require( '../../fixtures/fixtures' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );

test.describe( 'WooCommerce Home', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test(
		'Can access Analytics Reports from Stats Overview',
		{ tag: [ tags.NOT_E2E ] },
		async ( { page } ) => {
			await test.step( 'Navigate to the WooCommerce Home page', async () => {
				await page.goto( 'wp-admin/admin.php?page=wc-admin' );
				await expect(
					page.getByText( 'Stats overview' )
				).toBeVisible();
				await expect(
					page.getByRole( 'menuitem', {
						name: 'Total sales $0.00 No change',
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'link', { name: 'View detailed stats' } )
				).toBeVisible();
			} );

			await test.step( 'Navigate to Revenue Report', async () => {
				await page
					.getByRole( 'menuitem', {
						name: 'Total sales $0.00 No change',
					} )
					.click();
				await expect(
					page.getByRole( 'heading', { name: 'Revenue' } ).first()
				).toBeVisible();
				await expect( page.url() ).toContain(
					'admin.php?page=wc-admin&chart=total_sales&period=today&compare=previous_period&path=%2Fanalytics%2Frevenue'
				);
			} );

			await test.step( 'Navigate to Orders Report', async () => {
				await page.goto( 'wp-admin/admin.php?page=wc-admin' );
				await expect(
					page.getByRole( 'menuitem', {
						name: 'Orders 0 No change from',
					} )
				).toBeVisible();
				await page
					.getByRole( 'menuitem', {
						name: 'Orders 0 No change from',
					} )
					.click();
				await expect(
					page.getByRole( 'heading', { name: 'Orders' } ).first()
				).toBeVisible();
				await expect( page.url() ).toContain(
					'admin.php?page=wc-admin&chart=orders_count&period=today&compare=previous_period&path=%2Fanalytics%2Forders'
				);
			} );

			await test.step( 'Navigate to Analytics Overview', async () => {
				await page.goto( 'wp-admin/admin.php?page=wc-admin' );
				await expect(
					page.getByRole( 'link', { name: 'View detailed stats' } )
				).toBeVisible();
				await page
					.getByRole( 'link', { name: 'View detailed stats' } )
					.click();
				await expect(
					page.getByRole( 'heading', { name: 'Overview' } ).first()
				).toBeVisible();
				await expect( page.url() ).toContain(
					'admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
				);
			} );
		}
	);
} );
