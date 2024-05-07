const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { customer } = require( '../../test-data/data' );
const { addAProductToCart } = require( '../../utils/cart' );
const { random } = require( '../../utils/helpers' );

const productName = `Taxed products are awesome ${ random() }`;
const productPrice = '200.00';
const messyProductPrice = '13.47';
const secondProductName = `Other products are also awesome ${ random() }`;

let productId,
	productId2,
	nastyTaxId,
	seventeenTaxId,
	sixTaxId,
	countryTaxId,
	stateTaxId,
	cityTaxId,
	zipTaxId,
	shippingTaxId,
	shippingZoneId,
	shippingMethodId;

test.describe.serial( 'Tax rates in the cart and checkout', () => {
	test.beforeAll( async ( { baseURL } ) => {
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

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put( 'settings/tax/woocommerce_tax_display_cart', {
			value: 'excl',
		} );
		await api.put( 'settings/tax/woocommerce_tax_display_shop', {
			value: 'excl',
		} );
		await api.put( 'settings/tax/woocommerce_tax_round_at_subtotal', {
			value: 'no',
		} );
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'no',
		} );
		await api.put( 'settings/tax/woocommerce_tax_total_display', {
			value: 'itemized',
		} );
	} );

	test.describe( 'Shopper Tax Display Tests', () => {
		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api
				.post( 'products', {
					name: productName,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '25',
					name: 'Nasty Tax',
					shipping: false,
				} )
				.then( ( response ) => {
					nastyTaxId = response.data.id;
				} );
		} );

		test.beforeEach( async ( { page, context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
			// all tests use the first product
			await addAProductToCart( page, productId );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ nastyTaxId }`, {
				force: true,
			} );
		} );

		test( 'checks that taxes are calculated properly on totals, inclusive tax displayed properly', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'incl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_shop', {
				value: 'incl',
			} );

			await test.step( 'Load shop page, confirm title and confirm price display', async () => {
				await page.goto( '/shop/' );
				await expect(
					page.getByRole( 'heading', { name: 'Shop' } )
				).toBeVisible();
				await expect( page.getByText( productName ) ).toBeVisible();
				await expect(
					page.getByText( '$250.00', { exact: true } )
				).toBeVisible();
				expect( await page.title() ).toBe(
					'Shop – WooCommerce Core E2E Test Suite'
				);
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( '/cart/' );
				await expect(
					page.getByRole( 'heading', { name: 'Cart', exact: true } )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', { name: '$250.00 (incl. tax)' } )
				).toHaveCount( 2 );
				await expect(
					page.getByRole( 'row', {
						name: 'Subtotal $250.00 (incl. tax)',
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', {
						name: '$250.00 (includes $50.00 Nasty Tax)',
					} )
				).toBeVisible();
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( '/checkout/' );
				await expect(
					page.getByRole( 'heading', { name: 'Checkout' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', {
						name: `${ productName } × 1 $250.00 (incl. tax)`,
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', {
						name: 'Subtotal $250.00 (incl. tax)',
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', {
						name: 'Total $250.00 (includes $50.00 Nasty Tax)',
					} )
				).toBeVisible();
			} );
		} );

		test( 'checks that taxes are calculated and displayed correctly exclusive on shop, cart and checkout', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_shop', {
				value: 'excl',
			} );

			await test.step( 'Load shop page and confirm price display', async () => {
				await page.goto( '/shop/' );
				await expect(
					page.getByRole( 'heading', { name: 'Shop' } )
				).toBeVisible();
				await expect( page.getByText( productName ) ).toBeVisible();
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( '/cart/' );
				await expect(
					page.getByRole( 'heading', { name: 'Cart', exact: true } )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', { name: '$200.00' } )
				).toHaveCount( 3 );
				await expect(
					page.getByRole( 'row', { name: 'Subtotal $200.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Tax $50.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Total $250.00' } )
				).toBeVisible();
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( '/checkout/' );
				await expect(
					page.getByRole( 'heading', { name: 'Checkout' } )
				).toBeVisible();

				await page
					.getByRole( 'textbox', { name: 'First name *' } )
					.fill( customer.billing.us.first_name );
				await page
					.getByRole( 'textbox', { name: 'Last name *' } )
					.fill( customer.billing.us.last_name );
				await page
					.getByRole( 'textbox', { name: 'Street address *' } )
					.fill( customer.billing.us.address );
				await page
					.getByRole( 'textbox', { name: 'Town / City *' } )
					.type( customer.billing.us.city );
				await page
					.locator( '#billing_country' )
					.selectOption( customer.billing.us.country );
				await page
					.locator( '#billing_state' )
					.selectOption( customer.billing.us.state );
				await page
					.getByRole( 'textbox', { name: 'ZIP Code *' } )
					.type( customer.billing.us.zip );
				await page
					.getByLabel( 'Phone *' )
					.fill( customer.billing.us.phone );
				await page
					.getByLabel( 'Email address *' )
					.fill( customer.billing.us.email );

				await expect(
					page.getByRole( 'row', {
						name: `${ productName } × 1 $200.00`,
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Subtotal $200.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Nasty Tax $50.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Total $250.00' } )
				).toBeVisible();
			} );
		} );

		test( 'checks that display suffix is shown', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_shop', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_price_display_suffix', {
				value: 'excluding VAT',
			} );

			await test.step( 'Load shop page and confirm price suffix display', async () => {
				await page.goto( '/shop/' );
				await expect(
					page.getByRole( 'heading', { name: 'Shop' } )
				).toBeVisible();
				await expect( page.getByText( productName ) ).toBeVisible();
				await expect(
					page.getByText( '$200.00 excluding VAT' )
				).toBeVisible();
			} );
		} );
	} );

	test.describe( 'Shopper Tax Rounding', () => {
		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api
				.post( 'products', {
					name: productName,
					type: 'simple',
					regular_price: messyProductPrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			await api
				.post( 'products', {
					name: secondProductName,
					type: 'simple',
					regular_price: messyProductPrice,
				} )
				.then( ( response ) => {
					productId2 = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '17',
					name: 'Seventeen Tax',
					shipping: false,
					compound: true,
					priority: 1,
				} )
				.then( ( response ) => {
					seventeenTaxId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '6',
					name: 'Six Tax',
					shipping: false,
					compound: true,
					priority: 2,
				} )
				.then( ( response ) => {
					sixTaxId = response.data.id;
				} );
		} );

		test.beforeEach( async ( { page, context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();

			await addAProductToCart( page, productId );
			await addAProductToCart( page, productId2 );
			await addAProductToCart( page, productId2 );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );
			await api.delete( `products/${ productId2 }`, {
				force: true,
			} );
			await api.delete( `taxes/${ seventeenTaxId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ sixTaxId }`, {
				force: true,
			} );
		} );

		test( 'checks rounding at subtotal level', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_shop', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_round_at_subtotal', {
				value: 'yes',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'single',
			} );

			await test.step( 'Load shop page and confirm price display', async () => {
				await page.goto( '/shop/' );
				await expect(
					page.getByRole( 'heading', { name: 'Shop' } )
				).toBeVisible();
				await expect( page.getByText( productName ) ).toBeVisible();
				await expect(
					await page.getByText( '$13.47' ).count()
				).toBeGreaterThan( 0 );
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( '/cart/' );
				await expect(
					page.getByRole( 'heading', { name: 'Cart', exact: true } )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', { name: '$13.47' } )
				).toHaveCount( 3 );
				await expect(
					page.getByRole( 'row', { name: 'Subtotal $40.41' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Tax $9.71 ' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Total $50.12 ' } )
				).toBeVisible();
			} );
		} );

		test( 'checks rounding off at subtotal level', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_shop', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_round_at_subtotal', {
				value: 'no',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'itemized',
			} );

			await test.step( 'Load shop page and confirm price display', async () => {
				await page.goto( '/shop/' );
				await expect(
					page.getByRole( 'heading', { name: 'Shop' } )
				).toBeVisible();
				await expect( page.getByText( productName ) ).toBeVisible();
				await expect(
					await page.getByText( '$13.47' ).count()
				).toBeGreaterThan( 0 );
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( '/cart/' );
				await expect(
					page.getByRole( 'heading', { name: 'Cart', exact: true } )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', { name: '$13.47' } )
				).toHaveCount( 3 );
				await expect(
					page.getByRole( 'row', { name: 'Subtotal $40.41' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Seventeen Tax $6.87 ' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Six Tax $2.84 ' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Total $50.12 ' } )
				).toBeVisible();
			} );
		} );
	} );

	test.describe( 'Shopper Tax Levels', () => {
		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'yes',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );
			await api
				.post( 'products', {
					name: productName,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '10',
					name: 'Country Tax',
					shipping: false,
					priority: 1,
				} )
				.then( ( response ) => {
					countryTaxId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: '*',
					state: 'CA',
					cities: '*',
					postcodes: '*',
					rate: '5',
					name: 'State Tax',
					shipping: false,
					priority: 2,
				} )
				.then( ( response ) => {
					stateTaxId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: '*',
					state: '*',
					cities: 'Sacramento',
					postcodes: '*',
					rate: '2.5',
					name: 'City Tax',
					shipping: false,
					priority: 3,
				} )
				.then( ( response ) => {
					cityTaxId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: '*',
					state: '*',
					cities: '*',
					postcodes: '55555',
					rate: '1.25',
					name: 'Zip Tax',
					shipping: false,
					priority: 4,
				} )
				.then( ( response ) => {
					zipTaxId = response.data.id;
				} );
		} );

		test.beforeEach( async ( { page, context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
			// all tests use the first product
			await addAProductToCart( page, productId );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );

			await api.delete( `taxes/${ countryTaxId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ stateTaxId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ cityTaxId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ zipTaxId }`, {
				force: true,
			} );
		} );

		test( 'checks applying taxes of 4 different levels', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'itemized',
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( '/cart/' );
				await expect(
					page.getByRole( 'heading', { name: 'Cart', exact: true } )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', { name: '$200.00' } )
				).toHaveCount( 3 );
				await expect(
					page.getByRole( 'row', { name: 'Subtotal $200.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Country Tax $20.00 ' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'State Tax $10.00 ' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Total $230.00 ' } )
				).toBeVisible();
			} );

			await test.step( 'Load checkout page and confirm taxes displayed', async () => {
				await page.goto( '/checkout/' );
				await expect(
					page.getByRole( 'heading', {
						name: 'Checkout',
						exact: true,
					} )
				).toBeVisible();

				await page
					.getByLabel( 'First name *' )
					.first()
					.fill( customer.billing.us.first_name );
				await page
					.getByLabel( 'Last name *' )
					.first()
					.fill( customer.billing.us.last_name );
				await page
					.getByPlaceholder( 'House number and street name' )
					.first()
					.fill( customer.billing.us.address );
				await page
					.getByLabel( 'Town / City *' )
					.first()
					.pressSequentially( 'Sacramento' );
				await page
					.getByLabel( 'ZIP Code *' )
					.first()
					.pressSequentially( '55555' );
				await page
					.getByLabel( 'Phone *' )
					.first()
					.fill( customer.billing.us.phone );
				await page
					.getByLabel( 'Email address *' )
					.first()
					.fill( customer.billing.us.email );

				await expect(
					page.getByRole( 'row', { name: 'Subtotal $200.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Country Tax $20.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'State Tax $10.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'City Tax $5.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Zip Tax $2.50' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Total $237.50 ' } )
				).toBeVisible();
			} );
		} );

		test( 'checks applying taxes of 2 different levels (2 excluded)', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'itemized',
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( '/cart/' );
				await expect(
					page.getByRole( 'heading', { name: 'Cart', exact: true } )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', { name: '$200.00' } )
				).toHaveCount( 3 );
				await expect(
					page.getByRole( 'row', { name: 'Subtotal $200.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Country Tax $20.00 ' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'State Tax $10.00 ' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Total $230.00 ' } )
				).toBeVisible();
			} );

			await test.step( 'Load checkout page and confirm taxes displayed', async () => {
				await page.goto( '/checkout/' );
				await expect(
					page.getByRole( 'heading', {
						name: 'Checkout',
						exact: true,
					} )
				).toBeVisible();

				await page
					.getByLabel( 'First name *' )
					.first()
					.fill( customer.billing.us.first_name );
				await page
					.getByLabel( 'Last name *' )
					.first()
					.fill( customer.billing.us.last_name );
				await page
					.getByPlaceholder( 'House number and street name' )
					.first()
					.fill( customer.billing.us.address );
				await page
					.getByLabel( 'Town / City *' )
					.first()
					.pressSequentially( customer.billing.us.city );
				await page
					.getByLabel( 'ZIP Code *' )
					.first()
					.pressSequentially( customer.billing.us.zip );
				await page
					.getByLabel( 'Phone *' )
					.first()
					.fill( customer.billing.us.phone );
				await page
					.getByLabel( 'Email address *' )
					.first()
					.fill( customer.billing.us.email );

				await expect(
					page.getByRole( 'row', { name: 'Subtotal $200.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'Country Tax $20.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'State Tax $10.00' } )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', { name: 'City Tax $5.00' } )
				).toBeHidden();
				await expect(
					page.getByRole( 'row', { name: 'Zip Tax $2.50' } )
				).toBeHidden();
				await expect(
					page.getByRole( 'row', { name: 'Total $230.00 ' } )
				).toBeVisible();
			} );
		} );
	} );

	test.describe( 'Shipping Tax', () => {
		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'yes',
			} );
			await api
				.post( 'products', {
					name: productName,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '15',
					name: 'Shipping Tax',
					shipping: true,
				} )
				.then( ( response ) => {
					shippingTaxId = response.data.id;
				} );
			await api
				.post( 'shipping/zones', {
					name: 'All',
				} )
				.then( ( response ) => {
					shippingZoneId = response.data.id;
				} );
			await api
				.post( `shipping/zones/${ shippingZoneId }/methods`, {
					method_id: 'flat_rate',
					settings: {
						title: 'Flat rate',
					},
				} )
				.then( ( response ) => {
					shippingMethodId = response.data.id;
				} );
			await api.put(
				`shipping/zones/${ shippingZoneId }/methods/${ shippingMethodId }`,
				{
					settings: {
						cost: '20.00',
					},
				}
			);
			await api.put( 'payment_gateways/cod', {
				enabled: true,
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'incl',
			} );
		} );

		test.beforeEach( async ( { page, context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
			// all tests use the first product
			await addAProductToCart( page, productId );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ shippingTaxId }`, {
				force: true,
			} );
			await api.put( 'payment_gateways/cod', {
				enabled: false,
			} );
			await api.delete( `shipping/zones/${ shippingZoneId }`, {
				force: true,
			} );
		} );

		test( 'checks that tax is applied to shipping as well as order', async ( {
			page,
		} ) => {
			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( '/cart/' );
				await expect(
					page.getByRole( 'heading', { name: 'Cart', exact: true } )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', { name: '$230.00 (incl. tax)' } )
				).toHaveCount( 2 );
				await expect(
					page.getByRole( 'row', {
						name: 'Subtotal $230.00 (incl. tax)',
					} )
				).toBeVisible();

				await page.pause();

				await expect(
					page.getByRole( 'row', {
						name: 'Shipping Flat rate: $23.00 (incl. tax) Shipping to CA.',
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', {
						name: 'Total $253.00 (includes $33.00 Shipping Tax)',
					} )
				).toBeVisible();
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( '/checkout/' );
				await expect(
					page.getByRole( 'heading', { name: 'Checkout' } )
				).toBeVisible();

				await page
					.getByRole( 'textbox', { name: 'First name *' } )
					.fill( customer.billing.us.first_name );
				await page
					.getByRole( 'textbox', { name: 'Last name *' } )
					.fill( customer.billing.us.last_name );
				await page
					.getByRole( 'textbox', { name: 'Street address *' } )
					.fill( customer.billing.us.address );
				await page
					.getByRole( 'textbox', { name: 'Town / City *' } )
					.type( customer.billing.us.city );
				await page
					.getByRole( 'textbox', { name: 'ZIP Code *' } )
					.type( customer.billing.us.zip );
				await page
					.getByRole( 'textbox', { name: 'Phone *' } )
					.fill( customer.billing.us.phone );
				await page
					.getByLabel( 'Email address *' )
					.fill( customer.billing.us.email );

				await expect(
					page.getByRole( 'row', {
						name: `${ productName } × 1 $230.00 (incl. tax)`,
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', {
						name: 'Subtotal $230.00 (incl. tax)',
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', {
						name: 'Shipping Flat rate: $23.00 (incl. tax)',
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'row', {
						name: 'Total $253.00 (includes $33.00 Shipping Tax)',
					} )
				).toBeVisible();
			} );
		} );
	} );
} );
