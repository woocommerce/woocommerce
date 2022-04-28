const { test, expect } = require( '@playwright/test' );

test.describe( 'Analytics pages', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test( 'a user should see 3 sections by default - Performance, Charts, and Leaderboards', async ( {
		page,
	} ) => {
		// Create an array of the sections we're expecting to find.
		const arrExpectedSections = [ 'Charts', 'Leaderboards', 'Performance' ];
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
		// Grab all of the section headings
		const sections = await page.$$(
			'h2.woocommerce-section-header__title'
		);
		// Create an array with the section headings
		const arrFoundSections = new Array();
		for await ( const section of sections ) {
			arrFoundSections.push( await section.innerText() );
		}
		await expect( arrFoundSections.sort() ).toEqual(
			arrExpectedSections.sort()
		);
	} );

	test( 'should allow a user to remove a section', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
		// clicks the first button to the right of the Performance heading
		await page
			.locator( 'button:right-of(:text("Performance")) >> nth=0' )
			.click();
		await page.locator( 'text=Remove section' ).click();
		// Grab all of the section headings
		const sections = await page.$$(
			'h2.woocommerce-section-header__title'
		);
		await expect( sections.length ).toEqual( 2 );

		// clean up
		await page.locator( '//button[@title="Add more sections"]' ).click();
		await page
			.locator( '//button[@title="Add Performance section"]' )
			.click();
		await page.waitForLoadState( 'networkidle' );
	} );

	test( 'should allow a user to add a section back in', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
		// button only shows when not all sections visible, so remove a section
		await page
			.locator( 'button:right-of(:text("Performance")) >> nth=0' )
			.click();
		await page.locator( 'text=Remove section' ).click();

		// add section
		await page.locator( '//button[@title="Add more sections"]' ).click();
		await page
			.locator( '//button[@title="Add Performance section"]' )
			.click();
		await expect(
			page.locator( 'h2.woocommerce-section-header__title >> nth=2' )
		).toContainText( 'Performance' );
	} );

	test.describe( 'moving sections', () => {
		test.use( { storageState: 'e2e/storage/adminState.json' } );

		test( 'should not display move up for the top, or move down for the bottom section', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
			);
			// check the top section
			await page
				.locator(
					'button.components-button.woocommerce-ellipsis-menu__toggle >> nth=0'
				)
				.click();
			await expect( page.locator( 'text=Move up' ) ).not.toBeVisible();
			await expect( page.locator( 'text=Move down' ) ).toBeVisible();

			// check the bottom section
			await await page
				.locator(
					'button.components-button.woocommerce-ellipsis-menu__toggle >> nth=2'
				)
				.click();
			await expect( page.locator( 'text=Move down' ) ).not.toBeVisible();
			await expect( page.locator( 'text=Move up' ) ).toBeVisible();
		} );

		test( 'should allow a user to move a section down', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
			);
			const firstSection = await page
				.locator( 'h2.woocommerce-section-header__title >> nth=0' )
				.innerText();
			const secondSection = await page
				.locator( 'h2.woocommerce-section-header__title >> nth=1' )
				.innerText();

			await page
				.locator(
					'button.components-button.woocommerce-ellipsis-menu__toggle >> nth=0'
				)
				.click();
			await page.locator( 'text=Move down' ).click();

			// second section becomes first section, first becomes second
			await expect(
				page.locator( 'h2.woocommerce-section-header__title >> nth=0' )
			).toHaveText( secondSection );
			await expect(
				page.locator( 'h2.woocommerce-section-header__title >> nth=1' )
			).toHaveText( firstSection );
		} );

		test( 'should allow a user to move a section up', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
			);
			const firstSection = await page
				.locator( 'h2.woocommerce-section-header__title >> nth=0' )
				.innerText();
			const secondSection = await page
				.locator( 'h2.woocommerce-section-header__title >> nth=1' )
				.innerText();

			await page
				.locator(
					'button.components-button.woocommerce-ellipsis-menu__toggle >> nth=1'
				)
				.click();
			await page.locator( 'text=Move up' ).click();

			// second section becomes first section, first becomes second
			await expect(
				page.locator( 'h2.woocommerce-section-header__title >> nth=0' )
			).toHaveText( secondSection );
			await expect(
				page.locator( 'h2.woocommerce-section-header__title >> nth=1' )
			).toHaveText( firstSection );
		} );
	} );
} );
