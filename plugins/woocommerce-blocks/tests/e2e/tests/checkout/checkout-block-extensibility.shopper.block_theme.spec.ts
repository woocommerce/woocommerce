/**
 * External dependencies
 */
import { expect, test as base, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from './constants';
import { CheckoutPage } from './checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Account (guest user)', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { requestUtils, frontendUtils } ) => {
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/account/woocommerce_enable_guest_checkout',
			data: { value: 'yes' },
		} );
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/account/woocommerce_enable_checkout_login_reminder',
			data: { value: 'yes' },
		} );
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-extensioncartupdate'
		);

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
	} );

	test( 'Shopper can use extensionCartUpdate without the unpushed data being overwritten', async ( {
		checkoutPageObject,
	} ) => {
		await checkoutPageObject.page
			.getByLabel( 'Country/Region' )
			.fill( 'United Kingdom (UK)' );
		await checkoutPageObject.page.getByLabel( 'Country/Region' ).blur();

		await checkoutPageObject.page.evaluate(
			"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update' } )"
		);
		await expect(
			checkoutPageObject.page.getByLabel( 'Country/Region' )
		).toHaveValue( 'United Kingdom (UK)' );
	} );
} );
