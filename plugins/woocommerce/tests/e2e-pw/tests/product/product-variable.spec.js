/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';
import { checkCartContent } from '../../utils/cart';
import { resetValue, updateIfNeeded } from '../../utils/settings';

const productPrice = 18.16;
const cartDialogMessage =
	'Please select some product options before adding this product to your cart.';
const variations1 = [
	{
		regular_price: productPrice.toString(),
		attributes: [
			{
				name: 'Size',
				option: 'Small',
			},
		],
	},
	{
		regular_price: ( productPrice + 2 ).toString(),
		attributes: [
			{
				name: 'Size',
				option: 'Medium',
			},
		],
	},
	{
		regular_price: ( productPrice + 3 ).toString(),
		attributes: [
			{
				name: 'Size',
				option: 'Large',
			},
		],
	},
	{
		regular_price: ( productPrice + 4 ).toString(),
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
		regular_price: productPrice.toString(),
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
		regular_price: productPrice.toString(),
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
		regular_price: ( productPrice + 2 ).toString(),
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
		regular_price: ( productPrice + 2 ).toString(),
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

async function selectVariation(
	page,
	variations,
	price,
	productName,
	addToCart = true
) {
	for ( const v of variations ) {
		await page.locator( `#${ v.locatorId }` ).selectOption( v.value );
		const selectedValue = await page
			.locator( `#${ v.locatorId }` )
			.evaluate( ( select ) => select.value );
		await expect( selectedValue ).toBe( v.value ); // Use lowercase if the value attribute is lowercase
	}

	await expect(
		page.getByRole( 'alert' ).filter( { hasText: price } )
	).toBeVisible();

	if ( addToCart ) {
		await page
			.getByRole( 'button', { name: 'Add to cart', exact: true } )
			.click();
		await expect(
			page.getByText( `“${ productName }” has been added to your cart.` )
		).toBeVisible();
	}
}

test.describe(
	'Variable Product Page',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		const variableProductName = `Variable single product ${ Date.now() }`;
		const slug = variableProductName.replace( / /gi, '-' ).toLowerCase();
		let variableProductId;
		let calcTaxesState;

		test.beforeAll( async ( { restApi } ) => {
			calcTaxesState = await updateIfNeeded(
				`general/woocommerce_calc_taxes`,
				'no'
			);

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

			await resetValue(
				`general/woocommerce_calc_taxes`,
				calcTaxesState
			);
		} );

		test( 'should be able to add variation products to the cart', async ( {
			page,
		} ) => {
			await page.goto( `product/${ slug }` );

			for ( let i = 0; i < variations1.length; i++ ) {
				// eslint-disable-next-line playwright/no-conditional-in-test
				if ( i > 0 ) {
					await page
						.getByRole( 'link', { name: 'Clear options' } )
						.click();
				}
				await selectVariation(
					page,
					[
						{
							locatorId: 'size',
							value: variations1[ i ].attributes[ 0 ].option,
						},
					],
					variations1[ i ].regular_price,
					variableProductName
				);
			}

			await page.getByRole( 'link', { name: 'View cart' } ).click();

			const rowsLocator = 'tr.wc-block-cart-items__row';

			await expect( page.locator( rowsLocator ) ).toHaveCount( 4 );

			for ( const row of await page.locator( rowsLocator ).all() ) {
				await expect( row ).toContainText( variableProductName );
				await expect(
					row.getByRole( 'spinbutton', { name: 'Quantity' } )
				).toHaveValue( '1' );
			}

			const expectedTotal = variations1.reduce( ( sum, variation ) => {
				const price = parseFloat( variation.regular_price );
				return sum + price;
			}, 0 );

			await expect(
				page.locator( '.wc-block-components-totals-item__value' ).last()
			).toContainText( expectedTotal.toString() );
		} );

		test( 'should be able to remove variation products from the cart', async ( {
			page,
		} ) => {
			await page.goto( `product/${ slug }` );

			await selectVariation(
				page,
				[
					{
						locatorId: 'size',
						value: variations1[ 1 ].attributes[ 0 ].option,
					},
				],
				variations1[ 1 ].regular_price,
				variableProductName
			);

			await page.goto( 'cart/' );
			await page
				.getByRole( 'button', { name: 'Remove' } )
				.first()
				.click();

			await checkCartContent( false, page, [], 0 );
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
		let calcTaxesState;

		test.beforeAll( async ( { restApi } ) => {
			calcTaxesState = await updateIfNeeded(
				`general/woocommerce_calc_taxes`,
				'no'
			);

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

			await resetValue(
				`general/woocommerce_calc_taxes`,
				calcTaxesState
			);
		} );

		test(
			'Shopper can change variable attributes to the same value',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				await page.goto( `product/${ slug }` );

				await page.locator( '#size' ).selectOption( 'Small' );
				await page.locator( '#colour' ).selectOption( 'Red' );

				let totalPrice = await page
					.locator( '.woocommerce-variation-price' )
					.last()
					.locator( 'bdi' )
					.textContent();
				totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
				await expect( totalPrice ).toBeGreaterThanOrEqual(
					productPrice
				);
				await expect( totalPrice ).toBeLessThanOrEqual(
					productPrice * 1.25
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
					productPrice
				);
				await expect( totalPrice ).toBeLessThanOrEqual(
					productPrice * 1.25
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
					productPrice
				);
				await expect( totalPrice ).toBeLessThanOrEqual(
					productPrice * 1.25
				);
			}
		);

		test(
			'Shopper can change attributes to combination with dimensions and weight',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				await page.goto( `product/${ slug }` );

				await selectVariation(
					page,
					[
						{
							locatorId: 'colour',
							value: 'Red',
						},
						{
							locatorId: 'size',
							value: 'Small',
						},
					],
					productPrice,
					variableProductName,
					false
				);

				await expect(
					page.locator(
						'.woocommerce-product-attributes-item--weight'
					)
				).toContainText( '100 lbs' );
				await expect(
					page.locator(
						'.woocommerce-product-attributes-item--dimensions'
					)
				).toContainText( '5 × 10 × 10 in' );

				await selectVariation(
					page,
					[
						{
							locatorId: 'size',
							value: 'XLarge',
						},
					],
					productPrice + 2,
					variableProductName,
					false
				);

				await expect(
					page.locator(
						'.woocommerce-product-attributes-item--weight'
					)
				).toContainText( '400 lbs' );
				await expect(
					page.locator(
						'.woocommerce-product-attributes-item--dimensions'
					)
				).toContainText( '20 × 40 × 30 in' );
			}
		);

		test(
			'Shopper can change variable product attributes to variation with a different price',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				await page.goto( `product/${ slug }` );

				await selectVariation(
					page,
					[
						{
							locatorId: 'colour',
							value: 'Red',
						},
						{
							locatorId: 'size',
							value: 'Small',
						},
					],
					productPrice,
					variableProductName,
					false
				);

				await selectVariation(
					page,
					[
						{
							locatorId: 'size',
							value: 'Medium',
						},
					],
					productPrice,
					variableProductName,
					false
				);

				await expect(
					page.locator(
						'.woocommerce-product-attributes-item--weight'
					)
				).toContainText( '100 lbs' );
				await expect(
					page.locator(
						'.woocommerce-product-attributes-item--dimensions'
					)
				).toContainText( '5 × 10 × 10 in' );

				await selectVariation(
					page,
					[
						{
							locatorId: 'size',
							value: 'Large',
						},
					],
					productPrice + 2,
					variableProductName,
					false
				);
			}
		);

		test(
			'Shopper can reset variations',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				await page.goto( `product/${ slug }` );

				await page.locator( '#colour' ).selectOption( 'Red' );
				await page.locator( '#size' ).selectOption( 'Small' );

				let totalPrice = await page
					.locator( '.woocommerce-variation-price' )
					.last()
					.locator( 'bdi' )
					.textContent();
				totalPrice = Number( totalPrice.replace( /[^\d.-]/g, '' ) );
				await expect( totalPrice ).toBeGreaterThanOrEqual(
					productPrice
				);
				await expect( totalPrice ).toBeLessThanOrEqual(
					productPrice * 1.25
				);

				await page.locator( 'a.reset_variations' ).click();

				// Verify the reset by attempting to add the product to the cart
				page.on( 'dialog', async ( dialog ) => {
					expect( dialog.message() ).toContain( cartDialogMessage );
					await dialog.dismiss();
				} );
				await page.locator( '.single_add_to_cart_button' ).click();
			}
		);
	}
);
