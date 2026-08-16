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
const coupon = {
	code: '5fixedcart',
	discount_type: 'fixed_cart',
	amount: '5.00',
};
const couponLimitedCode = '10fixedcartlimited';
const customerBilling = {
	email: 'john.doe.merchant.test@example.com',
};

let productId: number,
	orderId: number,
	couponId: number,
	limitedCouponId: number;

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
					regular_price: '110.00',
				} )
				.then( ( response: { data: { id: number } } ) => {
					productId = response.data.id;
				} );
			// add coupon
			await restApi
				.post( `${ WC_API_PATH }/coupons`, coupon )
				.then( ( response: { data: { id: number } } ) => {
					couponId = response.data.id;
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
				.then( ( response: { data: { id: number } } ) => {
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
				.then( ( response: { data: { id: number } } ) => {
					orderId = response.data.id;
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: [ productId ],
			} );
			await restApi.post( `${ WC_API_PATH }/coupons/batch`, {
				delete: [ couponId, limitedCouponId ],
			} );
			await restApi.post( `${ WC_API_PATH }/orders/batch`, {
				delete: [ orderId ],
			} );
		} );

		test( 'applies a coupon via the cart block form', async ( {
			page,
		} ) => {
			await page.getByRole( 'button', { name: 'Add coupons' } ).click();
			await page.getByLabel( 'Enter code' ).fill( coupon.code );
			await page.getByText( 'Apply', { exact: true } ).click();

			// The block form is wired end-to-end: success notice renders...
			await expect(
				page
					.locator( '.wc-block-components-notice-banner__content' )
					.getByText(
						`Coupon code "${ coupon.code }" has been applied to your cart.`
					)
			).toBeVisible();
			// ...and a discount line appears. Value is asserted in PHPUnit, not here.
			await expect(
				page.locator( '.wc-block-components-totals-discount' )
			).toBeVisible();
		} );

		test(
			'prevents cart block applying coupon with usage limit',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
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
