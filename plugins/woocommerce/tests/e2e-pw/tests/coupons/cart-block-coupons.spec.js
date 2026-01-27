/**
 * External dependencies
 */
import {
	addAProductToCart,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';

const simpleProductName = 'Cart Coupons Product';
const singleProductFullPrice = '110.00';
const singleProductSalePrice = '55.00';
const coupons = [
	{
		code: '5fixedcart',
		discount_type: 'fixed_cart',
		amount: '5.00',
	},
	{
		code: '50percoff',
		discount_type: 'percent',
		amount: '50',
	},
	{
		code: '10fixedproduct',
		discount_type: 'fixed_product',
		amount: '10.00',
	},
];
const couponLimitedCode = '10fixedcartlimited';
const customerBilling = {
	email: 'john.doe.merchant.test@example.com',
};

let productId, orderId, limitedCouponId;

const test = baseTest.extend( {
	page: async ( { page }, use ) => {
		await addAProductToCart( page, productId );
		await page.goto( 'cart/' );
		await use( page );
	},
} );

test.describe(
	'Cart Block Applying Coupons',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		const couponBatchId = [];

		test.beforeAll( async ( { restApi } ) => {
			// make sure the currency is USD
			await restApi.put(
				`${ WC_API_PATH }/settings/general/woocommerce_currency`,
				{
					value: 'USD',
				}
			);
			// add a product
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: simpleProductName,
					type: 'simple',
					regular_price: singleProductFullPrice,
					sale_price: singleProductSalePrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
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
			// add limited coupon
			await restApi
				.post( `${ WC_API_PATH }/coupons`, {
					code: couponLimitedCode,
					discount_type: 'fixed_cart',
					amount: '10.00',
					usage_limit: 1,
					usage_count: 1,
				} )
				.then( ( response ) => {
					limitedCouponId = response.data.id;
				} );
			// add order with applied limited coupon
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					status: 'processing',
					billing: customerBilling,
					coupon_lines: [
						{
							code: couponLimitedCode,
						},
					],
				} )
				.then( ( response ) => {
					orderId = response.data.id;
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: [ productId ],
			} );
			await restApi.post( `${ WC_API_PATH }/coupons/batch`, {
				delete: [ ...couponBatchId, limitedCouponId ],
			} );
			await restApi.post( `${ WC_API_PATH }/orders/batch`, {
				delete: [ orderId ],
			} );
		} );

		test(
			'allows cart block to apply coupon of any type',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				const totals = [ '$50.00', '$27.50', '$45.00' ];

				// apply all coupon types
				for ( let i = 0; i < coupons.length; i++ ) {
					await page
						.getByRole( 'button', { name: 'Add coupons' } )
						.click();
					await page
						.getByLabel( 'Enter code' )
						.fill( coupons[ i ].code );
					await page.getByText( 'Apply', { exact: true } ).click();
					await expect(
						page
							.locator(
								'.wc-block-components-notice-banner__content'
							)
							.getByText(
								`Coupon code "${ coupons[ i ].code }" has been applied to your cart.`
							)
					).toBeVisible();
					await expect(
						page.locator(
							'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
						)
					).toHaveText( totals[ i ] );
					await page
						.getByLabel( `Remove coupon "${ coupons[ i ].code }"` )
						.click();
					await expect(
						page
							.locator(
								'.wc-block-components-notice-banner__content'
							)
							.getByText(
								`Coupon code "${ coupons[ i ].code }" has been removed from your cart.`
							)
					).toBeVisible();
				}
			}
		);

		test(
			'allows cart block to apply multiple coupons',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				const totals = [ '$50.00', '$22.50', '$12.50' ];
				const totalsReverse = [ '$17.50', '$45.00', '$55.00' ];
				const discounts = [ '-$5.00', '-$32.50', '-$42.50' ];

				// add all coupons and verify prices
				for ( let i = 0; i < coupons.length; i++ ) {
					await page
						.getByRole( 'button', { name: 'Add coupons' } )
						.click();
					await page
						.getByLabel( 'Enter code' )
						.fill( coupons[ i ].code );
					await page.getByText( 'Apply', { exact: true } ).click();
					await expect(
						page
							.locator(
								'.wc-block-components-notice-banner__content'
							)
							.getByText(
								`Coupon code "${ coupons[ i ].code }" has been applied to your cart.`
							)
					).toBeVisible();
					await expect(
						page.locator(
							'.wc-block-components-totals-discount > .wc-block-components-totals-item__value'
						)
					).toHaveText( discounts[ i ] );
					await expect(
						page.locator(
							'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
						)
					).toHaveText( totals[ i ] );
				}

				for ( let i = 0; i < coupons.length; i++ ) {
					await page
						.getByLabel( `Remove coupon "${ coupons[ i ].code }"` )
						.click();
					await expect(
						page
							.locator(
								'.wc-block-components-notice-banner__content'
							)
							.getByText(
								`Coupon code "${ coupons[ i ].code }" has been removed from your cart.`
							)
					).toBeVisible();
					await expect(
						page.locator(
							'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
						)
					).toHaveText( totalsReverse[ i ] );
				}
			}
		);

		test(
			'prevents cart block applying same coupon twice',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				// try to add two same coupons and verify the error message
				await page
					.getByRole( 'button', { name: 'Add coupons' } )
					.click();
				await page.getByLabel( 'Enter code' ).fill( coupons[ 0 ].code );
				await page.getByText( 'Apply', { exact: true } ).click();
				await expect(
					page
						.locator(
							'.wc-block-components-notice-banner__content'
						)
						.getByText(
							`Coupon code "${ coupons[ 0 ].code }" has been applied to your cart.`
						)
				).toBeVisible();
				await page
					.getByRole( 'button', { name: 'Add coupons' } )
					.click();
				await page.getByLabel( 'Enter code' ).fill( coupons[ 0 ].code );
				await page.getByText( 'Apply', { exact: true } ).click();
				await expect(
					page
						.getByRole( 'alert' )
						.getByText(
							`Coupon code "${ coupons[ 0 ].code }" has already been applied.`
						)
				).toBeVisible();
			}
		);

		test(
			'prevents cart block applying coupon with usage limit',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				// add coupon with usage limit
				await page
					.getByRole( 'button', { name: 'Add coupons' } )
					.click();
				await page.getByLabel( 'Enter code' ).fill( couponLimitedCode );
				await page.getByText( 'Apply', { exact: true } ).click();
				await expect(
					page
						.getByRole( 'alert' )
						.getByText(
							`Usage limit for coupon "${ couponLimitedCode }" has been reached.`
						)
				).toBeVisible();
			}
		);
	}
);
