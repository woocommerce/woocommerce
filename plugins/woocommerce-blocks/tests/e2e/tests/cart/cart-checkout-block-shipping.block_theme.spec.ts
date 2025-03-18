/**
 * External dependencies
 */
import { expect, test as base, FrontendUtils } from '@woocommerce/e2e-utils';

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
	 *
	 * 1. Y Y Y
	 * 2. Y Y N
	 * 3. Y N Y
	 * 4. Y N N
	 * 5. N Y Y
	 * 6. N Y N
	 * 7. N N Y
	 * 8. N N N
	 */

	test( '1. With shipping methods for the default location, shipping methods for _any_ location, and local pickup enabled, the shopper sees rates and pickup options in the sidebar', async ( {
		localPickupUtils,
		admin,
		browser,
		requestUtils,
	} ) => {
		await localPickupUtils.enableLocalPickup();
		await admin.visitAdminPage( 'admin.php?page=wc-settings&tab=general' );
		await admin.page
			.getByLabel( 'Default customer location' )
			.selectOption( 'No location by default' );
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();

		const guestContext = await browser.newContext( {
			storageState: { cookies: [], origins: [] },
		} );
		const userPage = await guestContext.newPage();

		const userFrontendUtils = new FrontendUtils( userPage, requestUtils );

		await userFrontendUtils.goToShop();
		await userFrontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await userFrontendUtils.goToCart();

		await expect(
			userFrontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
		await expect(
			userFrontendUtils.page.getByRole( 'radio', {
				name: 'Woo Collection FREE',
			} )
		).toBeVisible();

		await userFrontendUtils.goToCheckout();
		await expect(
			userFrontendUtils.page.getByRole( 'radio', {
				name: 'Ship',
				exact: true,
			} )
		).toBeChecked();
		await expect(
			userFrontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
		await expect(
			userFrontendUtils.page.getByRole( 'radio', {
				name: 'Flat rate shipping $',
			} )
		).toBeChecked();
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
