const { test, expect } = require( '@playwright/test' );

test.describe( 'Execute plugin check using plugin-check plugin', () => {
	test.use( { storageState: process.env.ADMINSTATE } );
	test( 'Can execute general checks', async ( { page } ) => {
		await page.goto( 'wp-admin/tools.php?page=plugin-check' );
		await page
			.getByLabel( 'Check the Plugin' )
			.selectOption( { label: 'WooCommerce' } );
		await page.getByRole( 'checkbox', { name: 'General' } ).check();
		await page.getByRole( 'checkbox', { name: 'Plugin Repo' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Security' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Performance' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Accessibility' } ).uncheck();
		await page.getByRole( 'button', { name: 'Check it!' } ).click();

		await expect(
			page.getByRole( 'heading', { name: 'Plugin Check' } )
		).toBeVisible();

		await expect( page.getByText( 'Checks complete.' ) ).toBeVisible( {
			timeout: 120000,
		} );

		await page.screenshot( {
			path: 'plugin-check-General.png',
			fullPage: true,
		} );
	} );

	test( 'Can execute plugin repo checks', async ( { page } ) => {
		await page.goto( 'wp-admin/tools.php?page=plugin-check' );
		await page
			.getByLabel( 'Check the Plugin' )
			.selectOption( { label: 'WooCommerce' } );
		await page.getByRole( 'checkbox', { name: 'General' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Plugin Repo' } ).check();
		await page.getByRole( 'checkbox', { name: 'Security' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Performance' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Accessibility' } ).uncheck();
		await page.getByRole( 'button', { name: 'Check it!' } ).click();

		await expect(
			page.getByRole( 'heading', { name: 'Plugin Check' } )
		).toBeVisible();

		await expect( page.getByText( 'Checks complete.' ) ).toBeVisible( {
			timeout: 120000,
		} );

		await page.screenshot( {
			path: 'plugin-check-Plugin_repo.png',
			fullPage: true,
		} );
	} );

	test( 'Can execute security checks', async ( { page } ) => {
		await page.goto( 'wp-admin/tools.php?page=plugin-check' );
		await page
			.getByLabel( 'Check the Plugin' )
			.selectOption( { label: 'WooCommerce' } );
		await page.getByRole( 'checkbox', { name: 'General' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Plugin Repo' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Security' } ).check();
		await page.getByRole( 'checkbox', { name: 'Performance' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Accessibility' } ).uncheck();
		await page.getByRole( 'button', { name: 'Check it!' } ).click();

		await expect(
			page.getByRole( 'heading', { name: 'Plugin Check' } )
		).toBeVisible();

		await expect( page.getByText( 'Checks complete.' ) ).toBeVisible( {
			timeout: 120000,
		} );

		await page.screenshot( {
			path: 'plugin-check-Security.png',
			fullPage: true,
		} );
	} );

	test( 'Can execute performance checks', async ( { page } ) => {
		await page.goto( 'wp-admin/tools.php?page=plugin-check' );
		await page
			.getByLabel( 'Check the Plugin' )
			.selectOption( { label: 'WooCommerce' } );
		await page.getByRole( 'checkbox', { name: 'General' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Plugin Repo' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Security' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Performance' } ).check();
		await page.getByRole( 'checkbox', { name: 'Accessibility' } ).uncheck();
		await page.getByRole( 'button', { name: 'Check it!' } ).click();

		await expect(
			page.getByRole( 'heading', { name: 'Plugin Check' } )
		).toBeVisible();

		await expect( page.getByText( 'Checks complete.' ) ).toBeVisible( {
			timeout: 120000,
		} );

		await page.screenshot( {
			path: 'plugin-check-Performance.png',
			fullPage: true,
		} );
	} );

	test( 'Can execute accessibility checks', async ( { page } ) => {
		await page.goto( 'wp-admin/tools.php?page=plugin-check' );
		await page
			.getByLabel( 'Check the Plugin' )
			.selectOption( { label: 'WooCommerce' } );
		await page.getByRole( 'checkbox', { name: 'General' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Plugin Repo' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Security' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Performance' } ).uncheck();
		await page.getByRole( 'checkbox', { name: 'Accessibility' } ).check();
		await page.getByRole( 'button', { name: 'Check it!' } ).click();

		await expect(
			page.getByRole( 'heading', { name: 'Plugin Check' } )
		).toBeVisible();

		await expect(
			page.getByText( 'Checks complete. No errors found.' )
		).toBeVisible( {
			timeout: 120000,
		} );

		await page.screenshot( {
			path: 'plugin-check-Accessibility.png',
			fullPage: true,
		} );
	} );
} );
