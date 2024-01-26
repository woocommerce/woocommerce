const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'WooCommerce Tax Settings > enable', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'can enable tax calculation', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=general' );

		// Make sure the general tab is active
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'General'
		);

		// Enable tax calculation
		await page.locator( '#woocommerce_calc_taxes' ).check();
		await page.locator( 'text=Save changes' ).click();

		// Verify that settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect( page.locator( '#woocommerce_calc_taxes' ) ).toBeChecked();

		// Verify that tax settings are now present
		await expect(
			page.locator( 'a.nav-tab:has-text("Tax")' )
		).toBeVisible();
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

		// Make sure we're on the tax tab
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Tax'
		);

		// Prices exclusive of tax
		await page
			.locator( 'text=No, I will enter prices exclusive of tax' )
			.check();
		// Tax based on customer shipping address
		await page
			.locator( '#woocommerce_tax_based_on' )
			.selectOption( 'shipping' );
		// Standard tax class for shipping
		await page.locator( '#woocommerce_shipping_tax_class' ).selectOption( {
			label: 'Standard',
		} );
		// Leave rounding unchecked
		// Display prices excluding tax
		await page
			.locator( '#woocommerce_tax_display_shop' )
			.selectOption( 'excl' );
		// Display prices including tax in cart and at checkout
		await page
			.locator( '#woocommerce_tax_display_cart' )
			.selectOption( 'incl' );
		// Display a single tax total
		await page
			.locator( '#woocommerce_tax_total_display' )
			.selectOption( 'single' );
		await page.locator( 'text=Save changes' ).click();

		// Verify that settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( 'text=No, I will enter prices exclusive of tax' )
		).toBeChecked();
		await expect( page.locator( '#woocommerce_tax_based_on' ) ).toHaveValue(
			'shipping'
		);
		await expect(
			page.locator( '#woocommerce_shipping_tax_class' )
		).toContainText( 'Standard' );
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

		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Tax'
		);

		// Clear out existing tax classes
		await page.locator( '#woocommerce_tax_classes' ).fill( '' );
		await page.locator( 'text=Save changes' ).click();

		// Verify that the settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect( page.locator( '#woocommerce_tax_classes' ) ).toHaveValue(
			''
		);

		// Add a "fancy" tax class
		await page.locator( '#woocommerce_tax_classes' ).fill( 'Fancy' );
		await page.locator( 'text=Save changes' ).click();

		// Verify that the settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( 'ul.subsubsub > li > a >> nth=2' )
		).toContainText( 'Fancy rates' );
	} );

	test( 'can set rate settings', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=tax&section=fancy',
			{ waitUntil: 'networkidle' }
		);

		// Make sure the tax tab is active, with the "fancy" subsection
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Tax'
		);
		await expect(
			page.locator( 'ul.subsubsub > li > a.current' )
		).toContainText( 'Fancy rates' );

		// Create a state tax
		await page.locator( '.wc_tax_rates a.insert' ).click();
		await page
			.locator( 'input[name^="tax_rate_country[new-0"]' )
			.fill( 'US' );
		await page
			.locator( 'input[name^="tax_rate_state[new-0"]' )
			.fill( 'CA' );
		await page.locator( 'input[name^="tax_rate[new-0"]' ).fill( '7.5' );
		await page
			.locator( 'input[name^="tax_rate_name[new-0"]' )
			.fill( 'CA State Tax' );

		// Create a federal tax
		await page.locator( '.wc_tax_rates a.insert' ).click();
		await page
			.locator( 'input[name^="tax_rate_country[new-1"]' )
			.fill( 'US' );
		await page.locator( 'input[name^="tax_rate[new-1"]' ).fill( '1.5' );
		await page
			.locator( 'input[name^="tax_rate_priority[new-1"]' )
			.fill( '2' );
		await page
			.locator( 'input[name^="tax_rate_name[new-1"]' )
			.fill( 'Federal Tax' );
		await page.locator( 'input[name^="tax_rate_shipping[new-1"]' ).click();

		// Save changes
		await page.locator( 'text=Save changes' ).click();
		await expect( page.locator( '.blockOverlay' ) ).toBeHidden();

		// Verity that there are 2 rates
		await expect( page.locator( '#rates tr' ) ).toHaveCount( 2 );

		// Delete federal rate
		await page.locator( 'input[value="Federal Tax"]' ).click();
		await page
			.getByRole( 'link', { name: 'Remove selected row(s)' } )
			.click();

		// Save changes
		await page.locator( 'text=Save changes' ).click();
		await expect( page.locator( '.blockOverlay' ) ).toBeHidden();

		// Verify that there is 1 tax rate left
		await expect( page.locator( '#rates tr' ) ).toHaveCount( 1 );
		await expect(
			page.locator(
				'#rates tr:first-of-type input[name^="tax_rate_state"][value="CA"]'
			)
		).toBeVisible();

		// Delete State tax
		await page.locator( '[value="CA State Tax"]' ).click();
		await page.locator( '.wc_tax_rates a.remove_tax_rates' ).click();
		await page.locator( 'text=Save changes' ).click();
		await expect( page.locator( '.blockOverlay' ) ).toBeHidden();
	} );

	test( 'can remove tax classes', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=tax' );
		await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
			'Tax'
		);

		// Remove "Fancy" tax class
		await page.locator( '#woocommerce_tax_classes' ).fill( '' );
		await page.locator( 'text=Save changes' ).click();
		await expect( page.locator( '.blockOverlay' ) ).toBeHidden();

		// Verify that settings have been saved
		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect( page.locator( '#woocommerce_tax_classes' ) ).toHaveValue(
			''
		);
		await expect(
			page.locator( 'ul.subsubsub > li > a:has-text("Fancy rates")' )
		).toHaveCount( 0 );
	} );
} );
