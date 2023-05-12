const { test, expect } = require( '@playwright/test' );

test.describe( 'Analytics pages', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterEach( async ( { page } ) => {
		// do some cleanup after each test to make sure things are where they should be
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);

		// Grab all of the section headings
		const sections = await page.$$(
			'h2.woocommerce-section-header__title'
		);

		if ( sections.length < 3 ) {
			// performance section is hidden
			//await page.click( '//button[@title="Add more sections"]' );//translate
			await page.click( '//button[@title="Añadir más secciones"]' );//translate
			//await page.click( '//button[@title="Add Performance section"]' );//translate
			await page.click( '//button[@title="Añadir sección Rendimiento"]' );//translate
			// await page.waitForSelector( 'h2:has-text("Performance")', {
			// 	state: 'visible',
			// } );//translate
			await page.waitForSelector( 'h2:has-text("Rendimiento")', {
				state: 'visible',
			} );//translate
			await page.waitForLoadState( 'networkidle' );
		}
		const lastSection = await page.textContent(
			'h2.woocommerce-section-header__title >> nth=2'
		);
		//if ( lastSection === 'Performance' ) {//translate
		if ( lastSection === 'Rendimiento' ) {//translate
			// sections are in the wrong order
			// await page.click(
			// 	'//button[@title="Choose which analytics to display and the section name"]'
			// );//translate
			await page.click(
				'//button[@title="Elige qué análisis mostrar y el nombre de la sección"]'
			);
			
			//await page.click( 'text=Move up' );//translate
			await page.click( 'text=Subir' );//translate
			
			// await page.click(
			// 	'//button[@title="Choose which analytics to display and the section name"]'
			// );//translate
			await page.click(
				'//button[@title="Elige qué análisis mostrar y el nombre de la sección"]'
			);
			//await page.click( 'text=Move up' );//translate
			await page.click( 'text=Subir' );//translate

			// wait for the changes to be saved
			await page.waitForResponse(
				( response ) =>
					response.url().includes( '/users/' ) &&
					response.status() === 200
			);
		}
	} );

	test( 'a user should see 3 sections by default - Performance, Charts, and Leaderboards', async ( {
		page,
	} ) => {
		// Create an array of the sections we're expecting to find.
		//const arrExpectedSections = [ 'Charts', 'Leaderboards', 'Performance' ];//translate
		const arrExpectedSections = [ 'Gráficos', 'Favoritos', 'Rendimiento' ];//translate
		
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
//await expect(page).toHaveScreenshot();

		for ( const expectedSection of arrExpectedSections ) {
			await test.step(
				`Assert that the "${ expectedSection }" section is visible`,
				async () => {
					await expect(
						page.locator( 'h2.woocommerce-section-header__title', {
							hasText: expectedSection,
						} )
					).toBeVisible();
				}
			);
		}
	} );

	test.describe( 'moving sections', () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test( 'should not display move up for the top, or move down for the bottom section', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
			);
//await expect(page).toHaveScreenshot();
			// check the top section
			await page
				.locator( 'button.woocommerce-ellipsis-menu__toggle' )
				.first()
				.click();
			//await expect( page.locator( 'text=Move up' ) ).not.toBeVisible();//translate
			await expect( page.locator( 'text=Subir' ) ).not.toBeVisible();//translate
			
			//await expect( page.locator( 'text=Move down' ) ).toBeVisible();//translate
			await expect( page.locator( 'text=Mover abajo' ) ).toBeVisible();//translate
			await page.keyboard.press( 'Escape' );

			// check the bottom section
			await page
				.locator( 'button.woocommerce-ellipsis-menu__toggle' )
				.last()
				.click();
			//await expect( page.locator( 'text=Move down' ) ).not.toBeVisible();//translate
			await expect( page.locator( 'text=Mover abajo' ) ).not.toBeVisible();//translate

			//await expect( page.locator( 'text=Move up' ) ).toBeVisible();//translate
			await expect( page.locator( 'text=Subir' ) ).toBeVisible();//translate

			await page.keyboard.press( 'Escape' );
		} );

		test( 'should allow a user to move a section down', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
			);
//await expect(page).toHaveScreenshot();
			const firstSection = await page
				.locator( 'h2.woocommerce-section-header__title >> nth=0' )
				.innerText();
			const secondSection = await page
				.locator( 'h2.woocommerce-section-header__title >> nth=1' )
				.innerText();

			await page.click(
				'button.components-button.woocommerce-ellipsis-menu__toggle >> nth=0'
			);
			//await page.click( 'text=Move down' );//translate
			await page.click( 'text=Mover abajo' );//translate
			

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
//await expect(page).toHaveScreenshot();
			const firstSection = await page
				.locator( 'h2.woocommerce-section-header__title >> nth=0' )
				.innerText();
			const secondSection = await page
				.locator( 'h2.woocommerce-section-header__title >> nth=1' )
				.innerText();

			await page.click(
				'button.components-button.woocommerce-ellipsis-menu__toggle >> nth=1'
			);
			//await page.click( 'text=Move up' );//translate
			await page.click( 'text=Subir' );//translate
			

			// second section becomes first section, first becomes second
			await expect(
				page.locator( 'h2.woocommerce-section-header__title >> nth=0' )
			).toHaveText( secondSection );
			await expect(
				page.locator( 'h2.woocommerce-section-header__title >> nth=1' )
			).toHaveText( firstSection );
		} );
	} );

	test( 'should allow a user to remove a section', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
//await expect(page).toHaveScreenshot();
		// clicks the first button to the right of the Performance heading
		//await page.click( 'button:right-of(:text("Performance")) >> nth=0' );//translate
		await page.click( 'button:right-of(:text("Rendimiento")) >> nth=0' );//translate
	
		//await page.click( 'text=Remove section' );//translate
		await page.click( 'text=Quitar sección' );//translate
		
		// Grab all of the section headings
		await page.waitForLoadState( 'networkidle' );
		const sections = await page.$$(
			'h2.woocommerce-section-header__title'
		);
		await expect( sections.length ).toEqual( 2 );
	} );

	test( 'should allow a user to add a section back in', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
//await expect(page).toHaveScreenshot();
		// button only shows when not all sections visible, so remove a section
		//await page.click( 'button:right-of(:text("Performance")) >> nth=0' );//translate
		await page.click( 'button:right-of(:text("Rendimiento")) >> nth=0' );//translate
		//await page.click( 'text=Remove section' );//translate
		await page.click( 'text=Quitar sección' );//translate

		// add section
		//await page.click( '//button[@title="Add more sections"]' );//translate
		await page.click( '//button[@title="Añadir más secciones"]' );//translate

		//await page.click( '//button[@title="Add Performance section"]' );//translate
		await page.click( '//button[@title="Añadir sección Rendimiento"]' );//translate
		// await expect(
		// 	page.locator( 'h2.woocommerce-section-header__title >> nth=2' )
		// ).toContainText( 'Performance' );//translate
		await expect(
			page.locator( 'h2.woocommerce-section-header__title >> nth=2' )
			).toContainText( 'Rendimiento' );//translate
	} );
} );
