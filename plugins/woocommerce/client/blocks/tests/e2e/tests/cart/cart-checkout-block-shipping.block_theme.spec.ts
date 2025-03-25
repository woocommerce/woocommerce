/**
 * External dependencies
 */
import { expect, test as base, FrontendUtils } from '@woocommerce/e2e-utils';
import { Dialog } from '@playwright/test';

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

	test.beforeEach( async ( { admin } ) => {
		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=general' );
		await admin.page
			.getByLabel( 'Default customer location' )
			.selectOption( 'No location by default' );
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();

		await admin.visitAdminPage(
			'admin.php?page=wc-settings&tab=shipping&zone_id=new'
		);
		await admin.page.getByLabel( 'Zone name' ).fill( 'UK' );
		await admin.page
			.getByRole( 'combobox', { name: 'Start typing to filter zones' } )
			.fill( 'United Kingdom' );
		await admin.page
			.getByRole( 'checkbox', { name: 'United Kingdom (UK)' } )
			.click(); // .check() won't work here as the input disappears immediately after checking.
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();
		await admin.page
			.getByRole( 'button', { name: 'Add shipping method' } )
			.click();
		await admin.page.getByText( 'Flat rate' ).click();
		await admin.page.getByRole( 'button', { name: 'Continue' } ).click();
		await admin.page
			.getByRole( 'button', { name: 'Create and save' } )
			.click();
		await expect( admin.page.getByText( 'Flat rate' ) ).toBeVisible();
		if (
			! ( await admin.page
				.getByRole( 'button', { name: 'Save changes' } )
				.isDisabled() )
		) {
			await admin.page
				.getByRole( 'button', { name: 'Save changes' } )
				.click();
		}
		await expect(
			admin.page.getByRole( 'button', { name: 'Save changes' } )
		).toBeDisabled();
	} );

	// Series of tests below to cover the following scenarios: see PR https://github.com/woocommerce/woocommerce/pull/56460 for more details

	/**
	 * Rates enabled for default customer location
	 * Rates enabled for _any_ location
	 * Local pickup enabled
	 * Hide rates until an address is entered (only applicable when Local pickup is DISABLED)
	 *
	 * 1.  Y Y Y #
	 * 2.  Y Y N N
	 * 3.  Y N Y # - Skipping because this behaves the same as test 1.
	 * 4.  Y N N N
	 * 5.  N Y Y #
	 * 6.  N Y N N
	 * 7.  N N Y # - known bug here with the text/UI to enter an address, despite there being no methods.
	 * 8.  N N N N
	 * 9.  Y Y N Y
	 * 10. Y N N Y
	 * 11. N Y N Y
	 * 12. N N N Y
	 */

	test( '1. With shipping methods for the default location, shipping methods for _any_ location, and local pickup enabled, the shopper sees shipping rates and pickup options - rates are selected default', async ( {
		localPickupUtils,
		frontendUtils,
		page,
	} ) => {
		await page.goto(
			'/?disable_third_party_local_pickup_method_registration'
		);
		await expect(
			page.getByText(
				'Third party local pickup method registration disabled.'
			)
		).toBeVisible();
		await localPickupUtils.enableLocalPickup();
		await localPickupUtils.addPickupLocation( {
			location: {
				name: 'Automattic, Inc.',
				address: '60 29th Street, Suite 343',
				city: 'San Francisco',
				postcode: '94110',
				state: 'US:CA',
				details: 'American entity',
			},
		} );

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Pickup (Automattic, Inc.) Free',
			} )
		).toBeVisible();

		await frontendUtils.goToCheckout();
		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Ship',
				exact: true,
			} )
		).toBeChecked();
		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
	} );

	test( '2. With shipping methods for the default location, shipping methods for _any_ location, local pickup disabled, and shipping costs requires address disabled, the shopper sees shipping rates only', async ( {
		localPickupUtils,
		frontendUtils,
		shippingUtils,
		checkoutPageObject,
	} ) => {
		await localPickupUtils.disableLocalPickup();
		await shippingUtils.disableShippingCostsRequireAddress();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await expect(
			checkoutPageObject.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();

		await frontendUtils.goToCheckout();
		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Ship',
				exact: true,
			} )
		).toBeHidden();
		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
	} );

	// 3. With shipping methods for the default location, no shipping methods for _any_ other location, and local pickup enabled, the shopper sees shipping rates and pickup options - skipped as same result as 1.

	test( '4. With shipping methods for the default location, no shipping methods for _any_ other location, local pickup disabled, and shipping costs require address disabled, the shopper sees shipping rates only after entering an address', async ( {
		localPickupUtils,
		admin,
		frontendUtils,
		checkoutPageObject,
		shippingUtils,
	} ) => {
		await localPickupUtils.disableLocalPickup();
		await shippingUtils.disableShippingCostsRequireAddress();

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );
		// Accept the delete dialog, then remove the listener;
		const acceptDialog = ( dialog: Dialog ) => dialog.accept();
		admin.page.on( 'dialog', acceptDialog );
		await admin.page.getByRole( 'link', { name: 'Delete' } ).click();
		admin.page.off( 'dialog', acceptDialog );

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await expect(
			frontendUtils.page.getByText(
				'Enter address to check delivery options'
			)
		).toBeHidden();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();

		await frontendUtils.goToCheckout();

		await expect(
			frontendUtils.page.getByText(
				'Enter a shipping address to view shipping options'
			)
		).toBeHidden();

		await expect(
			checkoutPageObject.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
	} );

	// 5. With no shipping methods for the default location, but shipping methods for _any_ other location, local pickup enabled, the shopper sees pickup rates until entering an address for the zone with rates
	// Not testing because this is a "bug" we are going to fix - see https://github.com/woocommerce/woocommerce/issues/56462

	test( '6. With no shipping methods for the default location, but shipping methods for _any_ other location, local pickup disabled, and shipping costs require address disabled, the shopper sees shipping rates only after entering an address', async ( {
		localPickupUtils,
		admin,
		frontendUtils,
		checkoutPageObject,
		shippingUtils,
		page,
	} ) => {
		await page.goto(
			'/?disable_third_party_local_pickup_method_registration'
		);
		await expect(
			page.getByText(
				'Third party local pickup method registration disabled.'
			)
		).toBeVisible();
		await localPickupUtils.disableLocalPickup();
		await shippingUtils.disableShippingCostsRequireAddress();
		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=general' );
		await admin.page
			.getByLabel( 'Default customer location' )
			.selectOption( 'No location by default' );
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );

		await admin.page
			.getByRole( 'row', { name: 'Rest of the world' } )
			.getByRole( 'link' )
			.click();

		// There are two shipping rates enabled. Clicking the first one turns it off.
		// Then only one "name: yes" remains, making it the first, even though it's the second rate.
		await admin.page.getByRole( 'link', { name: 'Yes' } ).first().click();
		await admin.page.getByRole( 'link', { name: 'Yes' } ).first().click();
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await expect(
			frontendUtils.page.getByText(
				'Enter address to check delivery options'
			)
		).toBeHidden();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Pickup (Automattic, Inc.) Free',
			} )
		).toBeHidden();
		await frontendUtils.goToCheckout();

		await expect(
			frontendUtils.page.getByText(
				'Enter a shipping address to view shipping options'
			)
		).toBeVisible();
		await checkoutPageObject.fillInCheckoutWithTestData();

		await expect(
			checkoutPageObject.page
				.getByLabel( 'Checkout' )
				.getByText(
					'No shipping options are available for this address. Please verify the address is correct or try a different address.'
				)
		).toBeVisible();

		await checkoutPageObject.fillInCheckoutWithTestData( {
			country: 'GB',
			postcode: 'SW19 5AE',
		} );

		await expect(
			checkoutPageObject.page.getByRole( 'radio', {
				name: 'Flat rate Free',
			} )
		).toBeChecked();
	} );

	test( '7. With no shipping methods for the default location, no shipping methods for _any_ other location, local pickup enabled the shopper sees local pickup rates only', async ( {
		localPickupUtils,
		admin,
		frontendUtils,
		page,
	} ) => {
		await page.goto(
			'/?disable_third_party_local_pickup_method_registration'
		);
		await expect(
			page.getByText(
				'Third party local pickup method registration disabled.'
			)
		).toBeVisible();

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );
		// Accept the delete dialog, then remove the listener;
		const acceptDialog = ( dialog: Dialog ) => dialog.accept();
		admin.page.on( 'dialog', acceptDialog );
		await admin.page.getByRole( 'link', { name: 'Delete' } ).click();
		admin.page.off( 'dialog', acceptDialog );

		await localPickupUtils.enableLocalPickup();
		await localPickupUtils.addPickupLocation( {
			location: {
				name: 'Automattic, Inc.',
				address: '60 29th Street, Suite 343',
				city: 'San Francisco',
				postcode: '94110',
				state: 'US:CA',
				details: 'American entity',
			},
		} );

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );

		await admin.page
			.getByRole( 'row', { name: 'Rest of the world' } )
			.getByRole( 'link' )
			.click();

		// There are two shipping rates enabled. Clicking the first one turns it off.
		// Then only one "name: yes" remains, making it the first, even though it's the second rate.
		await admin.page.getByRole( 'link', { name: 'Yes' } ).first().click();
		await admin.page.getByRole( 'link', { name: 'Yes' } ).first().click();
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await expect(
			frontendUtils.page.getByText(
				'Enter address to check delivery options'
			)
		).toBeHidden();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Pickup (Automattic, Inc.) Free',
			} )
		).toBeChecked();

		await frontendUtils.goToCheckout();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Automattic, Inc. free 60 29th',
			} )
		).toBeChecked();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Ship',
				exact: true,
			} )
		).toBeHidden();
	} );

	// Skipping test due to a known bug with needs_shipping - see issue https://github.com/woocommerce/woocommerce/issues/56507
	test.skip( '8. With no shipping methods for the default location, no shipping methods for _any_ other location, local pickup disabled the shopper sees no shipping and no pickup rates', async ( {
		localPickupUtils,
		admin,
		frontendUtils,
		page,
	} ) => {
		await page.goto(
			'/?disable_third_party_local_pickup_method_registration'
		);
		await expect(
			page.getByText(
				'Third party local pickup method registration disabled.'
			)
		).toBeVisible();

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );
		// Accept the delete dialog, then remove the listener;
		const acceptDialog = ( dialog: Dialog ) => dialog.accept();
		admin.page.on( 'dialog', acceptDialog );
		await admin.page.getByRole( 'link', { name: 'Delete' } ).click();
		admin.page.off( 'dialog', acceptDialog );

		await localPickupUtils.disableLocalPickup();

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );

		await admin.page
			.getByRole( 'row', { name: 'Rest of the world' } )
			.getByRole( 'link' )
			.click();

		// There are two shipping rates enabled. Clicking the first one turns it off.
		// Then only one "name: yes" remains, making it the first, even though it's the second rate.
		await admin.page.getByRole( 'link', { name: 'Yes' } ).first().click();
		await admin.page.getByRole( 'link', { name: 'Yes' } ).first().click();
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await frontendUtils.goToCheckout();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Ship',
				exact: true,
			} )
		).toBeHidden();

		await expect(
			frontendUtils.page.getByRole( 'heading', {
				name: 'Shipping options',
			} )
		).toBeHidden();
	} );

	test( '9. With shipping methods for the default location, shipping methods for _any_ other location, local pickup disabled and shipping requires address enabled, shopper sees shipping rates immediately.', async ( {
		localPickupUtils,
		frontendUtils,
		shippingUtils,
		page,
		checkoutPageObject,
	} ) => {
		await page.goto(
			'/?disable_third_party_local_pickup_method_registration'
		);
		await expect(
			page.getByText(
				'Third party local pickup method registration disabled.'
			)
		).toBeVisible();

		await localPickupUtils.disableLocalPickup();
		await shippingUtils.enableShippingCostsRequireAddress();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await expect(
			frontendUtils.page.getByText(
				'Enter address to check delivery options'
			)
		).toBeVisible();

		await frontendUtils.goToCheckout();

		await expect(
			frontendUtils.page.getByText(
				'Enter a shipping address to view shipping options.'
			)
		).toBeVisible();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Ship',
				exact: true,
			} )
		).toBeHidden();

		await expect(
			frontendUtils.page.getByRole( 'heading', {
				name: 'Shipping options',
			} )
		).toBeVisible();

		await checkoutPageObject.fillInCheckoutWithTestData();

		await expect(
			checkoutPageObject.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeVisible();
	} );

	test( '10. With shipping methods for the default location, no shipping methods for _any_ other location, local pickup disabled, and shipping costs require address enabled, the shopper sees shipping rates only after entering an address', async ( {
		localPickupUtils,
		admin,
		frontendUtils,
		checkoutPageObject,
		shippingUtils,
	} ) => {
		await localPickupUtils.disableLocalPickup();
		await shippingUtils.enableShippingCostsRequireAddress();

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );
		// Accept the delete dialog, then remove the listener;
		const acceptDialog = ( dialog: Dialog ) => dialog.accept();
		admin.page.on( 'dialog', acceptDialog );
		await admin.page.getByRole( 'link', { name: 'Delete' } ).click();
		admin.page.off( 'dialog', acceptDialog );

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await expect(
			frontendUtils.page.getByText(
				'Enter address to check delivery options'
			)
		).toBeVisible();

		await frontendUtils.goToCheckout();

		await expect(
			frontendUtils.page.getByText(
				'Enter a shipping address to view shipping options'
			)
		).toBeVisible();
		await checkoutPageObject.fillInCheckoutWithTestData();

		await expect(
			checkoutPageObject.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeVisible();
	} );

	// 11. With no shipping methods for the default location, but shipping methods for _any_ other location, local pickup disabled, and shipping requires address enabled, the shopper sees no shipping until an address is entered no pickup rates
	// Skipping testing 11 because it is the same as 6.

	// Skipping test due to a known bug with needs_shipping - see issue https://github.com/woocommerce/woocommerce/issues/56507
	test.skip( '12. With no shipping methods for the default location, no shipping methods for _any_ other location, local pickup disabled, and shipping requires address enabled the shopper sees no shipping and no pickup rates', async ( {
		localPickupUtils,
		admin,
		frontendUtils,
		page,
		shippingUtils,
	} ) => {
		await page.goto(
			'/?disable_third_party_local_pickup_method_registration'
		);
		await expect(
			page.getByText(
				'Third party local pickup method registration disabled.'
			)
		).toBeVisible();

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );
		// Accept the delete dialog, then remove the listener;
		const acceptDialog = ( dialog: Dialog ) => dialog.accept();
		admin.page.on( 'dialog', acceptDialog );
		await admin.page.getByRole( 'link', { name: 'Delete' } ).click();
		admin.page.off( 'dialog', acceptDialog );

		await localPickupUtils.disableLocalPickup();
		await shippingUtils.enableShippingCostsRequireAddress();

		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=shipping' );

		await admin.page
			.getByRole( 'row', { name: 'Rest of the world' } )
			.getByRole( 'link' )
			.click();

		// There are two shipping rates enabled. Clicking the first one turns it off.
		// Then only one "name: yes" remains, making it the first, even though it's the second rate.
		await admin.page.getByRole( 'link', { name: 'Yes' } ).first().click();
		await admin.page.getByRole( 'link', { name: 'Yes' } ).first().click();
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await frontendUtils.goToCheckout();

		await expect(
			frontendUtils.page.getByRole( 'radio', {
				name: 'Ship',
				exact: true,
			} )
		).toBeHidden();

		await expect(
			frontendUtils.page.getByRole( 'heading', {
				name: 'Shipping options',
			} )
		).toBeHidden();
	} );

	test( 'Guest user can see shipping calculator on cart page', async ( {
		requestUtils,
		browser,
	} ) => {
		const guestContext = await browser.newContext( {
			storageState: { cookies: [], origins: [] },
		} );
		const userPage = await guestContext.newPage();

		const userFrontendUtils = new FrontendUtils( userPage, requestUtils );

		await userFrontendUtils.goToShop();
		await userFrontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await userFrontendUtils.goToCart();

		// Note that the default customer location is set to the shop country/region, which
		// is why this label is pre-populated with the shop country/region.
		await expect(
			userPage.getByText( 'Enter address to check delivery options' )
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

		await userFrontendUtils.goToShop();
		await userFrontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await userFrontendUtils.goToCheckout();

		await expect(
			userPage.getByText(
				'Enter a shipping address to view shipping options.'
			)
		).toBeVisible();

		await userCheckoutPageObject.fillInCheckoutWithTestData();

		await expect(
			userPage.getByText(
				'Enter a shipping address to view shipping options.'
			)
		).toBeHidden();
	} );
} );
