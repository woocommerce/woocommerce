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
import { tags, test, expect } from '../../fixtures/fixtures';
import { createClassicCartPage, CLASSIC_CART_PAGE } from '../../utils/pages';
import { updateIfNeeded } from '../../utils/settings';

const firstProductName = 'Coupon test product';
const coupon = {
	code: 'fixed-cart-off',
	discount_type: 'fixed_cart',
	amount: '5.00',
};

test.describe(
	'Cart applying coupons',
	{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
	() => {
		let firstProductId: number;
		let couponId: number;

		test.beforeAll( async ( { restApi } ) => {
			// Make sure the classic cart page exists.
			await createClassicCartPage();

			await updateIfNeeded( 'general/woocommerce_calc_taxes', 'no' );

			// Make sure the currency is USD.
			await restApi.put(
				`${ WC_API_PATH }/settings/general/woocommerce_currency`,
				{
					value: 'USD',
				}
			);

			// Add product.
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: firstProductName,
					type: 'simple',
					regular_price: '20.00',
				} )
				.then( ( response: { data: { id: number } } ) => {
					firstProductId = response.data.id;
				} );

			// Add coupon.
			await restApi
				.post( `${ WC_API_PATH }/coupons`, coupon )
				.then( ( response: { data: { id: number } } ) => {
					couponId = response.data.id;
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit.
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ firstProductId }`,
				{
					force: true,
				}
			);
			await restApi.delete( `${ WC_API_PATH }/coupons/${ couponId }`, {
				force: true,
			} );
		} );

		test( 'applies a coupon via the classic cart form', async ( {
			page,
		} ) => {
			await addAProductToCart( page, firstProductId );

			await page.goto( CLASSIC_CART_PAGE.slug );
			await page.locator( '#coupon_code' ).fill( coupon.code );
			await page
				.locator( '.blockOverlay' )
				.first()
				.waitFor( { state: 'hidden' } );
			await page.getByRole( 'button', { name: 'Apply coupon' } ).click();
			await page
				.locator( '.blockOverlay' )
				.first()
				.waitFor( { state: 'hidden' } );

			// The form is wired end-to-end: success notice renders...
			await expect(
				page.getByText( 'Coupon code applied successfully.' )
			).toBeVisible();
			// ...and a discount line appears. Value is asserted in PHPUnit, not here.
			await expect(
				page.locator( '.cart-discount .amount' )
			).toBeVisible();
		} );
	}
);
