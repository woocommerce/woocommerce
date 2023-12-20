/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import {
	DISCOUNTED_PRODUCT_NAME,
	REGULAR_PRICED_PRODUCT_NAME,
} from '../checkout/constants';
import { CheckoutPage } from '../checkout/checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Taxes', () => {
	test( 'Tax visibility on Cart/Checkout/OrderSummary blocks depends on "Enable tax rate calculations" option in WC settings -> general', async ( {
		requestUtils,
		frontendUtils,
		page,
		checkoutPageObject,
	} ) => {
		// Turn off tax display.
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/general/woocommerce_calc_taxes',
			data: { value: 'no' },
		} );
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( DISCOUNTED_PRODUCT_NAME );
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		let cartSidebar = page.locator(
			'.wp-block-woocommerce-cart-totals-block'
		);
		const taxRow = cartSidebar
			.locator( '.wc-block-components-totals-taxes' )
			.getByText( 'Tax' );
		await expect( taxRow ).toBeHidden();

		// Move to Checkout and look for Tax row.
		await frontendUtils.goToCheckout();
		let checkoutSidebar = page.locator(
			'.wp-block-woocommerce-checkout-totals-block'
		);
		const checkoutTaxRow = checkoutSidebar
			.locator( '.wc-block-components-totals-taxes' )
			.getByText( 'Tax' );
		await expect( checkoutTaxRow ).toBeHidden();

		// Check out and look for tax on order confirmation page.
		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();
		const taxOnOrderConfirmation = page.getByText( 'Tax:' );
		await expect( taxOnOrderConfirmation ).toBeHidden();

		// Empty the cart (it should be empty already, but just in case).
		await frontendUtils.emptyCart();

		// Turn on tax display.
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/general/woocommerce_calc_taxes',
			data: { value: 'yes' },
		} );
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( DISCOUNTED_PRODUCT_NAME );
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		cartSidebar = page.locator( '.wp-block-woocommerce-cart-totals-block' );
		const visibleTaxRow = cartSidebar
			.locator( '.wc-block-components-totals-taxes' )
			.getByText( 'Tax' );
		await expect( visibleTaxRow ).toBeVisible();

		// Move to Checkout and look for Tax row.
		await frontendUtils.goToCheckout();
		checkoutSidebar = page.locator(
			'.wp-block-woocommerce-checkout-totals-block'
		);
		const visibleCheckoutTaxRow = checkoutSidebar
			.locator( '.wc-block-components-totals-taxes' )
			.getByText( 'Tax' );
		await expect( visibleCheckoutTaxRow ).toBeVisible();

		// Check out and look for tax on order confirmation page.
		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();
		const visibleTaxOnOrderConfirmation = page.getByText( 'Tax:' );
		await expect( visibleTaxOnOrderConfirmation ).toBeVisible();
	} );
} );
