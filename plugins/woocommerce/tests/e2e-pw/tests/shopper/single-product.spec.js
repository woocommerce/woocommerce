const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productPrice = '18.16';
const simpleProductName = 'Simple single product';
const variableProductName = 'Variable single product';
const variations = [
	{
		regular_price: productPrice,
		attributes: [
			{
				name: 'Size',
				option: 'Small',
			},
		],
	},
	{
		regular_price: ( +productPrice * 2 ).toString(),
		attributes: [
			{
				name: 'Size',
				option: 'Medium',
			},
		],
	},
	{
		regular_price: ( +productPrice * 3 ).toString(),
		attributes: [
			{
				name: 'Size',
				option: 'Large',
			},
		],
	},
	{
		regular_price: ( +productPrice * 4 ).toString(),
		attributes: [
			{
				name: 'Size',
				option: 'XLarge',
			},
		],
	},
];
const groupedProductName = 'Grouped single product';

let simpleProductId, simpleProduct2Id, variableProductId, groupedProductId;

test.describe( 'Single Product Page', () => {
	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add product
		await api
			.post( 'products', {
				name: simpleProductName,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				simpleProductId = response.data.id;
			} );
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ simpleProductId }`, {
			force: true,
		} );
	} );

	test( 'should be able to add simple products to the cart', async ( {
		page,
	} ) => {
		const slug = simpleProductName.replace( / /gi, '-' ).toLowerCase();
		await page.goto( `product/${ slug }` );

		await page.fill( 'input.qty', '5' );
		await page.click( 'text=Add to cart' );

		await expect( page.locator( '.woocommerce-message' ) ).toContainText(
			'have been added to your cart.'
		);

		await page.goto( 'cart/' );
		await expect( page.locator( 'td.product-name' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'input.qty' ) ).toHaveValue( '5' );
		await expect( page.locator( 'td.product-subtotal' ) ).toContainText(
			( 5 * +productPrice ).toString()
		);
	} );

	test( 'should be able to remove simple products from the cart', async ( {
		page,
	} ) => {
		await page.goto( `/shop/?add-to-cart=${ simpleProductId }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( 'cart/' );
		await page.click( 'a.remove' );

		await expect( page.locator( '.cart-empty' ) ).toContainText(
			'Your cart is currently empty.'
		);
	} );
} );

test.describe( 'Variable Product Page', () => {
	const slug = variableProductName.replace( / /gi, '-' ).toLowerCase();

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add product
		await api
			.post( 'products', {
				name: variableProductName,
				type: 'variable',
				attributes: [
					{
						name: 'Size',
						options: [ 'Small', 'Medium', 'Large', 'XLarge' ],
						visible: true,
						variation: true,
					},
				],
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
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ variableProductId }`, {
			force: true,
		} );
	} );

	test( 'should be able to add variation products to the cart', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );

		for ( const attr of variations ) {
			await page.selectOption( '#size', attr.attributes[ 0 ].option );
			await page.click( 'text=Add to cart' );
			await expect(
				page.locator( '.woocommerce-message' )
			).toContainText( 'has been added to your cart.' );
		}

		await page.goto( 'cart/' );
		await expect(
			page.locator( 'td.product-name >> nth=0' )
		).toContainText( variableProductName );
		await expect( page.locator( 'tr.order-total > td' ) ).toContainText(
			( +productPrice * 10 ).toString()
		);
	} );

	test( 'should be able to remove variation products from the cart', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );
		await page.selectOption( '#size', 'Large' );
		await page.click( 'text=Add to cart' );

		await page.goto( 'cart/' );
		await page.click( 'a.remove' );

		await expect( page.locator( '.cart-empty' ) ).toContainText(
			'Your cart is currently empty.'
		);
	} );
} );

test.describe( 'Grouped Product Page', () => {
	const slug = groupedProductName.replace( / /gi, '-' ).toLowerCase();
	const simpleProduct1 = simpleProductName + ' 1';
	const simpleProduct2 = simpleProductName + ' 2';

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add products
		await api
			.post( 'products', {
				name: simpleProduct1,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				simpleProductId = response.data.id;
			} );
		await api
			.post( 'products', {
				name: simpleProduct2,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				simpleProduct2Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: groupedProductName,
				type: 'grouped',
				grouped_products: [ simpleProductId, simpleProduct2Id ],
			} )
			.then( ( response ) => {
				groupedProductId = response.data.id;
			} );
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ simpleProductId }`, {
			force: true,
		} );
		await api.delete( `products/${ simpleProduct2Id }`, {
			force: true,
		} );
		await api.delete( `products/${ groupedProductId }`, {
			force: true,
		} );
	} );

	test( 'should be able to add grouped products to the cart', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );

		await page.click( 'text=Add to cart' );
		await expect( page.locator( '.woocommerce-error' ) ).toContainText(
			'Please choose the quantity of items you wish to add to your cart…'
		);

		await page.fill( 'div.quantity input.qty >> nth=0', '5' );
		await page.fill( 'div.quantity input.qty >> nth=1', '5' );
		await page.click( 'text=Add to cart' );
		await expect( page.locator( '.woocommerce-message' ) ).toContainText(
			`“${ simpleProduct1 }” and “${ simpleProduct2 }” have been added to your cart.`
		);

		await page.goto( 'cart/' );
		await expect(
			page.locator( 'td.product-name >> nth=0' )
		).toContainText( simpleProduct1 );
		await expect(
			page.locator( 'td.product-name >> nth=1' )
		).toContainText( simpleProduct2 );
		await expect( page.locator( 'tr.order-total > td' ) ).toContainText(
			( +productPrice * 10 ).toString()
		);
	} );

	test( 'should be able to remove grouped products from the cart', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );
		await page.fill( 'div.quantity input.qty >> nth=0', '1' );
		await page.fill( 'div.quantity input.qty >> nth=1', '1' );
		await page.click( 'text=Add to cart' );

		await page.goto( 'cart/' );
		await page.click( 'a.remove >> nth=1' );
		await page.click( 'a.remove >> nth=0' );

		await expect( page.locator( '.cart-empty' ) ).toContainText(
			'Your cart is currently empty.'
		);
	} );
} );
