/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-utils';

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
		// The rate has to be global. This test's subject is the store-wide
		// "Enable tax rate calculations" option, and it shops with shared
		// fixture products, so `withScopedTaxClass` does not apply: it asserts
		// that tax calculation stays on, and scoping would mean reassigning
		// products other specs also use. Both the rate and the option are put
		// back in `finally` instead, so neither follows later specs.
		const { id: taxRateId } = await requestUtils.rest< { id: number } >( {
			method: 'POST',
			path: 'wc/v3/taxes',
			data: {
				rate: '20',
				name: 'Blocks tax visibility rate',
				class: 'standard',
			},
		} );

		try {
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

			cartSidebar = page.locator(
				'.wp-block-woocommerce-cart-totals-block'
			);
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
		} finally {
			// Restore the store-wide baseline: tax calculation on, and no
			// standard-class rate for the specs that run after this one.
			await requestUtils.rest( {
				method: 'PUT',
				path: 'wc/v3/settings/general/woocommerce_calc_taxes',
				data: { value: 'yes' },
			} );
			await requestUtils.rest( {
				method: 'DELETE',
				path: `wc/v3/taxes/${ taxRateId }`,
				params: { force: true },
			} );
		}
	} );
} );
