/**
 * External dependencies
 */
import { expect, test as base, guestFile } from '@woocommerce/e2e-utils';
import { Route } from '@playwright/test';

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

test.describe( 'Shopper → Extensibility', () => {
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

	test.describe( 'extensionCartUpdate', () => {
		test( 'Cart data can be modified by extensions', async ( {
			checkoutPageObject,
		} ) => {
			const { page } = checkoutPageObject;
			const updateCustomerUrl = '**/wc/store/v1/cart/update-customer**';
			let releaseCustomerUpdate!: () => void;
			let markCustomerUpdateContinued!: () => void;
			let customerUpdateWasIntercepted = false;
			let customerUpdateWasReleased = false;
			const customerUpdateReleased = new Promise< void >( ( resolve ) => {
				releaseCustomerUpdate = resolve;
			} );
			const customerUpdateContinued = new Promise< void >(
				( resolve ) => {
					markCustomerUpdateContinued = resolve;
				}
			);
			const releasePendingCustomerUpdate = () => {
				if ( ! customerUpdateWasReleased ) {
					customerUpdateWasReleased = true;
					releaseCustomerUpdate();
				}
			};
			const deferCustomerUpdate = async ( route: Route ) => {
				customerUpdateWasIntercepted = true;
				await customerUpdateReleased;
				try {
					await route.continue();
				} finally {
					markCustomerUpdateContinued();
				}
			};

			await page.route( updateCustomerUrl, deferCustomerUpdate );

			try {
				const country = page.getByLabel( 'Country/Region' );
				await country.selectOption( 'United Kingdom (UK)' );
				await country.blur();
				await page.waitForFunction( () => {
					return (
						window.localStorage.getItem(
							'WOOCOMMERCE_CHECKOUT_IS_CUSTOMER_DATA_DIRTY'
						) === 'true'
					);
				} );
				await page.evaluate( () =>
					window.wc.blocksCheckout.extensionCartUpdate( {
						namespace:
							'woocommerce-blocks-test-extension-cart-update',
					} )
				);
				await expect( country ).toHaveValue( 'GB' );

				const overwriteResponse = await page.evaluate( () =>
					window.wc.blocksCheckout.extensionCartUpdate( {
						namespace:
							'woocommerce-blocks-test-extension-cart-update',
						overwriteDirtyCustomerData: true,
					} )
				);
				await expect( country ).toHaveValue(
					overwriteResponse.shipping_address.country
				);

				await page.evaluate( () =>
					window.wc.blocksCheckout.extensionCartUpdate( {
						namespace:
							'woocommerce-blocks-test-extension-cart-update',
						data: { 'test-name-change': true },
						overwriteDirtyCustomerData: true,
					} )
				);
				await expect( page.getByLabel( 'First name' ) ).toHaveValue(
					'Mr. Test'
				);
			} finally {
				releasePendingCustomerUpdate();
				if ( customerUpdateWasIntercepted ) {
					await customerUpdateContinued;
				}
				await page.unroute( updateCustomerUrl, deferCustomerUpdate );
			}
		} );
	} );
} );
