const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'WooCommerce Tax Settings > enable', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'can enable tax calculation', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=general' );
//await expect(page).toHaveScreenshot();

		// Make sure the general tab is active
		// await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
		// 	'General'
		// );//translate
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'General'
		);//translate

		// Enable tax calculation
		await page.check( '#woocommerce_calc_taxes' );
		//await page.click( 'text=Save changes' );//translate
		await page.click( 'text=Guardar los cambios' );//translate

		// Verify that settings have been saved
		// await expect( page.locator( 'div.updated.inline' ) ).toContainText(
		// 	'Your settings have been saved.'
		// );//translate
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Tus ajustes se han guardado.'
		);//translate
		await expect( page.locator( '#woocommerce_calc_taxes' ) ).toBeChecked();

		// Verify that tax settings are now present
		// await expect(
		// 	page.locator( 'a.nav-tab:has-text("Tax")' )
		// ).toBeVisible();//translate
		await expect(
			page.locator( 'a.nav-tab:has-text("Impuesto")' )
		).toBeVisible();//translate
		
	} );
} );

test.describe.serial( 'WooCommerce Tax Settings', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'yes',
		} );
	} );
	test.afterEach( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'no',
		} );
	} );

	test( 'can set tax options', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=tax', {
			waitUntil: 'networkidle',
		} );
//await expect(page).toHaveScreenshot();

		// Make sure we're on the tax tab
		// await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
		// 	'Tax'
		// );//translate
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Impuesto'
		);//translate

		// Prices exclusive of tax
		// await page.check( 'text=No, I will enter prices exclusive of tax' );//translate
		await page.check( 'text=No, introduciré los precios sin impuestos' );//translate
		
		// Tax based on customer shipping address
		await page.selectOption( '#woocommerce_tax_based_on', 'shipping' );
		
		// Standard tax class for shipping
		// await page.selectOption( '#woocommerce_shipping_tax_class', {
		// 	label: 'Standard',
		// } );//translate
		await page.selectOption( '#woocommerce_shipping_tax_class', {
			label: 'Estándar',
		} );//translate

		// Leave rounding unchecked
		// Display prices excluding tax
		await page.selectOption( '#woocommerce_tax_display_shop', 'excl' );
		// Display prices including tax in cart and at checkout
		await page.selectOption( '#woocommerce_tax_display_cart', 'incl' );
		// Display a single tax total
		await page.selectOption( '#woocommerce_tax_total_display', 'single' );
		//await page.click( 'text=Save changes' );//translate
		await page.click( 'text=Guardar los cambios' );//translate

		// Verify that settings have been saved
		// await expect( page.locator( 'div.updated.inline' ) ).toContainText(
		// 	'Your settings have been saved.'
		// );//translate
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Tus ajustes se han guardado.'
		);//translate
		// await expect(
		// 	page.locator( 'text=No, I will enter prices exclusive of tax' )
		// ).toBeChecked();//translate
		await expect(
			page.locator( 'text=No, introduciré los precios sin impuestos' )
		).toBeChecked();//translate

		await expect( page.locator( '#woocommerce_tax_based_on' ) ).toHaveValue(
			'shipping'
		);
		// await expect(
		// 	page.locator( '#woocommerce_shipping_tax_class' )
		// ).toContainText( 'Standard' );//translate
		await expect(
			page.locator( '#woocommerce_shipping_tax_class' )
		).toContainText( 'Estándar' );//translate
		
		await expect(
			page.locator( '#woocommerce_tax_display_shop' )
		).toHaveValue( 'excl' );
		await expect(
			page.locator( '#woocommerce_tax_display_cart' )
		).toHaveValue( 'incl' );
		await expect(
			page.locator( '#woocommerce_tax_total_display' )
		).toHaveValue( 'single' );
	} );

	test( 'can add tax classes', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=tax', {
			waitUntil: 'networkidle',
		} );
//await expect(page).toHaveScreenshot();

		// await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
		// 	'Tax'
		// );//translate
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Impuesto'
		);//translate
		

		// Clear out existing tax classes
		await page.fill( '#woocommerce_tax_classes', '' );
		//await page.click( 'text=Save changes' );//translate
		await page.click( 'text=Guardar los cambios' );//translate

		// Verify that the settings have been saved
		// await expect( page.locator( 'div.updated.inline' ) ).toContainText(
		// 	'Your settings have been saved.'
		// );//translate
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Tus ajustes se han guardado.'
		);//translate
		await expect( page.locator( '#woocommerce_tax_classes' ) ).toHaveValue(
			''
		);

		// Add a "fancy" tax class
		await page.fill( '#woocommerce_tax_classes', 'Fancy' );
		//await page.click( 'text=Save changes' );//translate
		await page.click( 'text=Guardar los cambios' );//translate

		// Verify that the settings have been saved
		// await expect( page.locator( 'div.updated.inline' ) ).toContainText(
		// 	'Your settings have been saved.'
		// );//translate
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Tus ajustes se han guardado.'
		);//translate
		// await expect(
		// 	page.locator( 'ul.subsubsub > li > a >> nth=2' )
		// ).toContainText( 'Fancy rates' );//translate
		await expect(
			page.locator( 'ul.subsubsub > li > a >> nth=2' )
		).toContainText( 'Tarifas Fancy' );//translate
	} );

	test( 'can set rate settings', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=tax&section=fancy',
			{ waitUntil: 'networkidle' }
		);
//await expect(page).toHaveScreenshot();

		// Make sure the tax tab is active, with the "fancy" subsection
		// await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
		// 	'Tax'
		// );//translate
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Impuesto'
		);//translate
		
		// await expect(
		// 	page.locator( 'ul.subsubsub > li > a.current' )
		// ).toContainText( 'Fancy rates' );//translate
		await expect(
			page.locator( 'ul.subsubsub > li > a.current' )
		).toContainText( 'Tarifas Fancy' );//translate

		// Create a state tax
		await page.click( '.wc_tax_rates a.insert' );
		await page.fill( 'input[name^="tax_rate_country[new-0"]', 'US' );
		await page.fill( 'input[name^="tax_rate_state[new-0"]', 'CA' );
		await page.fill( 'input[name^="tax_rate[new-0"]', '7.5' );
		await page.fill( 'input[name^="tax_rate_name[new-0"]', 'CA State Tax' );

		// Create a federal tax
		await page.click( '.wc_tax_rates a.insert' );
		await page.fill( 'input[name^="tax_rate_country[new-1"]', 'US' );
		await page.fill( 'input[name^="tax_rate[new-1"]', '1.5' );
		await page.fill( 'input[name^="tax_rate_priority[new-1"]', '2' );
		await page.fill( 'input[name^="tax_rate_name[new-1"]', 'Federal Tax' );
		await page.click( 'input[name^="tax_rate_shipping[new-1"]' );

		// Save changes
		//await page.click( 'text=Save changes' );//translate
		await page.click( 'text=Guardar los cambios' );//translate
		await page.waitForLoadState( 'networkidle' );

		// Verity that there are 2 rates
		await expect( page.locator( '#rates tr' ) ).toHaveCount( 2 );

		// Delete federal rate
		await page.click( '#rates tr:nth-child(2) input' );
		await page.click( '.wc_tax_rates a.remove_tax_rates' );

		// Save changes
		//await page.click( 'text=Save changes' );//translate
		await page.click( 'text=Guardar los cambios' );//translate
		await page.waitForLoadState( 'networkidle' );

		// Verity that there are 2 rates
		await expect( page.locator( '#rates tr' ) ).toHaveCount( 1 );
		await expect(
			page.locator(
				'#rates tr:first-of-type input[name^="tax_rate_state"][value="CA"]'
			)
		).toBeVisible();

		// Delete State tax
		await page.click( '#rates tr input' );
		await page.click( '.wc_tax_rates a.remove_tax_rates' );
		//await page.click( 'text=Save changes' );//translate
		await page.click( 'text=Guardar los cambios' );//translate
		await page.waitForLoadState( 'networkidle' );
	} );

	test( 'can remove tax classes', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=tax' );
//await expect(page).toHaveScreenshot();
		// await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
		// 	'Tax'
		// );//translate
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Impuesto'
		);//translate

		// Remove "Fancy" tax class
		await page.fill( '#woocommerce_tax_classes', '' );
		//await page.click( 'text=Save changes' );//translate
		await page.click( 'text=Guardar los cambios' );//translate

		// Verify that settings have been saved
		// await expect( page.locator( 'div.updated.inline' ) ).toContainText(
		// 	'Your settings have been saved.'
		// );//translate
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Tus ajustes se han guardado.'
		);//translate
		await expect( page.locator( '#woocommerce_tax_classes' ) ).toHaveValue(
			''
		);
		await expect(
			page.locator( 'ul.subsubsub > li > a:has-text("Fancy rates")' )
		).toHaveCount( 0 );
	} );
} );
