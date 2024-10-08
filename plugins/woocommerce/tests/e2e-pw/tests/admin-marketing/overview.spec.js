const { test, expect } = require( '@playwright/test' );

test.describe( 'Marketing page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'A user can view the Marketing > Overview page without it crashing', async ( {
		page,
	} ) => {
		// Go to the Marketing page.
		await page.goto( 'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing' );

		// Users should see the "Learn about marketing a store" card.
		await expect(
			page.getByText( 'Learn about marketing a store' )
		).toBeVisible();
	} );

	test(
		'Marketing Overview page have relevant content',
		{ tag: '@skip-on-default-wpcom' },
		async ( { page } ) => {
			// Go to the Marketing page.
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing'
			);

			// Heading should be overview
			await expect(
				page.getByRole( 'heading', { name: 'Overview' } )
			).toBeVisible();

			// Sections present
			await expect(
				page.getByText( 'Channels', { exact: true } )
			).toBeVisible();
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
	);

	test(
		'Introduction can be dismissed',
		{ tag: '@skip-on-default-pressable' },
		async ( { page } ) => {
			// Go to the Marketing page.
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing'
			);

			// Dismiss the introduction (if it's visible)
			try {
				await page
					.locator(
						'.woocommerce-marketing-introduction-banner-illustration > .components-button'
					)
					.click( { timeout: 2000 } );
			} catch ( e ) {
				console.log( 'Info: introduction already hidden' );
			}

			// The introduction should be hidden.
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

	test( 'Learning section can be expanded', async ( { page } ) => {
		// Go to the Dashboard page (this adds time for posts to be created)
		await page.goto( 'wp-admin/index.php' );

		// Go to the Marketing page.
		await page.goto( 'wp-admin/admin.php?page=wc-admin&path=%2Fmarketing' );

		// Expand the learning section
		await page.getByLabel( 'Expand' ).waitFor();
		await page.getByLabel( 'Expand' ).click( { timeout: 2000 } );

		// The learning section should be expanded.
		await expect( page.getByText( 'Page 1 of 4' ) ).toBeVisible();

		// Can navigate to next page
		await page.getByLabel( 'Next page' ).click();
		await expect( page.getByText( 'Page 2 of 4' ) ).toBeVisible();

		// Collapse the learning section
		await page.getByLabel( 'Collapse' ).nth( 2 ).click( { timeout: 2000 } );

		// The learning section should be collapsed.
		await expect( page.getByText( 'Page 1 of 4' ) ).toBeHidden();
	} );
} );
