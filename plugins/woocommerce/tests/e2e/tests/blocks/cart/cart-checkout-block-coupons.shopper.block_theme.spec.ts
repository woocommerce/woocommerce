/**
 * External dependencies
 */
import { randomUUID } from 'crypto';
import { expect, test as base, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
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
	test.use( { storageState: guestFile } );

	let productId: number;
	let couponCode: string;

	test.beforeEach(
		async ( { requestUtils, createHiddenProduct, cleanup } ) => {
			( { id: productId } = await createHiddenProduct( {
				name: 'Coupon test product',
				regular_price: '20',
			} ) );
			couponCode = `single-use-${ randomUUID().slice( 0, 8 ) }`;
			const { id: couponId } = await requestUtils.rest< { id: number } >(
				{
					method: 'POST',
					path: 'wc/v3/coupons',
					data: {
						code: couponCode,
						discount_type: 'percent',
						amount: '10',
						usage_limit: 1,
					},
				}
			);
			cleanup( () =>
				requestUtils.rest( {
					method: 'DELETE',
					path: `wc/v3/coupons/${ couponId }`,
					params: { force: true },
				} )
			);
		}
	);

	test( 'Shopper can apply single-use coupon and place order', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
	} ) => {
		await page.goto( `/?add-to-cart=${ productId }` );
		await frontendUtils.goToCart();

		await page.getByRole( 'button', { name: 'Add coupons' } ).click();
		await page.getByLabel( 'Enter code' ).fill( couponCode );
		await page.getByRole( 'button', { name: 'Apply' } ).click();

		await expect(
			page.getByLabel( `Remove coupon "${ couponCode }"` )
		).toBeVisible();

		await page.getByLabel( `Remove coupon "${ couponCode }"` ).click();

		await expect(
			page.getByLabel( `Remove coupon "${ couponCode }"` )
		).toBeHidden();

		await frontendUtils.goToCheckout();
		await page.getByRole( 'button', { name: 'Add coupons' } ).click();
		await page.getByLabel( 'Enter code' ).fill( couponCode );
		await page.getByRole( 'button', { name: 'Apply' } ).click();

		await expect(
			page.getByLabel( `Remove coupon "${ couponCode }"` )
		).toBeVisible();

		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();

		await expect(
			page.getByText( 'Thank you. Your order has been received.' )
		).toBeVisible();

		await expect(
			page.getByRole( 'rowheader', { name: 'Discount:' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'cell', { name: '–$2.00' } )
		).toBeVisible();
	} );

	test( 'Shopper cannot apply single-use coupon twice', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
	} ) => {
		await page.goto( `/?add-to-cart=${ productId }` );
		await frontendUtils.goToCheckout();

		await page.getByRole( 'button', { name: 'Add coupons' } ).click();
		await page.getByLabel( 'Enter code' ).fill( couponCode );
		await page.getByRole( 'button', { name: 'Apply' } ).click();

		await expect(
			page.getByLabel( `Remove coupon "${ couponCode }"` )
		).toBeVisible();

		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();

		await frontendUtils.emptyCart();
		await page.goto( `/?add-to-cart=${ productId }` );
		await frontendUtils.goToCheckout();

		await page.getByRole( 'button', { name: 'Add coupons' } ).click();
		await page.getByLabel( 'Enter code' ).fill( couponCode );
		await page.getByRole( 'button', { name: 'Apply' } ).click();

		await expect(
			page.getByText(
				`Usage limit for coupon "${ couponCode.toUpperCase() }" has been reached.`
			)
		).toBeVisible();
	} );
} );
