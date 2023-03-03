const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const variableProductName = 'Variable Product Updates';
const productPrice = '11.16';
const variations = [
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

const cartDialogMessage =
	'Please select some product options before adding this product to your cart.';

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
		context.clearCookies();
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

		await page.selectOption( '#size', 'Small' );

		await page.selectOption( '#colour', 'Red' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.selectOption( '#colour', 'Green' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.selectOption( '#colour', 'Blue' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );
	} );

	test( 'Shopper can change attributes to combination with dimentions and weight', async ( {
		page,
	} ) => {
		await page.goto( `product/${ slug }` );

		await page.selectOption( '#colour', 'Red' );

		await page.selectOption( '#size', 'Small' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );
		await expect(
			page.locator( '.woocommerce-product-attributes-item--weight' )
		).toContainText( '100 kg' );
		await expect(
			page.locator( '.woocommerce-product-attributes-item--dimensions' )
		).toContainText( '5 × 10 × 10 cm' );

		await page.selectOption( '#size', 'XLarge' );
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

		await page.selectOption( '#colour', 'Red' );

		await page.selectOption( '#size', 'Small' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.selectOption( '#size', 'Medium' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.selectOption( '#size', 'Large' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( ( +productPrice * 2 ).toString() );

		await page.selectOption( '#size', 'XLarge' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( ( +productPrice * 2 ).toString() );
	} );

	test( 'Shopper can reset variations', async ( { page } ) => {
		await page.goto( `product/${ slug }` );

		await page.selectOption( '#colour', 'Red' );

		await page.selectOption( '#size', 'Small' );
		await expect(
			page.locator( '.woocommerce-variation-price' )
		).toContainText( productPrice );

		await page.click( 'a.reset_variations' );

		// Verify the reset by attempting to add the product to the cart
		page.on( 'dialog', async ( dialog ) => {
			expect( dialog.message() ).toContain( cartDialogMessage );
			await dialog.dismiss();
		} );
		await page.click( '.single_add_to_cart_button' );
	} );
} );
