const { test, expect } = require( '@playwright/test' );

test.describe( 'Merchant can add shipping classes', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterEach( async ( { page } ) => {
		// no api endpoints for shipping classes, so use the UI to cleanup
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes'
		);

		await page.dispatchEvent(
			'.wc-shipping-class-delete >> nth=0',
			'click'
		);
		await page.dispatchEvent(
			'.wc-shipping-class-delete >> nth=0',
			'click'
		);
		await page.dispatchEvent( 'text=Save shipping classes', 'click' );
	} );

	test( 'can add shipping classes', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes'
		);

		const shippingClassSlug = {
			name: 'Small Items',
			slug: 'small-items',
			description: "Small items that don't cost much to ship.",
		};
		const shippingClassNoSlug = {
			name: 'Poster Pack',
			slug: '',
			description: '',
		};
		const shippingClasses = [ shippingClassSlug, shippingClassNoSlug ];

		// Add shipping classes
		for ( const { name, slug, description } of shippingClasses ) {
			await page.click( 'text=Add shipping class' );
			await page.fill(
				'.editing:last-child [data-attribute="name"]',
				name
			);
			await page.fill(
				'.editing:last-child [data-attribute="slug"]',
				slug
			);
			await page.fill(
				'.editing:last-child [data-attribute="description"]',
				description
			);
		}
		await page.click( 'text=Save shipping classes' );

		// Set the expected auto-generated slug
		shippingClassNoSlug.slug = 'poster-pack';

		// Verify that the specified shipping classes were saved
		for ( const { name, slug, description } of shippingClasses ) {
			await expect(
				page.locator( `text=${ name } Edit | Remove` )
			).toBeVisible();
			await expect( page.locator( `text=${ slug }` ) ).toBeVisible();
			// account for blank description
			if ( description !== '' ) {
				await expect(
					page.locator( `text=${ description }` )
				).toBeVisible();
			}
		}
	} );
} );
