/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';

const productPrice = '18.16';
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

test.describe(
	'Variable Product Page',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		const variableProductName = `Variable single product ${ Date.now() }`;
		const slug = variableProductName.replace( / /gi, '-' ).toLowerCase();
		let variableProductId, totalPrice;

		test.beforeAll( async ( { restApi } ) => {
			// add product
			await restApi
				.post( `${ WC_API_PATH }/products`, {
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
						await restApi.post(
							`${ WC_API_PATH }/products/${ variableProductId }/variations`,
							variations1[ key ]
						);
					}
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ variableProductId }`,
				{
					force: true,
				}
			);
		} );

		test( 'should be able to add variation products to the cart', async ( {
			page,
		} ) => {
			await page.goto( `product/${ slug }` );

			for ( const attr of variations1 ) {
				await page
					.locator( '#size' )
					.selectOption( attr.attributes[ 0 ].option );
				await page.waitForTimeout( 300 );
				await page
					.getByRole( 'button', { name: 'Add to cart', exact: true } )
					.click();
				await expect(
					page.getByText( 'has been added to your cart' )
				).toBeVisible();
			}

			await page.goto( 'cart/' );
			await expect(
				page.locator( 'td.product-name >> nth=0' )
			).toContainText( variableProductName );

			totalPrice = await page
				.getByRole( 'row', { name: 'Total' } )
				.last()
				.locator( 'td' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice * 10 )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 10 * 1.25 )
			);
		} );

		test( 'should be able to remove variation products from the cart', async ( {
			page,
		} ) => {
			await page.goto( `product/${ slug }` );
			await page.locator( '#size' ).selectOption( 'Large' );
			await page.waitForTimeout( 300 );
			await page
				.getByRole( 'button', { name: 'Add to cart', exact: true } )
				.click();

			await page.goto( 'cart/' );
			await page.locator( 'a.remove' ).click();

			await expect(
				page.getByText( 'Your cart is currently empty' )
			).toBeVisible();
		} );
	}
);

test.describe(
	'Shopper > Update variable product',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		const variableProductName = `Variable single product ${ Date.now() }`;
		const slug = variableProductName.replace( / /gi, '-' ).toLowerCase();
		let variableProductId;

		test.beforeAll( async ( { restApi } ) => {
			// add product
			await restApi
				.post( `${ WC_API_PATH }/products`, {
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
						await restApi.post(
							`${ WC_API_PATH }/products/${ variableProductId }/variations`,
							variations2[ key ]
						);
					}
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ variableProductId }`,
				{
					force: true,
				}
			);
		} );

		test( 'Shopper can change variable attributes to the same value', async ( {
			page,
		} ) => {
			await page.goto( `product/${ slug }` );

			await page.locator( '#size' ).selectOption( 'Small' );

			await page.locator( '#colour' ).selectOption( 'Red' );

			await page.waitForTimeout( 300 );

			// handling assertion this way because taxes may or may not be enabled
			let totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 1.25 )
			);

			await page.locator( '#colour' ).selectOption( 'Green' );

			// handling assertion this way because taxes may or may not be enabled
			totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 1.25 )
			);

			await page.locator( '#colour' ).selectOption( 'Blue' );

			// handling assertion this way because taxes may or may not be enabled
			totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 1.25 )
			);
		} );

		test( 'Shopper can change attributes to combination with dimensions and weight', async ( {
			page,
		} ) => {
			await page.goto( `product/${ slug }` );

			await page.locator( '#colour' ).selectOption( 'Red' );

			await page.locator( '#size' ).selectOption( 'Small' );

			await page.waitForTimeout( 300 );

			let totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 1.25 )
			);

			await expect(
				page.locator( '.woocommerce-product-attributes-item--weight' )
			).toContainText( '100 lbs' );
			await expect(
				page.locator(
					'.woocommerce-product-attributes-item--dimensions'
				)
			).toContainText( '5 × 10 × 10 in' );

			await page.locator( '#size' ).selectOption( 'XLarge' );

			await page.waitForTimeout( 300 );

			totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice * 2 )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 2 * 1.25 )
			);

			await expect(
				page.locator( '.woocommerce-product-attributes-item--weight' )
			).toContainText( '400 lbs' );
			await expect(
				page.locator(
					'.woocommerce-product-attributes-item--dimensions'
				)
			).toContainText( '20 × 40 × 30 in' );
		} );

		test( 'Shopper can change variable product attributes to variation with a different price', async ( {
			page,
		} ) => {
			await page.goto( `product/${ slug }` );

			await page.locator( '#colour' ).selectOption( 'Red' );

			await page.locator( '#size' ).selectOption( 'Small' );

			await page.waitForTimeout( 300 );

			let totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 1.25 )
			);

			await page.locator( '#size' ).selectOption( 'Medium' );

			await page.waitForTimeout( 300 );

			totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 1.25 )
			);

			await page.locator( '#size' ).selectOption( 'Large' );

			await page.waitForTimeout( 300 );

			totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice * 2 )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 2 * 1.25 )
			);

			await page.locator( '#size' ).selectOption( 'XLarge' );

			await page.waitForTimeout( 300 );

			totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice * 2 )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 2 * 1.25 )
			);
		} );

		test( 'Shopper can reset variations', async ( { page } ) => {
			await page.goto( `product/${ slug }` );

			await page.locator( '#colour' ).selectOption( 'Red' );

			await page.locator( '#size' ).selectOption( 'Small' );

			await page.waitForTimeout( 300 );

			let totalPrice = await page
				.locator( '.woocommerce-variation-price' )
				.last()
				.locator( 'bdi' )
				.textContent();
			totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
			await expect( totalPrice ).toBeGreaterThanOrEqual(
				Number( productPrice )
			);
			await expect( totalPrice ).toBeLessThanOrEqual(
				Number( productPrice * 1.25 )
			);

			await page.locator( 'a.reset_variations' ).click();

			// Verify the reset by attempting to add the product to the cart
			page.on( 'dialog', async ( dialog ) => {
				expect( dialog.message() ).toContain( cartDialogMessage );
				await dialog.dismiss();
			} );
			await page.locator( '.single_add_to_cart_button' ).click();
		} );
	}
);
