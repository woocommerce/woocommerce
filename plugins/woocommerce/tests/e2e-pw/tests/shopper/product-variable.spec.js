const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productPrice = '18.16';
const variableProductName = 'Variable single product';
const cartDialogMessage =
	'Please select some product options before adding this product to your cart.';
const variations1 = [
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
const variations2 = [
	{
		attributes: [
			{
				name: 'Colour',
				option: 'Red',
			},
		],
	},
	{
		attributes: [
			{
				name: 'Colour',
				option: 'Blue',
			},
		],
	},
	{
		attributes: [
			{
				name: 'Colour',
				option: 'Green',
			},
		],
	},
	{
		regular_price: productPrice,
		weight: '100',
		dimensions: {
			length: '5',
			width: '10',
			height: '10',
		},
		attributes: [
			{
				name: 'Size',
				option: 'Small',
			},
		],
	},
	{
		regular_price: productPrice,
		weight: '100',
		dimensions: {
			length: '5',
			width: '10',
			height: '10',
		},
		attributes: [
			{
				name: 'Size',
				option: 'Medium',
			},
		],
	},
	{
		regular_price: ( +productPrice * 2 ).toString(),
		weight: '200',
		dimensions: {
			length: '10',
			width: '20',
			height: '15',
		},
		attributes: [
			{
				name: 'Size',
				option: 'Large',
			},
		],
	},
	{
		regular_price: ( +productPrice * 2 ).toString(),
		weight: '400',
		dimensions: {
			length: '20',
			width: '40',
			height: '30',
		},
		attributes: [
			{
				name: 'Size',
				option: 'XLarge',
			},
		],
	},
];

let variableProductId;

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
			.then( async ( response ) => {
				variableProductId = response.data.id;
				for ( const key in variations1 ) {
					await api.post(
						`products/${ variableProductId }/variations`,
						variations1[ key ]
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

		for ( const attr of variations1 ) {
			await page
				.locator( '#size' )
				.selectOption( attr.attributes[ 0 ].option );
			await page.getByRole( 'button', { name: 'Add to cart' } ).click();
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
		await page.locator( '#size' ).selectOption( 'Large' );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();

		await page.goto( 'cart/' );
		await page.locator( 'a.remove' ).click();

		await expect( page.locator( '.cart-empty' ) ).toContainText(
			'Your cart is currently empty.'
		);
	} );
} );

test.describe( 'Shopper > Update variable product', () => {
	let variableProductId;
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
					{
						name: 'Colour',
						options: [ 'Red', 'Green', 'Blue' ],
						visible: true,
						variation: true,
					},
				],
			} )
			.then( async ( response ) => {
				variableProductId = response.data.id;
				for ( const key in variations2 ) {
					await api.post(
						`products/${ variableProductId }/variations`,
						variations2[ key ]
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

	test( 'Shopper can change variable attributes to the same value', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );

		await page.locator( '#size' ).selectOption( 'Small' );

		await page.locator( '#colour' ).selectOption( 'Red' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.locator( '#colour' ).selectOption( 'Green' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.locator( '#colour' ).selectOption( 'Blue' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );
	} );

	test( 'Shopper can change attributes to combination with dimensions and weight', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );

		await page.locator( '#colour' ).selectOption( 'Red' );

		await page.locator( '#size' ).selectOption( 'Small' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );
		await expect(
			page.locator( '.woocommerce-product-attributes-item--weight' )
		).toContainText( '100 kg' );
		await expect(
			page.locator( '.woocommerce-product-attributes-item--dimensions' )
		).toContainText( '5 × 10 × 10 cm' );

		await page.locator( '#size' ).selectOption( 'XLarge' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( ( +productPrice * 2 ).toString() );
		await expect(
			page.locator( '.woocommerce-product-attributes-item--weight' )
		).toContainText( '400 kg' );
		await expect(
			page.locator( '.woocommerce-product-attributes-item--dimensions' )
		).toContainText( '20 × 40 × 30 cm' );
	} );

	test( 'Shopper can change variable product attributes to variation with a different price', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );

		await page.locator( '#colour' ).selectOption( 'Red' );

		await page.locator( '#size' ).selectOption( 'Small' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.locator( '#size' ).selectOption( 'Medium' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.locator( '#size' ).selectOption( 'Large' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( ( +productPrice * 2 ).toString() );

		await page.locator( '#size' ).selectOption( 'XLarge' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( ( +productPrice * 2 ).toString() );
	} );

	test( 'Shopper can reset variations', async ( { page } ) => {
		await page.goto( `product/${ slug }` );

		await page.locator( '#colour' ).selectOption( 'Red' );

		await page.locator( '#size' ).selectOption( 'Small' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.locator( 'a.reset_variations' ).click();

		// Verify the reset by attempting to add the product to the cart
		page.on( 'dialog', async ( dialog ) => {
			expect( dialog.message() ).toContain( cartDialogMessage );
			await dialog.dismiss();
		} );
		await page.locator( '.single_add_to_cart_button' ).click();
	} );
} );
