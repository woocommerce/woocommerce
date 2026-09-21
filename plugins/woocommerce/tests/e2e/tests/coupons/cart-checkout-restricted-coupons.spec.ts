/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import {
	addAProductToCart,
	getOrderIdFromUrl,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';
import { faker } from '@faker-js/faker';

/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { setGatewayEnabled } from '../../utils/payment-gateways';
import {
	createClassicCartPage,
	createClassicCheckoutPage,
	CLASSIC_CART_PAGE,
	CLASSIC_CHECKOUT_PAGE,
} from '../../utils/pages';

const includedProductName = 'Included test product';
const includedCategoryName = 'Included Category';

// Coupon codes are global: suffix each with a random token so a run never
// collides with a concurrent or leftover coupon of the same code. WooCommerce
// stores codes lowercased, so keep the suffix lowercase for the error-message
// assertions (which echo the stored code) to match.
const couponSuffix = faker.string.alphanumeric( 8 ).toLowerCase();
const EXPIRED_COUPON = `expired-coupon-${ couponSuffix }`;
const INCLUDED_COUPON = `product-and-category-included-${ couponSuffix }`;
const EMAIL_RESTRICTED_COUPON = `email-restricted-${ couponSuffix }`;

const applyCoupon = async ( page: Page, couponCode: string ) => {
	const responsePromise = page.waitForResponse(
		( response ) =>
			response.url().includes( '?wc-ajax=apply_coupon' ) &&
			response.status() === 200
	);
	await page.getByPlaceholder( 'Coupon code' ).fill( couponCode );
	await page.getByRole( 'button', { name: 'Apply coupon' } ).click();
	await responsePromise;
};

const expandCouponForm = async ( page: Page ) => {
	await page
		.getByRole( 'button', { name: 'Enter your coupon code' } )
		.click();
	await expect( page.getByPlaceholder( 'Coupon code' ) ).toBeVisible();
};

const fillBillingDetails = async ( page: Page, email: string ) => {
	await page.getByLabel( 'First name' ).first().fill( 'Homer' );
	await page.getByLabel( 'Last name' ).first().fill( 'Simpson' );
	await page
		.getByLabel( 'Street address' )
		.first()
		.fill( '123 Evergreen Terrace' );
	await page.getByLabel( 'Town / City' ).first().fill( 'Springfield' );
	await page.getByLabel( 'ZIP Code' ).first().fill( '55555' );
	await page.getByLabel( 'Phone' ).first().fill( '555-555-5555' );
	await page.getByLabel( 'Email address' ).first().fill( email );
};

test.describe(
	'Cart & Checkout Restricted Coupons',
	{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
	() => {
		let firstProductId: number, firstCategoryId: number;
		let codWasEnabled: boolean;
		const couponBatchId: number[] = [];

		test.beforeAll( async ( { restApi } ) => {
			await createClassicCartPage();
			await createClassicCheckoutPage();

			await restApi.post( `${ WC_API_PATH }/settings/general/batch`, {
				update: [
					{ id: 'woocommerce_store_address', value: 'addr 1' },
					{ id: 'woocommerce_store_city', value: 'San Francisco' },
					{ id: 'woocommerce_default_country', value: 'US:CA' },
					{ id: 'woocommerce_store_postcode', value: '94107' },
				],
			} );
			await restApi.put(
				`${ WC_API_PATH }/settings/general/woocommerce_currency`,
				{ value: 'USD' }
			);
			// COD is enabled globally in site setup; guard defensively in case it
			// is somehow off, and restore its prior state in afterAll.
			codWasEnabled = await setGatewayEnabled( restApi, 'cod', true );
			// Free shipping is provided by the baseline zone in site setup, so this
			// spec no longer creates its own (catch-all) shipping zone — that zone
			// added a shipping method to every concurrent worker's cart.
			await restApi
				.post( `${ WC_API_PATH }/products/categories`, {
					name: includedCategoryName,
				} )
				.then( ( response: { data: { id: number } } ) => {
					firstCategoryId = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: includedProductName,
					type: 'simple',
					regular_price: '20.00',
					categories: [ { id: firstCategoryId } ],
				} )
				.then( ( response: { data: { id: number } } ) => {
					firstProductId = response.data.id;
				} );

			const residualCoupons = [
				{
					code: EXPIRED_COUPON,
					discount_type: 'fixed_cart',
					amount: '10.00',
					date_expires: '2020-01-01T00:00:00',
				},
				{
					code: INCLUDED_COUPON,
					discount_type: 'fixed_cart',
					amount: '10.00',
					product_ids: [ firstProductId ],
					product_categories: [ firstCategoryId ],
				},
				{
					code: EMAIL_RESTRICTED_COUPON,
					discount_type: 'fixed_cart',
					amount: '25.00',
					email_restrictions: [ 'homer@example.com' ],
					usage_limit_per_user: 1,
				},
			];
			await restApi
				.post( `${ WC_API_PATH }/coupons/batch`, {
					create: residualCoupons,
				} )
				.then( ( response: { data: { create: { id: number }[] } } ) => {
					for ( const created of response.data.create ) {
						couponBatchId.push( created.id );
					}
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit.
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ firstProductId }`,
				{ force: true }
			);
			await restApi.delete(
				`${ WC_API_PATH }/products/categories/${ firstCategoryId }`,
				{ force: true }
			);
			await restApi.post( `${ WC_API_PATH }/coupons/batch`, {
				delete: [ ...couponBatchId ],
			} );

			await setGatewayEnabled( restApi, 'cod', codWasEnabled );
		} );

		test( 'rejected coupon surfaces its error in cart and checkout', async ( {
			page,
			context,
		} ) => {
			await test.step( 'cart', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( CLASSIC_CART_PAGE.slug );
				await applyCoupon( page, EXPIRED_COUPON );
				await expect(
					page.getByText(
						`Coupon "${ EXPIRED_COUPON }" has expired.`
					)
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'checkout', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
				await expandCouponForm( page );
				await applyCoupon( page, EXPIRED_COUPON );
				await expect(
					page.getByText(
						`Coupon "${ EXPIRED_COUPON }" has expired.`
					)
				).toBeVisible();
			} );
		} );

		test( 'accepted coupon surfaces success in cart and checkout', async ( {
			page,
			context,
		} ) => {
			await test.step( 'cart', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( CLASSIC_CART_PAGE.slug );
				await applyCoupon( page, INCLUDED_COUPON );
				await expect(
					page.getByText( 'Coupon code applied successfully.' )
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'checkout', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
				await expandCouponForm( page );
				await applyCoupon( page, INCLUDED_COUPON );
				await expect(
					page.getByText( 'Coupon code applied successfully.' )
				).toBeVisible();
			} );
		} );

		test( 'email-restricted coupon can be used by the right customer but only once', async ( {
			page,
			restApi,
		} ) => {
			await addAProductToCart( page, firstProductId );
			await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
			await fillBillingDetails( page, 'homer@example.com' );
			await expandCouponForm( page );
			await applyCoupon( page, EMAIL_RESTRICTED_COUPON );
			await expect(
				page.getByText( 'Coupon code applied successfully.' )
			).toBeVisible();

			await page.getByRole( 'button', { name: 'Place order' } ).click();
			await expect(
				page.getByText( 'Your order has been received' )
			).toBeVisible();
			const newOrderId = getOrderIdFromUrl( page );

			await addAProductToCart( page, firstProductId );
			await page.goto( CLASSIC_CHECKOUT_PAGE.slug );
			await fillBillingDetails( page, 'homer@example.com' );
			await expandCouponForm( page );
			await applyCoupon( page, EMAIL_RESTRICTED_COUPON );
			await expect(
				page.getByText( 'Coupon code applied successfully.' )
			).toBeVisible();

			await page.getByRole( 'button', { name: 'Place order' } ).click();
			await expect(
				page.getByText(
					`Usage limit for coupon "${ EMAIL_RESTRICTED_COUPON }" has been reached.`
				)
			).toBeVisible();

			await restApi.delete( `${ WC_API_PATH }/orders/${ newOrderId }`, {
				force: true,
			} );
		} );
	}
);
