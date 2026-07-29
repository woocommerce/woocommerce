/**
 * External dependencies
 */
import { expect, test as base, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';
import { CheckoutPage } from '../checkout/checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Coupon', () => {
	test.beforeEach( async () => {
		await wpCLI(
			'wc shop_coupon create --code=single-use-coupon --discount_type=percent --amount=10 --usage_limit=1 --user=1'
		);
	} );

	test( 'Logged in user can apply a single-use coupon only once', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
	} ) => {
		await test.step( 'Logged in user cannot apply single-use coupon twice', async () => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
			await frontendUtils.goToCheckout();

			await page.getByRole( 'button', { name: 'Add coupons' } ).click();
			await page.getByLabel( 'Enter code' ).fill( 'single-use-coupon' );
			await page.getByRole( 'button', { name: 'Apply' } ).click();

			const removeCouponButton = page.getByLabel(
				'Remove coupon "single-use-coupon"'
			);
			await expect( removeCouponButton ).toBeVisible();
			await removeCouponButton.click();
			await removeCouponButton.waitFor( { state: 'hidden' } );

			// eslint-disable-next-line playwright/no-nested-step
			await test.step( 'Logged in user can apply single-use coupon and place order', async () => {
				await frontendUtils.goToCart();

				await page
					.getByRole( 'button', {
						name: 'Add coupons',
					} )
					.click();
				await page
					.getByLabel( 'Enter code' )
					.fill( 'single-use-coupon' );
				await page.getByRole( 'button', { name: 'Apply' } ).click();

				await expect(
					page.getByLabel( 'Remove coupon "single-use-coupon"' )
				).toBeVisible();

				await page
					.getByLabel( 'Remove coupon "single-use-coupon"' )
					.click();

				await expect(
					page.getByLabel( 'Remove coupon "single-use-coupon"' )
				).toBeHidden();

				await frontendUtils.goToCheckout();
				await page
					.getByRole( 'button', {
						name: 'Add coupons',
					} )
					.click();
				await page
					.getByLabel( 'Enter code' )
					.fill( 'single-use-coupon' );
				await page.getByRole( 'button', { name: 'Apply' } ).click();

				await expect(
					page.getByLabel( 'Remove coupon "single-use-coupon"' )
				).toBeVisible();

				await checkoutPageObject.fillInCheckoutWithTestData();
				await checkoutPageObject.placeOrder();

				await expect(
					page.getByText( 'Thank you. Your order has been received.' )
				).toBeVisible();

				await expect(
					page.getByRole( 'rowheader', {
						name: 'Discount:',
					} )
				).toBeVisible();

				await expect(
					page.getByRole( 'cell', { name: '–$2.00' } )
				).toBeVisible();
			} );

			await frontendUtils.emptyCart();
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
			await frontendUtils.goToCheckout();

			await page.getByRole( 'button', { name: 'Add coupons' } ).click();
			await page.getByLabel( 'Enter code' ).fill( 'single-use-coupon' );
			await page.getByRole( 'button', { name: 'Apply' } ).click();

			await expect(
				page.getByText(
					'Usage limit for coupon "SINGLE-USE-COUPON" has been reached.'
				)
			).toBeVisible();
		} );
	} );
} );
