/**
 * External dependencies
 */
import { addAProductToCart } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';

const firstProductName = 'Coupon test product';
const coupons = [
	{
		code: 'fixed-cart-off',
		discount_type: 'fixed_cart',
		amount: '5.00',
	},
	{
		code: 'percent-off',
		discount_type: 'percent',
		amount: '50',
	},
	{
		code: 'fixed-product-off',
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
		let firstProductId;
		const couponBatchId = [];

		test.beforeAll( async ( { restApi } ) => {
			// make sure the currency is USD
			await restApi.put(
				`${ WC_API_PATH }/settings/general/woocommerce_currency`,
				{
					value: 'USD',
				}
			);
			// enable COD
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: true,
			} );
			// add product
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: firstProductName,
					type: 'simple',
					regular_price: '20.00',
				} )
				.then( ( response ) => {
					firstProductId = response.data.id;
				} );
			// add coupons
			await restApi
				.post( `${ WC_API_PATH }/coupons/batch`, {
					create: coupons,
				} )
				.then( ( response ) => {
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
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: false,
			} );
		} );

		for ( let i = 0; i < coupons.length; i++ ) {
			test(
				`allows applying coupon of type ${ coupons[ i ].discount_type }`,
				{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
				async ( { page, context } ) => {
					await test.step( 'Load cart page and apply coupons', async () => {
						await addAProductToCart( page, firstProductId );

						await page.goto( 'cart/' );
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
							page.locator( '.cart-discount .amount' )
						).toContainText( discounts[ i ] );
						// Checks that the cart total is updated
						await expect(
							page.locator( '.order-total .amount' )
						).toContainText( totals[ i ] );
					} );

					await context.clearCookies();

					await test.step( 'Load checkout page and apply coupons', async () => {
						await addAProductToCart( page, firstProductId );

						await page.goto( 'checkout' );
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
							page.locator( '.cart-discount .amount' )
						).toContainText( discounts[ i ] );
						await expect(
							page.locator( '.order-total .amount' )
						).toContainText( totals[ i ] );
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

					await page.goto( 'cart/' );
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
					await page.goto( 'cart/' );
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page
						.getByRole( 'button', { name: 'Apply coupon' } )
						.click();

					// error received
					await expect(
						page.getByText( 'Coupon code already applied!' )
					).toBeVisible();
					// check cart total
					await expect(
						page.locator( '.cart-discount .amount' )
					).toContainText( discounts[ 0 ] );
					await expect(
						page.locator( '.order-total .amount' )
					).toContainText( totals[ 0 ] );
				} );

				await context.clearCookies();

				await test.step( 'Load checkout page and try applying same coupon twice', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( 'checkout/' );
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
						page.getByText( 'Coupon code already applied!' )
					).toBeVisible();
					// check cart total
					await expect(
						page.locator( '.cart-discount .amount' )
					).toContainText( discounts[ 0 ] );
					await expect(
						page.locator( '.order-total .amount' )
					).toContainText( totals[ 0 ] );
				} );
			}
		);

		test(
			'allows applying multiple coupons',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page, context } ) => {
				await test.step( 'Load cart page and try applying multiple coupons', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( 'cart/' );
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

					// If not waiting the next coupon is not applied correctly. This should be temporary, we need a better way to handle this.
					await page.waitForTimeout( 2000 );

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
						page.locator( '.cart-discount .amount >> nth=0' )
					).toContainText( discounts[ 0 ] );
					await expect(
						page.locator( '.cart-discount .amount >> nth=1' )
					).toContainText( discounts[ 2 ] );
					await expect(
						page.locator( '.order-total .amount' )
					).toContainText( '$8.00' );
				} );

				await context.clearCookies();

				await test.step( 'Load checkout page and try applying multiple coupons', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( 'checkout/' );
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

					// If not waiting the next coupon is not applied correctly. This should be temporary, we need a better way to handle this.
					await page.waitForTimeout( 2000 );

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
						page.locator( '.cart-discount .amount >> nth=0' )
					).toContainText( discounts[ 0 ] );
					await expect(
						page.locator( '.cart-discount .amount >> nth=1' )
					).toContainText( discounts[ 2 ] );
					await expect(
						page.locator( '.order-total .amount' )
					).toContainText( '$8.00' );
				} );
			}
		);

		test(
			'restores total when coupons are removed',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page, context } ) => {
				await test.step( 'Load cart page and try restoring total when removed coupons', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( 'cart/' );
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page
						.getByRole( 'button', { name: 'Apply coupon' } )
						.click();

					// confirm numbers
					await expect(
						page.locator( '.cart-discount .amount' )
					).toContainText( discounts[ 0 ] );
					await expect(
						page.locator( '.order-total .amount' )
					).toContainText( totals[ 0 ] );

					await page.locator( 'a.woocommerce-remove-coupon' ).click();

					await expect(
						page.locator( '.order-total .amount' )
					).toContainText( '$20.00' );
				} );

				await context.clearCookies();

				await test.step( 'Load checkout page and try restoring total when removed coupons', async () => {
					await addAProductToCart( page, firstProductId );

					await page.goto( 'checkout/' );
					await page
						.locator( 'text=Click here to enter your code' )
						.click();
					await page
						.locator( '#coupon_code' )
						.fill( coupons[ 0 ].code );
					await page.locator( 'text=Apply coupon' ).click();

					// confirm numbers
					await expect(
						page.locator( '.cart-discount .amount' )
					).toContainText( discounts[ 0 ] );
					await expect(
						page.locator( '.order-total .amount' )
					).toContainText( totals[ 0 ] );

					await page.locator( 'a.woocommerce-remove-coupon' ).click();

					await expect(
						page.locator( '.order-total .amount' )
					).toContainText( '$20.00' );
				} );
			}
		);
	}
);
