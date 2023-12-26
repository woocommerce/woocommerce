/**
 * External dependencies
 */
import { merchant, withRestApi } from '@woocommerce/e2e-utils';
import { visitAdminPage } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import {
	shopper,
	preventCompatibilityNotice,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
	BASE_URL,
} from '../../../../utils';
import { merchant as merchantUtils } from '../../../../utils/merchant';
import { createCoupon } from '../../../utils';

let coupon;

describe( 'Shopper → Checkout', () => {
	beforeAll( async () => {
		// Check that Woo Collection is enabled.
		await page.goto(
			`${ BASE_URL }?check_third_party_local_pickup_method`
		);
		// eslint-disable-next-line jest/no-standalone-expect
		await expect( page ).toMatch( 'Woo Collection' );

		await shopper.block.emptyCart();
	} );

	describe.skip( `Shipping`, () => {
		afterEach( async () => {
			await merchant.login();
			await merchantUtils.disableLocalPickup();
		} );

		it( 'User does not see shipping rates until full address is entered', async () => {
			await preventCompatibilityNotice();
			await merchant.login();

			await merchantUtils.disableLocalPickup();
			await visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=options'
			);
			const hideShippingLabel = await page.$x(
				'//label[contains(., "Hide shipping costs until an address is entered")]'
			);
			await hideShippingLabel[ 0 ].click();

			const saveButton = await page.$x(
				'//button[contains(., "Save changes")]'
			);
			await saveButton[ 0 ].click();

			await page.waitForXPath(
				'//strong[contains(., "Your settings have been saved.")]'
			);
			await merchant.logout();

			await shopper.block.emptyCart();
			await shopper.block.goToShop();
			await shopper.addToCartFromShopPage( SIMPLE_PHYSICAL_PRODUCT_NAME );
			await shopper.block.goToCheckout();

			// // Expect no shipping options to be shown, but with a friendly message.
			const shippingOptionsRequireAddressText = await page.$x(
				'//p[contains(text(), "Shipping options will be displayed here after entering your full shipping address.")]'
			);

			await expect( shippingOptionsRequireAddressText ).toHaveLength( 1 );

			// Enter the address but not city and expect shipping options not to be shown.
			await shopper.block.fillInCheckoutWithTestData( { postcode: '' } );

			await expect( page ).not.toMatchElement(
				'.wc-block-components-shipping-rates-control'
			);

			await merchant.login();
			await merchantUtils.enableLocalPickup();
			await merchantUtils.addLocalPickupLocation();
			await merchant.logout();

			// This sequence will reset the checkout form.
			await shopper.login();
			await shopper.logout();

			await shopper.block.emptyCart();
			await shopper.block.goToShop();
			await shopper.addToCartFromShopPage( SIMPLE_PHYSICAL_PRODUCT_NAME );
			await shopper.block.goToCheckout();

			await expect( page ).toClick(
				'.wc-block-checkout__shipping-method button',
				{ text: 'Shipping' }
			);

			// Expect the shipping options to be displayed without entering an address.
			await expect( page ).toMatchElement(
				'.wc-block-components-shipping-rates-control'
			);
		} );
	} );

	describe.skip( 'Coupons', () => {
		beforeAll( async () => {
			coupon = await createCoupon( { usageLimit: 1 } );
			await shopper.logout();
			await shopper.login();
		} );

		afterAll( async () => {
			await withRestApi.deleteCoupon( coupon.id );
			await shopper.logout();
		} );

		it( 'Logged in user can apply single-use coupon and place order', async () => {
			await shopper.block.goToShop();
			await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
			await shopper.block.goToCheckout();
			await shopper.block.applyCouponFromCheckout( coupon.code );

			const discountBlockSelector =
				'.wc-block-components-totals-discount';
			const discountAppliedCouponCodeSelector =
				'.wc-block-components-totals-discount__coupon-list-item span.wc-block-components-chip__text';
			const discountValueSelector =
				'.wc-block-components-totals-discount .wc-block-components-totals-item__value';

			// Verify that the discount had been applied correctly on the checkout page.
			await page.waitForSelector( discountBlockSelector );
			await expect( page ).toMatchElement( discountValueSelector, {
				text: coupon.amount,
			} );
			await expect( page ).toMatchElement(
				discountAppliedCouponCodeSelector,
				{
					text: coupon.code,
				}
			);

			await shopper.block.fillInCheckoutWithTestData();
			await shopper.block.placeOrder();
			await expect( page ).toMatch( 'Your order has been received.' );

			// Verify that the discount had been applied correctly on the order confirmation page.
			await expect( page ).toMatchElement( `th`, {
				text: 'Discount',
			} );
			await expect( page ).toMatchElement(
				`span.woocommerce-Price-amount`,
				{
					text: coupon.amount,
				}
			);
		} );

		it( 'Logged in user cannot apply single-use coupon twice', async () => {
			await shopper.block.goToShop();
			await shopper.addToCartFromShopPage( SIMPLE_VIRTUAL_PRODUCT_NAME );
			await shopper.block.goToCheckout();
			await shopper.block.applyCouponFromCheckout( coupon.code );
			await page.waitForSelector(
				'.wc-block-components-totals-coupon__content .wc-block-components-validation-error'
			);
			await expect( page ).toMatch(
				'Coupon usage limit has been reached.'
			);
		} );
	} );
} );
