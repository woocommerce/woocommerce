const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const simpleProductName = 'Add new order simple product';
const variableProductName = 'Add new order variable product';
const externalProductName = 'Add new order external product';
const groupedProductName = 'Add new order grouped product';
const taxClasses = [
	{
		name: 'Tax Class Simple',
		slug: 'tax-class-simple',
	},
	{
		name: 'Tax Class Variable',
		slug: 'tax-class-variable',
	},
	{
		name: 'Tax Class External',
		slug: 'tax-class-external',
	},
];
const taxRates = [
	{
		name: 'Tax Rate Simple',
		rate: '10.0000',
		class: 'tax-class-simple',
	},
	{
		name: 'Tax Rate Variable',
		rate: '20.0000',
		class: 'tax-class-variable',
	},
	{
		name: 'Tax Rate External',
		rate: '30.0000',
		class: 'tax-class-external',
	},
];
const taxTotals = [ '10.00', '20.00', '240.00' ];
let simpleProductId,
	variableProductId,
	externalProductId,
	subProductAId,
	subProductBId,
	groupedProductId,
	customerId,
	orderId;

test.describe( 'WooCommerce Orders > Add new order', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// enable taxes on the account
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'yes',
		} );
		// add tax classes
		for ( const taxClass of taxClasses ) {
			await api.post( 'taxes/classes', taxClass );
		}
		// attach rates to the classes
		for ( let i = 0; i < taxRates.length; i++ ) {
			await api.post( 'taxes', taxRates[ i ] );
		}
		// create simple product
		await api
			.post( 'products', {
				name: simpleProductName,
				type: 'simple',
				regular_price: '100',
				tax_class: 'Tax Class Simple',
			} )
			.then( ( resp ) => {
				simpleProductId = resp.data.id;
			} );
		// create variable product
		const variations = [
			{
				regular_price: '100',
				attributes: [
					{
						name: 'Size',
						option: 'Small',
					},
					{
						name: 'Colour',
						option: 'Yellow',
					},
				],
				tax_class: 'Tax Class Variable',
			},
			{
				regular_price: '100',
				attributes: [
					{
						name: 'Size',
						option: 'Medium',
					},
					{
						name: 'Colour',
						option: 'Magenta',
					},
				],
				tax_class: 'Tax Class Variable',
			},
		];
		await api
			.post( 'products', {
				name: variableProductName,
				type: 'variable',
				tax_class: 'Tax Class Variable',
			} )
			.then( ( response ) => {
				variableProductId = response.data.id;
				for ( const key in variations ) {
					api.post(
						`products/${ variableProductId }/variations`,
						variations[ key ]
					);
				}
			} );
		// create external product
		await api
			.post( 'products', {
				name: externalProductName,
				regular_price: '800',
				tax_class: 'Tax Class External',
				external_url: 'https://wordpress.org/plugins/woocommerce',
				type: 'external',
				button_text: 'Buy now',
			} )
			.then( ( response ) => {
				externalProductId = response.data.id;
			} );
		// create grouped product
		await api
			.post( 'products', { name: 'Add-on A', regular_price: '11.95' } )
			.then( ( response ) => {
				subProductAId = response.data.id;
			} );
		await api
			.post( 'products', { name: 'Add-on B', regular_price: '18.97' } )
			.then( ( response ) => {
				subProductBId = response.data.id;
			} );
		await api
			.post( 'products', {
				name: groupedProductName,
				regular_price: '29.99',
				grouped_products: [ subProductAId, subProductBId ],
				type: 'grouped',
			} )
			.then( ( response ) => {
				groupedProductId = response.data.id;
			} );
		// create a customer
		await api
			.post( 'customers', {
				email: 'sideshowbob@example.com',
				first_name: 'Sideshow',
				last_name: 'Bob',
				username: 'sideshowbob',
				billing: {
					first_name: 'Sideshow',
					last_name: 'Bob',
					company: 'Die Bart Die',
					address_1: '123 Fake St',
					address_2: '',
					city: 'Springfield',
					state: 'FL',
					postcode: '12345',
					country: 'US',
					email: 'sideshowbob@example.com',
					phone: '555-555-5556',
				},
				shipping: {
					first_name: 'Sideshow',
					last_name: 'Bob',
					company: 'Die Bart Die',
					address_1: '321 Fake St',
					address_2: '',
					city: 'Springfield',
					state: 'FL',
					postcode: '12345',
					country: 'US',
				},
			} )
			.then( ( response ) => {
				customerId = response.data.id;
			} );
	} );

	test.afterEach( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// clean up order after each test
		if ( orderId && orderId !== '' ) {
			await api.delete( `orders/${ orderId }`, { force: true } );
		}
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// cleans up all products after run
		await api.post( 'products/batch', {
			delete: [
				simpleProductId,
				variableProductId,
				externalProductId,
				subProductAId,
				subProductBId,
				groupedProductId,
			],
		} );
		// clean up tax classes and rates
		for ( const { slug } of taxClasses ) {
			await api
				.delete( `taxes/classes/${ slug }`, {
					force: true,
				} )
				.catch( ( error ) => {
					if (
						error.response.data.code ===
						'woocommerce_rest_invalid_tax_class'
					) {
						// do nothing, probably the tax class was not created due to a failing test
					} else {
						// Something else went wrong.
						throw new Error( error.response.data );
					}
				} );
		}
		// turn off taxes
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'no',
		} );
		// clean up customer
		await api.delete( `customers/${ customerId }`, { force: true } );
	} );

	test( 'can create a simple guest order', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=shop_order' );

		// get order ID from the page
		const orderText = await page
			.locator( 'h2.woocommerce-order-data__heading' )
			.textContent();
		orderId = orderText.match( /([0-9])\w+/ );
		orderId = orderId[ 0 ].toString();

		await page.locator( '#order_status' ).selectOption( 'wc-processing' );

		// Enter billing information
		await page
			.getByRole( 'heading', { name: 'Billing Edit' } )
			.getByRole( 'link' )
			.click();
		await page
			.getByRole( 'textbox', { name: 'First name' } )
			.fill( 'Bart' );
		await page
			.getByRole( 'textbox', { name: 'Last name' } )
			.fill( 'Simpson' );
		await page
			.getByRole( 'textbox', { name: 'Company' } )
			.fill( 'Kwik-E-Mart' );
		await page
			.getByRole( 'textbox', { name: 'Address line 1' } )
			.fill( '742 Evergreen Terrace' );
		await page
			.getByRole( 'textbox', { name: 'City' } )
			.fill( 'Springfield' );
		await page.getByRole( 'textbox', { name: 'Postcode' } ).fill( '12345' );
		await page
			.getByRole( 'textbox', { name: 'Select an option…' } )
			.click();
		await page.getByRole( 'option', { name: 'Florida' } ).click();
		await page
			.getByRole( 'textbox', { name: 'Email address' } )
			.fill( 'elbarto@example.com' );
		await page
			.getByRole( 'textbox', { name: 'Phone' } )
			.fill( '555-555-5555' );
		await page
			.getByRole( 'textbox', { name: 'Transaction ID' } )
			.fill( '1234567890' );

		// Enter shipping information
		await page
			.getByRole( 'heading', { name: 'Shipping Edit' } )
			.getByRole( 'link' )
			.click();
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page
			.getByRole( 'link', { name: 'Copy billing address' } )
			.click();
		await page
			.getByPlaceholder( 'Customer notes about the order' )
			.fill( 'Only asked for a slushie' );

		// Add a product
		await page.getByRole( 'button', { name: 'Add item(s)' } ).click();
		await page.getByRole( 'button', { name: 'Add product(s)' } ).click();
		await page.getByText( 'Search for a product…' ).click();
		await page.locator( 'span > .select2-search__field' ).fill( 'Simple' );
		await page.getByRole( 'option', { name: simpleProductName } ).click();
		await page
			.getByRole( 'row', { name: '×Add new order simple product' } )
			.getByPlaceholder( '1' )
			.fill( '2' );
		await page.locator( '#btn-ok' ).click();

		// Create the order
		await page.getByRole( 'button', { name: 'Create' } ).click();
		await expect( page.locator( 'div.notice-success' ) ).toContainText(
			'Order updated.'
		);

		// Confirm the details
		await expect(
			page.getByText(
				'Billing Edit Load billing address Bart SimpsonKwik-E-Mart742 Evergreen'
			)
		).toBeVisible();
		await expect(
			page.getByText(
				'Shipping Edit Load shipping address Copy billing address Bart SimpsonKwik-E-'
			)
		).toBeVisible();
		await expect(
			page.locator( 'table' ).filter( { hasText: 'Paid: $200.00' } )
		).toBeVisible();
	} );

	test( 'can create an order for an existing customer', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=shop_order' );

		// get order ID from the page
		const orderText = await page
			.locator( 'h2.woocommerce-order-data__heading' )
			.textContent();
		orderId = orderText.match( /([0-9])\w+/ );
		orderId = orderId[ 0 ].toString();

		// Select customer
		await page.getByText( 'Guest' ).click();
		await page.getByRole( 'combobox' ).nth( 4 ).fill( 'sideshowbob@' );
		await page.getByRole( 'option', { name: 'Sideshow Bob' } ).click();

		// Add a product
		await page.getByRole( 'button', { name: 'Add item(s)' } ).click();
		await page.getByRole( 'button', { name: 'Add product(s)' } ).click();
		await page.getByText( 'Search for a product…' ).click();
		await page.locator( 'span > .select2-search__field' ).fill( 'Simple' );
		await page.getByRole( 'option', { name: simpleProductName } ).click();
		await page
			.getByRole( 'row', { name: '×Add new order simple product' } )
			.getByPlaceholder( '1' )
			.fill( '2' );
		await page.locator( '#btn-ok' ).click();

		// Create the order
		await page.getByRole( 'button', { name: 'Create' } ).click();
		await expect( page.locator( 'div.notice-success' ) ).toContainText(
			'Order updated.'
		);

		// Confirm the details
		await expect(
			page.getByText(
				'Billing Edit Load billing address Sideshow BobDie Bart Die123 Fake'
			)
		).toBeVisible();
		await expect(
			page.getByText(
				'Shipping Edit Load shipping address Copy billing address Sideshow BobDie Bart'
			)
		).toBeVisible();

		// View customer profile
		await page.getByRole( 'link', { name: 'Profile →' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Edit User sideshowbob' } )
		).toBeVisible();

		// Go back to the order
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );
		await page
			.getByRole( 'link', {
				name: 'View other orders',
			} )
			.click();
		await expect( page.locator( 'h1.wp-heading-inline' ) ).toContainText(
			'Orders'
		);
		await expect( page.getByRole( 'row' ) ).toHaveCount( 3 ); // 1 order and header and footer rows
	} );

	test( 'can create new order', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=shop_order' );
		await expect( page.locator( 'h1.wp-heading-inline' ) ).toContainText(
			'Add new order'
		);

		await page.waitForLoadState( 'networkidle' );
		// get order ID from the page
		const orderText = await page
			.locator( 'h2.woocommerce-order-data__heading' )
			.textContent();
		orderId = orderText.match( /([0-9])\w+/ );
		orderId = orderId[ 0 ].toString();

		await page.locator( '#order_status' ).selectOption( 'wc-processing' );
		await page.locator( 'input[name=order_date]' ).fill( '2018-12-13' );
		await page.locator( 'input[name=order_date_hour]' ).fill( '18' );
		await page.locator( 'input[name=order_date_minute]' ).fill( '55' );

		await page.locator( 'button.save_order' ).click();

		await expect(
			page.locator( 'div.updated.notice.notice-success.is-dismissible', {
				has: page.locator( 'p' ),
			} )
		).toContainText( 'Order updated.' );
		await expect( page.locator( '#order_status' ) ).toHaveValue(
			'wc-processing'
		);
		await expect( page.locator( 'div.note_content' ) ).toContainText(
			'Order status changed from Pending payment to Processing.'
		);
	} );

	test( 'can create new complex order with multiple product types & tax classes', async ( {
		page,
	} ) => {
		orderId = '';
		await page.goto( 'wp-admin/post-new.php?post_type=shop_order' );

		// open modal for adding line items
		await page.locator( 'button.add-line-item' ).click();
		await page.locator( 'button.add-order-item' ).click();

		// search for each product to add
		await page.locator( 'text=Search for a product…' ).click();
		await page
			.locator( '.select2-search--dropdown' )
			.getByRole( 'combobox' )
			.pressSequentially( simpleProductName );
		await page
			.locator(
				'li.select2-results__option.select2-results__option--highlighted'
			)
			.click();

		await page.locator( 'text=Search for a product…' ).click();
		await page
			.locator( '.select2-search--dropdown' )
			.getByRole( 'combobox' )
			.pressSequentially( variableProductName );
		await page
			.locator(
				'li.select2-results__option.select2-results__option--highlighted'
			)
			.click();

		await page.locator( 'text=Search for a product…' ).click();
		await page
			.locator( '.select2-search--dropdown' )
			.getByRole( 'combobox' )
			.type( groupedProductName );
		await page
			.locator(
				'li.select2-results__option.select2-results__option--highlighted'
			)
			.click();

		await page.locator( 'text=Search for a product…' ).click();
		await page
			.locator( '.select2-search--dropdown' )
			.getByRole( 'combobox' )
			.type( externalProductName );
		await page
			.locator(
				'li.select2-results__option.select2-results__option--highlighted'
			)
			.click();

		await page.locator( 'button#btn-ok' ).click();

		// assert that products added
		await expect( page.locator( 'td.name > a >> nth=0' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'td.name > a >> nth=1' ) ).toContainText(
			variableProductName
		);
		await expect( page.locator( 'td.name > a >> nth=2' ) ).toContainText(
			groupedProductName
		);
		await expect( page.locator( 'td.name > a >> nth=3' ) ).toContainText(
			externalProductName
		);

		// Recalculate taxes
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page.locator( 'text=Recalculate' ).click();

		// verify tax names
		let i = 0;
		for ( const taxRate of taxRates ) {
			await expect(
				page.locator( `th.line_tax >> nth=${ i }` )
			).toHaveText( taxRate.name );
			i++;
		}

		// verify tax amounts
		i = 1; // subtotal line is 0 here
		for ( const taxAmount of taxTotals ) {
			await expect(
				page.locator( `.wc-order-totals td.total >> nth=${ i }` )
			).toContainText( taxAmount );
			i++;
		}
	} );
} );
