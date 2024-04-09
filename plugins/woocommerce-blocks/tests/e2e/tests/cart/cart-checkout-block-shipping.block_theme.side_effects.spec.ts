/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import { FrontendUtils, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from '../checkout/checkout.page';
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Merchant → Shipping', () => {
	test( 'Merchant can enable shipping calculator and hide shipping costs before address is entered', async ( {
		page,
		shippingUtils,
		localPickupUtils,
	} ) => {
		await localPickupUtils.disableLocalPickup();

		await shippingUtils.enableShippingCalculator();
		await shippingUtils.enableShippingCostsRequireAddress();

		await expect(
			page.getByLabel( 'Enable the shipping calculator on the cart page' )
		).toBeChecked();

		await expect(
			page.getByLabel( 'Hide shipping costs until an address is entered' )
		).toBeChecked();
	} );
} );

test.describe( 'Shopper → Shipping', () => {
	test.beforeEach( async ( { shippingUtils } ) => {
		await shippingUtils.enableShippingCostsRequireAddress();
	} );

	test( 'Guest user can see shipping calculator on cart page', async ( {
		requestUtils,
		browser,
	} ) => {
		const guestContext = await browser.newContext();
		const userPage = await guestContext.newPage();

		const userFrontendUtils = new FrontendUtils( userPage, requestUtils );

		await userFrontendUtils.emptyCart();
		await userFrontendUtils.goToShop();
		await userFrontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await userFrontendUtils.goToCart();

		await expect(
			userPage.getByLabel( 'Add an address for shipping options' )
		).toBeVisible();
	} );

	test( 'Guest user does not see shipping rates until full address is entered', async ( {
		requestUtils,
		browser,
	} ) => {
		const guestContext = await browser.newContext();
		const userPage = await guestContext.newPage();

		const userFrontendUtils = new FrontendUtils( userPage, requestUtils );
		const userCheckoutPageObject = new CheckoutPage( { page: userPage } );

		await userFrontendUtils.emptyCart();
		await userFrontendUtils.goToShop();
		await userFrontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await userFrontendUtils.goToCheckout();

		await expect(
			userPage.getByText(
				'Shipping options will be displayed here after entering your full shipping addres'
			)
		).toBeVisible();

		await userCheckoutPageObject.fillInCheckoutWithTestData();

		await expect(
			userPage.getByText(
				'Shipping options will be displayed here after entering your full shipping addres'
			)
		).toBeHidden();
	} );
} );
