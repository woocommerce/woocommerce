/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import { BlockData } from '@woocommerce/e2e-types';
import { customerFile, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	REGULAR_PRICED_PRODUCT_NAME,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	FREE_SHIPPING_NAME,
	FREE_SHIPPING_PRICE,
	FLAT_RATE_SHIPPING_NAME,
	FLAT_RATE_SHIPPING_PRICE,
} from './constants';
import { CheckoutPage } from './checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

const blockData: BlockData = {
	name: 'Checkout',
	slug: 'woocommerce/checkout',
	mainClass: '.wp-block-woocommerce-checkout',
	selectors: {
		editor: {
			block: '.wp-block-woocommerce-checkout',
			insertButton: "//button//span[text()='Checkout']",
		},
		frontend: {},
	},
};

test.describe( 'Shopper → Account', () => {
	// Become a logged out user.
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test.beforeAll( async ( { requestUtils } ) => {
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
	} );
	test.beforeEach( async ( { frontendUtils } ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
	} );

	test( 'Shopper can log in to an existing account and can create an account', async ( {
		requestUtils,
		checkoutPageObject,
		page,
	} ) => {
		//Get the login link from checkout page.
		const loginLink = page.getByText( 'Log in.' );
		const loginLinkHref = await loginLink.getAttribute( 'href' );

		//Confirm login link is correct.
		expect( loginLinkHref ).toContain(
			`${ process.env.WORDPRESS_BASE_URL }/my-account/?redirect_to`
		);
		expect( loginLinkHref ).toContain( `checkout` );

		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/account/woocommerce_enable_signup_and_login_from_checkout',
			data: { value: 'yes' },
		} );

		await page.reload();

		const createAccount = page.getByLabel( 'Create an account?' );
		await createAccount.check();
		const testEmail = `test${ Math.random() * 10 }@example.com`;
		await checkoutPageObject.fillInCheckoutWithTestData( {
			email: testEmail,
		} );
		await checkoutPageObject.placeOrder();

		// Get users from API with same email used when purchasing.
		await requestUtils
			.rest( {
				method: 'GET',
				path: `wc/v3/customers?email=${ testEmail }`,
			} )
			.then( ( response ) => {
				expect( response[ 0 ].email ).toBe( testEmail );
			} );
	} );
} );

test.describe( 'shopper → Local pickup', () => {
	test.beforeEach( async ( { admin } ) => {
		// Enable local pickup.
		await admin.visitAdminPage(
			'admin.php?page=wc-settings&tab=shipping&section=pickup_location'
		);
		await admin.page.getByLabel( 'Enable local pickup' ).check();
		await admin.page
			.getByRole( 'button', { name: 'Add pickup location' } )
			.click();
		await admin.page.getByLabel( 'Location name' ).fill( 'Testing' );
		await admin.page.getByPlaceholder( 'Address' ).fill( 'Test Address' );
		await admin.page.getByPlaceholder( 'City' ).fill( 'Test City' );
		await admin.page.getByPlaceholder( 'Postcode / ZIP' ).fill( '90210' );
		await admin.page
			.getByLabel( 'Pickup details' )
			.fill( 'Pickup method.' );
		await admin.page.getByRole( 'button', { name: 'Done' } ).click();
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();
	} );

	test.afterEach( async ( { admin } ) => {
		// Enable local pickup.
		await admin.visitAdminPage(
			'admin.php?page=wc-settings&tab=shipping&section=pickup_location'
		);
		await admin.page.getByRole( 'button', { name: 'Edit' } ).last().click();
		await admin.page
			.getByRole( 'button', { name: 'Delete location' } )
			.click();
		await admin.page
			.getByRole( 'button', { name: 'Save changes' } )
			.click();
	} );

	test( 'The shopper can choose a local pickup option', async ( {
		page,
		frontendUtils,
		checkoutPageObject,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await page.getByRole( 'radio', { name: 'Local Pickup free' } ).click();
		await expect( page.getByLabel( 'Testing' ).last() ).toBeVisible();
		await page.getByLabel( 'Testing' ).last().check();

		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();

		await expect(
			page.getByText( 'Collection from Testing' )
		).toBeVisible();
		await checkoutPageObject.verifyBillingDetails();
	} );

	// Switching between local pickup and shipping does affect the address. We should create a ticket for this.
	// Let's skip the test until the bug is fixed.
	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'Switching between local pickup and shipping does not affect the address', async ( {
		page,
		frontendUtils,
		checkoutPageObject,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await page.getByRole( 'radio', { name: 'Local Pickup free' } ).click();
		await expect( page.getByLabel( 'Testing' ).last() ).toBeVisible();

		await checkoutPageObject.fillInCheckoutWithTestData();

		await page
			.getByLabel( 'Email address' )
			.fill( 'thisShouldRemainHere@mail.com' );
		await page.getByRole( 'radio', { name: 'Shipping from free' } ).click();
		await expect(
			page.getByRole( 'radio', { name: 'Flat rate shipping' } ).first()
		).toBeVisible();
		await expect( page.getByLabel( 'Email address' ) ).toHaveValue(
			'thisShouldRemainHere@mail.com'
		);

		await page
			.getByLabel( 'Email address' )
			.fill( 'thisShouldRemainHereToo@mail.com' );
		await page.getByLabel( 'First name' ).fill( 'Test FirstName' );
		await expect( page.getByLabel( 'Email address' ) ).toHaveValue(
			'thisShouldRemainHereToo@mail.com'
		);
	} );
} );

test.describe( 'Payment Methods', () => {
	test( 'User can change payment methods', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await page
			.getByRole( 'radio', { name: 'Direct bank transfer' } )
			.click();
		await expect(
			page.getByRole( 'radio', { name: 'Direct bank transfer' } )
		).toBeChecked();

		await page.getByRole( 'radio', { name: 'Cash on delivery' } ).click();
		await expect(
			page.getByRole( 'radio', { name: 'Cash on delivery' } )
		).toBeChecked();
	} );
} );

test.describe( 'Shipping and Billing Addresses', () => {
	const billingTestData = {
		firstname: 'John',
		lastname: 'Doe',
		company: 'Automattic',
		addressfirstline: '123 Main Road',
		addresssecondline: 'Unit 23',
		city: 'San Francisco',
		state: 'California',
		country: 'United Kingdom',
		postcode: 'SW1 1AA',
		phone: '123456789',
		email: 'john.doe@example.com',
	};
	const shippingTestData = {
		firstname: 'Jane',
		lastname: 'Doe',
		company: 'WooCommerce',
		addressfirstline: '123 Main Avenue',
		addresssecondline: 'Unit 42',
		city: 'Los Angeles',
		phone: '987654321',
		country: 'Albania',
		state: 'Berat',
		postcode: '1234',
	};
	// `as string` is safe here because we know the variable is a string, it is defined above.
	const blockSelectorInEditor = blockData.selectors.editor.block as string;

	test.beforeEach(
		async ( { editor, frontendUtils, admin, editorUtils } ) => {
			await admin.visitSiteEditor( {
				postId: 'woocommerce/woocommerce//page-checkout',
				postType: 'wp_template',
			} );
			await editorUtils.enterEditMode();
			await editor.openDocumentSettingsSidebar();
			await editor.selectBlocks(
				blockSelectorInEditor +
					'  [data-type="woocommerce/checkout-shipping-address-block"]'
			);

			const checkbox = editor.page.getByRole( 'checkbox', {
				name: 'Company',
				exact: true,
			} );
			await checkbox.check();
			await expect( checkbox ).toBeChecked();
			await expect(
				editor.canvas.locator(
					'div.wc-block-components-address-form__company'
				)
			).toBeVisible();
			await editorUtils.saveSiteEditorEntities();
			await frontendUtils.emptyCart();
		}
	);

	test.afterEach( async ( { frontendUtils, admin, editorUtils, editor } ) => {
		await frontendUtils.emptyCart();
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//page-checkout',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		await editor.openDocumentSettingsSidebar();
		await editor.selectBlocks(
			blockSelectorInEditor +
				'  [data-type="woocommerce/checkout-shipping-address-block"]'
		);
		const checkbox = editor.page.getByRole( 'checkbox', {
			name: 'Company',
			exact: true,
		} );
		await checkbox.uncheck();
		await expect( checkbox ).not.toBeChecked();
		await expect(
			editor.canvas.locator(
				'.wc-block-checkout__shipping-fields .wc-block-components-address-form__company'
			)
		).toBeHidden();
		await editorUtils.saveSiteEditorEntities();
	} );

	test( 'User can add postcodes for different countries', async ( {
		frontendUtils,
		page,
		checkoutPageObject,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await page.getByLabel( 'Use same address for billing' ).uncheck();

		await checkoutPageObject.fillShippingDetails( shippingTestData );
		await checkoutPageObject.fillBillingDetails( billingTestData );
		await expect(
			page.getByText( 'Please enter a valid postcode' )
		).toBeHidden();
	} );
} );

test.describe( 'Shopper → Checkout block → Shipping', () => {
	test.use( { storageState: customerFile } );

	test( 'Shopper can choose free shipping, flat rate shipping, and can have different billing and shipping addresses', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.emptyCart();
		await frontendUtils.addToCart( 'Beanie' );
		await frontendUtils.goToCheckout();
		await expect(
			await checkoutPageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();
		await checkoutPageObject.verifyAddressDetails( 'billing' );
		await checkoutPageObject.verifyAddressDetails( 'shipping' );
		await expect( page.getByText( FREE_SHIPPING_NAME ) ).toBeVisible();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( 'Beanie' );
		await frontendUtils.goToCheckout();
		await expect(
			await checkoutPageObject.selectAndVerifyShippingOption(
				FLAT_RATE_SHIPPING_NAME,
				FLAT_RATE_SHIPPING_PRICE
			)
		).toBe( true );

		await checkoutPageObject.syncBillingWithShipping();
		await checkoutPageObject.fillInCheckoutWithTestData( {
			phone: '0987654322',
		} );
		await checkoutPageObject.unsyncBillingWithShipping();
		const shippingForm = page.getByRole( 'group', {
			name: 'Shipping address',
		} );
		const billingForm = page.getByRole( 'group', {
			name: 'Billing address',
		} );

		await expect( shippingForm.getByLabel( 'Phone' ).inputValue ).toEqual(
			billingForm.getByLabel( 'Phone' ).inputValue
		);

		await checkoutPageObject.fillInCheckoutWithTestData();
		const overrideBillingDetails = {
			firstname: 'Juan',
			lastname: 'Perez',
			addressfirstline: '123 Test Street',
			addresssecondline: 'Apartment 6',
			country: 'ES',
			city: 'Madrid',
			postcode: '08830',
			state: 'M',
			phone: '0987654321',
			email: 'juan.perez@test.com',
		};
		await checkoutPageObject.fillBillingDetails( overrideBillingDetails );
		await checkoutPageObject.placeOrder();
		await checkoutPageObject.verifyAddressDetails(
			'billing',
			overrideBillingDetails
		);
		await checkoutPageObject.verifyAddressDetails( 'shipping' );
	} );
} );

// We only check if guest user can place an order because we already checked if logged in user can
// place an order in the previous test
test.describe( 'Shopper → Checkout block → Place Order', () => {
	test.use( { storageState: guestFile } );

	test( 'Guest user can place order', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		await expect(
			await checkoutPageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();
		await expect(
			page.getByText( 'Your order has been received.' )
		).toBeVisible();
	} );
} );

test.describe( 'Checkout Form Errors', () => {
	test.use( { storageState: guestFile } );

	test( 'User can see errors when form is incomplete', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await page.getByLabel( 'Email address' ).clear();
		await page.getByRole( 'button', { name: 'Place order' } ).click();

		// Verify that all required fields show the correct warning.
		await expect(
			page.getByText( 'Please enter a valid email address' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid first name' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid last name' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid address' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid city' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid zip code' )
		).toBeVisible();
	} );
} );
