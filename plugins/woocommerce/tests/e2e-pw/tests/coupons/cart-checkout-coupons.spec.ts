/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
import {
	addAProductToCart,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { setGatewayEnabled } from '../../utils/payment-gateways';
import {
	createClassicCartPage,
	createClassicCheckoutPage,
	CLASSIC_CART_PAGE,
	CLASSIC_CHECKOUT_PAGE,
} from '../../utils/pages';
import { setTaxCalculationEnabled } from '../../utils/taxes';

// Suffix the coupon codes with a per-run unique id so concurrent workers never
// create colliding global coupon codes.
const couponSuffix = faker.string.alphanumeric( {
	length: 6,
	casing: 'lower',
} );
const coupons = [
	{
		code: `fixed-cart-off-${ couponSuffix }`,
		discount_type: 'fixed_cart',
		amount: '5.00',
	},
	{
		code: `percent-off-${ couponSuffix }`,
		discount_type: 'percent',
		amount: '50',
	},
	{
		code: `fixed-product-off-${ couponSuffix }`,
		discount_type: 'fixed_product',
		amount: '7.00',
	},
];

const discounts = [ '$5.00', '$10.00', '$7.00' ];
const totals = [ '$15.00', '$10.00', '$13.00' ];

test.describe(
	'Cart & Checkout applying coupons',
	{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
	() => {
		let firstProductId: number;
		let codWasEnabled: boolean;
		let taxCalcWasEnabled: boolean;
		const couponBatchId: number[] = [];

		test.beforeAll( async ( { restApi } ) => {
			// Make sure the classic cart and checkout pages exist
			await createClassicCartPage();
			await createClassicCheckoutPage();

			taxCalcWasEnabled = await setTaxCalculationEnabled( restApi, false );

			// make sure the currency is USD
			await restApi.put(
				`${ WC_API_PATH }/settings/general/woocommerce_currency`,
				{
					value: 'USD',
				}
			);
			// COD is enabled globally in site setup; guard defensively in case it
			// is somehow off, and restore its prior state in afterAll.
			codWasEnabled = await setGatewayEnabled( restApi, 'cod', true );
			// add product
			await restApi
				.post(
					`${ WC_API_PATH }/products`,
					getFakeProduct( { regular_price: '20.00' } )
				)
				.then( ( response: { data: { id: number } } ) => {
					firstProductId = response.data.id;
				} );
			// add coupons
			await restApi
				.post( `${ WC_API_PATH }/coupons/batch`, {
					create: coupons,
				} )
				.then( ( response: { data: { create: { id: number }[] } } ) => {
					for ( let i = 0; i < response.data.create.length; i++ ) {
						couponBatchId.push( response.data.create[ i ].id );
					}
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ firstProductId }`,
				{
					force: true,
				}
			);
			await restApi.post( `${ WC_API_PATH }/coupons/batch`, {
				delete: [ ...couponBatchId ],
			} );
			await setGatewayEnabled( restApi, 'cod', codWasEnabled );
			await setTaxCalculationEnabled( restApi, taxCalcWasEnabled );
		} );

		for ( let i = 0; i < coupons.length; i++ ) {
			test(
				`allows applying coupon of type ${ coupons[ i ].discount_type }`,
				{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
				async ( { page, context } ) => {
					await test.step( 'Load cart page and apply coupons', async () => {
						await addAProductToCart( page, firstProductId );

						await page.goto( CLASSIC_CART_PAGE.slug );
						await page
							.locator( '#coupon_code' )
							.fill( coupons[ i ].code );
						await page
							.locator( '.blockOverlay' )
							.first()
							.waitFor( { state: 'hidden' } );
						await page
							.getByRole( 'button', { name: 'Apply coupon' } )
							.click();

						await expect(
							page.getByText(
								'Coupon code applied successfully.'
							)
						).toBeVisible();
						// Checks the coupon amount is credited properly
						await expect(
							page
								.locator( '.cart-discount .amount' )
								.filter( { hasText: discounts[ i ] } )
						).toBeVisible();
						// Checks that the cart total is updated
						await expect(
							page
								.locator( '.order-total .amount' )
								.filter( { hasText: totals[ i ] } )
						).toBeVisible();
					} );

					await context.clearCookies();

					await test.step( 'Load checkout page and apply coupons', async () => {
						await addAProductToCart( page, firstProductId );

						await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
						await page
							.locator( 'text=Click here to enter your code' )
							.click();
						await page
							.locator( '#coupon_code' )
							.fill( coupons[ i ].code );
						await page
							.locator( '.blockOverlay' )
							.first()
							.waitFor( { state: 'hidden' } );
						await page.locator( 'text=Apply coupon' ).click();

						await expect(
							page.getByText(
								'Coupon code applied successfully.'
							)
						).toBeVisible();
						await expect(
							page
								.locator( '.cart-discount .amount' )
								.filter( { hasText: discounts[ i ] } )
						).toBeVisible();
						await expect(
							page
								.locator( '.order-total .amount' )
								.filter( { hasText: totals[ i ] } )
						).toBeVisible();
					} );
				}
			);
		}

		test(
			'prevents applying same coupon twice',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page, context } ) => {
				await test.step( 'Load cart page and try applying same coupon twice', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( CLASSIC_CART_PAGE.slug );
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page
						.getByRole( 'button', { name: 'Apply coupon' } )
						.click();
					// successful first time
					await expect(
						page.getByText( 'Coupon code applied successfully.' )
					).toBeVisible();

					// try to apply the same coupon
					await page.goto( CLASSIC_CART_PAGE.slug );
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page
						.getByRole( 'button', { name: 'Apply coupon' } )
						.click();

					// error received
					await expect(
						page.getByText(
							`Coupon code "${ coupons[ 0 ].code }" already applied!`
						)
					).toBeVisible();
					// check cart total
					await expect(
						page
							.locator( '.cart-discount .amount' )
							.filter( { hasText: discounts[ 0 ] } )
					).toBeVisible();
					await expect(
						page
							.locator( '.order-total .amount' )
							.filter( { hasText: totals[ 0 ] } )
					).toBeVisible();
				} );

				await context.clearCookies();

				await test.step( 'Load checkout page and try applying same coupon twice', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
					await page
						.locator( 'text=Click here to enter your code' )
						.click();
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page.locator( 'text=Apply coupon' ).click();
					// successful first time
					await expect(
						page.getByText( 'Coupon code applied successfully.' )
					).toBeVisible();
					// try to apply the same coupon
					await page
						.locator( 'text=Click here to enter your code' )
						.click();
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page.locator( 'text=Apply coupon' ).click();
					// error received
					await expect(
						page.getByText(
							`Coupon code "${ coupons[ 0 ].code }" already applied!`
						)
					).toBeVisible();
					// check cart total
					await expect(
						page
							.locator( '.cart-discount .amount' )
							.filter( { hasText: discounts[ 0 ] } )
					).toBeVisible();
					await expect(
						page
							.locator( '.order-total .amount' )
							.filter( { hasText: totals[ 0 ] } )
					).toBeVisible();
				} );
			}
		);

		test(
			'allows applying multiple coupons',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page, context } ) => {
				await test.step( 'Load cart page and try applying multiple coupons', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
					await page
						.locator( 'text=Click here to enter your code' )
						.click();
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page
						.getByRole( 'button', { name: 'Apply coupon' } )
						.click();
					// successful
					await expect(
						page.getByText( 'Coupon code applied successfully.' )
					).toBeVisible();

					await page
						.locator( 'text=Click here to enter your code' )
						.click();
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 2 ].code );
					await page
						.getByRole( 'button', { name: 'Apply coupon' } )
						.click();
					// successful
					await expect(
						page.getByText( 'Coupon code applied successfully.' )
					).toBeVisible();
					// check cart total
					await expect(
						page
							.locator( '.cart-discount .amount >> nth=0' )
							.filter( { hasText: discounts[ 0 ] } )
					).toBeVisible();
					await expect(
						page
							.locator( '.cart-discount .amount >> nth=1' )
							.filter( { hasText: discounts[ 2 ] } )
					).toBeVisible();
					await expect(
						page
							.locator( '.order-total .amount' )
							.filter( { hasText: '$8.00' } )
					).toBeVisible();
				} );

				await context.clearCookies();

				await test.step( 'Load checkout page and try applying multiple coupons', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
					await page
						.locator( 'text=Click here to enter your code' )
						.click();
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page.locator( 'text=Apply coupon' ).click();
					// successful
					await expect(
						page.getByText( 'Coupon code applied successfully.' )
					).toBeVisible();

					await page
						.locator( 'text=Click here to enter your code' )
						.click();
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 2 ].code );
					await page.locator( 'text=Apply coupon' ).click();
					// successful
					await expect(
						page.getByText( 'Coupon code applied successfully.' )
					).toBeVisible();
					// check cart total
					await expect(
						page
							.locator( '.cart-discount .amount >> nth=0' )
							.filter( { hasText: discounts[ 0 ] } )
					).toBeVisible();
					await expect(
						page
							.locator( '.cart-discount .amount >> nth=1' )
							.filter( { hasText: discounts[ 2 ] } )
					).toBeVisible();
					await expect(
						page
							.locator( '.order-total .amount' )
							.filter( { hasText: '$8.00' } )
					).toBeVisible();
				} );
			}
		);

		test(
			'restores total when coupons are removed',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page, context } ) => {
				await test.step( 'Load cart page and try restoring total when removed coupons', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( CLASSIC_CART_PAGE.slug );
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page
						.getByRole( 'button', { name: 'Apply coupon' } )
						.click();
					await expect(
						page.getByText( 'Coupon code applied successfully.' )
					).toBeVisible();

					// confirm numbers
					await expect(
						page
							.locator( '.cart-discount .amount' )
							.filter( { hasText: discounts[ 0 ] } )
					).toBeVisible();
					await expect(
						page
							.locator( '.order-total .amount' )
							.filter( { hasText: totals[ 0 ] } )
					).toBeVisible();

					await page.locator( 'a.woocommerce-remove-coupon' ).click();

					await expect(
						page
							.locator( '.order-total .amount' )
							.filter( { hasText: '$20.00' } )
					).toBeVisible();
				} );

				await context.clearCookies();

				await test.step( 'Load checkout page and try restoring total when removed coupons', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
					await page
						.locator( 'text=Click here to enter your code' )
						.click();
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page.locator( 'text=Apply coupon' ).click();

					// confirm numbers
					await expect(
						page
							.locator( '.cart-discount .amount' )
							.filter( { hasText: discounts[ 0 ] } )
					).toBeVisible();
					await expect(
						page
							.locator( '.order-total .amount' )
							.filter( { hasText: totals[ 0 ] } )
					).toBeVisible();

					await page.locator( 'a.woocommerce-remove-coupon' ).click();

					await expect(
						page
							.locator( '.order-total .amount' )
							.filter( { hasText: '$20.00' } )
					).toBeVisible();
				} );
			}
		);
	}
);
