const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const { random } = require( '../../utils/helpers' );

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

async function getOrderIdFromPage( page ) {
	// get order ID from the page
	const orderText = await page
		.locator( 'h2.woocommerce-order-data__heading' )
		.textContent();
	const parts = orderText.match( /([0-9])\w+/ );
	return parts[ 0 ];
}

async function addProductToOrder( page, product, quantity ) {
	await page.getByRole( 'button', { name: 'Add item(s)' } ).click();
	await page.getByRole( 'button', { name: 'Add product(s)' } ).click();
	await page.getByText( 'Search for a product…' ).click();
	await page.locator( 'span > .select2-search__field' ).fill( product.name );
	await page.getByRole( 'option', { name: product.name } ).first().click();
	await page
		.locator( 'tr' )
		.filter( { hasText: product.name } )
		.getByPlaceholder( '1' )
		.fill( quantity.toString() );
	await page.locator( '#btn-ok' ).click();
}

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	order: async ( { api }, use ) => {
		const order = {};

		await use( order );

		if ( order.id ) {
			await api.delete( `orders/${ order.id }`, { force: true } );
		}
	},

	customer: async ( { api }, use ) => {
		let customer = {};
		const username = `sideshowbob_${ random() }`;

		await api
			.post( 'customers', {
				email: `${ username }@example.com`,
				first_name: 'Sideshow',
				last_name: 'Bob',
				username,
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
					email: `${ username }@example.com`,
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
				customer = response.data;
			} );

		await use( customer );

		// Cleanup
		await api.delete( `customers/${ customer.id }`, { force: true } );
	},

	simpleProduct: async ( { api }, use ) => {
		let product = {};

		await api
			.post( 'products', {
				name: `Product simple ${ random() }`,
				type: 'simple',
				regular_price: '100',
				tax_class: 'Tax Class Simple',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await api.delete( `products/${ product.id }`, { force: true } );
	},

	variableProduct: async ( { api }, use ) => {
		let product = {};

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
				name: `Product variable ${ random() }`,
				type: 'variable',
				tax_class: 'Tax Class Variable',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		for ( const key in variations ) {
			api.post(
				`products/${ product.id }/variations`,
				variations[ key ]
			);
		}

		await use( product );

		// Cleanup
		await api.delete( `products/${ product.id }`, { force: true } );
	},

	externalProduct: async ( { api }, use ) => {
		let product = {};

		await api
			.post( 'products', {
				name: `Product external ${ random() }`,
				regular_price: '800',
				tax_class: 'Tax Class External',
				external_url: 'https://wordpress.org/plugins/woocommerce',
				type: 'external',
				button_text: 'Buy now',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await api.delete( `products/${ product.id }`, { force: true } );
	},

	groupedProduct: async ( { api }, use ) => {
		let product = {};
		let subProductAId;
		let subProductBId;

		await api
			.post( 'products', {
				name: 'Add-on A',
				regular_price: '11.95',
			} )
			.then( ( response ) => {
				subProductAId = response.data.id;
			} );
		await api
			.post( 'products', {
				name: 'Add-on B',
				regular_price: '18.97',
			} )
			.then( ( response ) => {
				subProductBId = response.data.id;
			} );
		await api
			.post( 'products', {
				name: `Product grouped ${ random() }`,
				regular_price: '29.99',
				grouped_products: [ subProductAId, subProductBId ],
				type: 'grouped',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await api.delete( `products/${ product.id }`, { force: true } );
	},
} );

test.describe(
	'WooCommerce Orders > Add new order',
	{ tag: [ '@services', '@hpos' ] },
	() => {
		test.beforeAll( async ( { api } ) => {
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
		} );

		test.afterAll( async ( { api } ) => {
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
		} );

		test( 'can create a simple guest order', async ( {
			page,
			simpleProduct,
			order,
		} ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders&action=new' );
			order.id = await getOrderIdFromPage( page );

			await page
				.locator( '#order_status' )
				.selectOption( 'wc-processing' );

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
			await page
				.getByRole( 'textbox', { name: 'Postcode' } )
				.fill( '12345' );
			// eslint-disable-next-line playwright/no-conditional-in-test
			if (
				await page
					.getByRole( 'textbox', { name: 'Select an option…' } )
					.isVisible()
			) {
				await page
					.getByRole( 'textbox', { name: 'Select an option…' } )
					.click();
				await page.getByRole( 'option', { name: 'Florida' } ).click();
			}
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
			await addProductToOrder( page, simpleProduct, 2 );

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
			simpleProduct,
			customer,
			order,
		} ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders&action=new' );
			order.id = await getOrderIdFromPage( page );

			// Select customer
			await page.getByText( 'Guest' ).click();
			await page
				.locator( 'input[aria-owns="select2-customer_user-results"]' )
				.fill( customer.username );
			await page
				.getByRole( 'option', {
					name: `${ customer.first_name } ${ customer.last_name }`,
				} )
				.click();

			// Add a product
			await addProductToOrder( page, simpleProduct, 2 );

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
				page.getByRole( 'heading', {
					name: `Edit User ${ customer.username }`,
				} )
			).toBeVisible();

			// Go back to the order
			await page.goto(
				`wp-admin/admin.php?page=wc-orders&action=edit&id=${ order.id }`
			);
			await page
				.getByRole( 'link', {
					name: 'View other orders',
				} )
				.click();
			await expect(
				page.locator( 'h1.wp-heading-inline' )
			).toContainText( 'Orders' );
			await expect( page.getByRole( 'row' ) ).toHaveCount( 3 ); // 1 order and header and footer rows
		} );

		test( 'can create new order', async ( { page, order } ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders&action=new' );
			await expect(
				page.locator( 'h1.wp-heading-inline' )
			).toContainText( 'Add new order' );
			order.id = await getOrderIdFromPage( page );

			await page
				.locator( '#order_status' )
				.selectOption( 'wc-processing' );
			await page.locator( 'input[name=order_date]' ).fill( '2018-12-13' );
			await page.locator( 'input[name=order_date_hour]' ).fill( '18' );
			await page.locator( 'input[name=order_date_minute]' ).fill( '55' );

			await page.locator( 'button.save_order' ).click();

			await expect(
				page.locator(
					'div.updated.notice.notice-success.is-dismissible',
					{
						has: page.locator( 'p' ),
					}
				)
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
			simpleProduct,
			variableProduct,
			externalProduct,
			groupedProduct,
			order,
		} ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders&action=new' );
			order.id = await getOrderIdFromPage( page );

			// open modal for adding line items
			await page.locator( 'button.add-line-item' ).click();
			await page.locator( 'button.add-order-item' ).click();

			// search for each product to add
			for ( const product of [
				simpleProduct,
				variableProduct,
				groupedProduct,
				externalProduct,
			] ) {
				await page.getByText( 'Search for a product…' ).click();
				await page
					.locator( 'span > .select2-search__field' )
					.fill( product.name );
				await page
					.getByRole( 'option', { name: product.name } )
					.first()
					.click();
			}

			await page.locator( 'button#btn-ok' ).click();

			// assert that products added
			await expect(
				page.locator( 'td.name > a >> nth=0' )
			).toContainText( simpleProduct.name );
			await expect(
				page.locator( 'td.name > a >> nth=1' )
			).toContainText( variableProduct.name );
			await expect(
				page.locator( 'td.name > a >> nth=2' )
			).toContainText( groupedProduct.name );
			await expect(
				page.locator( 'td.name > a >> nth=3' )
			).toContainText( externalProduct.name );

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
	}
);
