/**
 * @format
 */

/**
 * External dependencies
 */
import { pressKeyWithModifier } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { clearAndFillInput } from './page-utils';

const config = require( 'config' );
const baseUrl = config.get( 'url' );

const WP_ADMIN_LOGIN = baseUrl + 'wp-login.php';
const WP_ADMIN_DASHBOARD = baseUrl + 'wp-admin';
const WP_ADMIN_PLUGINS = baseUrl + 'wp-admin/plugins.php';
const WP_ADMIN_SETUP_WIZARD = baseUrl + 'wp-admin/admin.php?page=wc-admin';
const WP_ADMIN_ALL_ORDERS_VIEW = baseUrl + 'wp-admin/edit.php?post_type=shop_order';
const WP_ADMIN_NEW_COUPON = baseUrl + 'wp-admin/post-new.php?post_type=shop_coupon';
const WP_ADMIN_NEW_ORDER = baseUrl + 'wp-admin/post-new.php?post_type=shop_order';
const WP_ADMIN_NEW_PRODUCT = baseUrl + 'wp-admin/post-new.php?post_type=product';
const WP_ADMIN_WC_SETTINGS = baseUrl + 'wp-admin/admin.php?page=wc-settings&tab=';
const WP_ADMIN_PERMALINK_SETTINGS = baseUrl + 'wp-admin/options-permalink.php';

const SHOP_PAGE = baseUrl + 'shop';
const SHOP_PRODUCT_PAGE = baseUrl + '?p=';
const SHOP_CART_PAGE = baseUrl + 'cart';
const SHOP_CHECKOUT_PAGE = baseUrl + 'checkout/';
const SHOP_MY_ACCOUNT_PAGE = baseUrl + 'my-account/';

const MY_ACCOUNT_ORDERS = baseUrl + 'my-account/orders';
const MY_ACCOUNT_DOWNLOADS = baseUrl + 'my-account/downloads';
const MY_ACCOUNT_ADDRESSES = baseUrl + 'my-account/edit-address';
const MY_ACCOUNT_ACCOUNT_DETAILS = baseUrl + 'my-account/edit-account';

const getProductColumnExpression = ( productTitle ) => (
	'td[@class="product-name" and ' +
	`a[contains(text(), "${ productTitle }")]` +
	']'
);

const getQtyColumnExpression = ( args ) => (
	'td[@class="product-quantity" and ' +
	'.//' + getQtyInputExpression( args ) +
	']'
);

const getQtyInputExpression = ( args = {} ) => {
	let qtyValue = '';

	if ( args.checkQty ) {
		qtyValue = ` and @value="${ args.qty }"`;
	}

	return 'input[contains(@class, "input-text")' + qtyValue + ']';
};

const getCartItemExpression = ( productTitle, args ) => (
	'//tr[contains(@class, "cart_item") and ' +
	getProductColumnExpression( productTitle ) +
	' and ' +
	getQtyColumnExpression( args ) +
	']'
);

const getRemoveExpression = () => (
	'td[@class="product-remove"]//a[@class="remove"]'
);

const CustomerFlow = {
	addToCart: async () => {
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( '.single_add_to_cart_button' ),
		] );
	},

	addToCartFromShopPage: async ( productTitle ) => {
		const addToCartXPath = `//li[contains(@class, "type-product") and a/h2[contains(text(), "${ productTitle }")]]` +
			'//a[contains(@class, "add_to_cart_button") and contains(@class, "ajax_add_to_cart")';

		const [ addToCartButton ] = await page.$x( addToCartXPath + ']' );
		addToCartButton.click();

		await page.waitFor( addToCartXPath + ' and contains(@class, "added")]' );
	},

	goToCheckout: async () => {
		await page.goto( SHOP_CHECKOUT_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	goToOrders: async () => {
		await page.goto( MY_ACCOUNT_ORDERS, {
			waitUntil: 'networkidle0',
		} );
	},

	goToDownloads: async () => {
		await page.goto( MY_ACCOUNT_DOWNLOADS, {
			waitUntil: 'networkidle0',
		} );
	},

	goToAddresses: async () => {
		await page.goto( MY_ACCOUNT_ADDRESSES, {
			waitUntil: 'networkidle0',
		} );
	},

	goToAccountDetails: async () => {
		await page.goto( MY_ACCOUNT_ACCOUNT_DETAILS, {
			waitUntil: 'networkidle0',
		} );
	},

	goToProduct: async ( postID ) => {
		await page.goto( SHOP_PRODUCT_PAGE + postID, {
			waitUntil: 'networkidle0',
		} );
	},


	goToShop: async () => {
		await page.goto(SHOP_PAGE, {
			waitUntil: 'networkidle0',
		});
	},

	placeOrder: async () => {
		await Promise.all( [
			expect( page ).toClick( '#place_order' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	productIsInCheckout: async ( productTitle, quantity, total, cartSubtotal ) => {
		await expect( page ).toMatchElement( '.product-name', { text: productTitle } );
		await expect( page ).toMatchElement( '.product-quantity', { text: quantity } );
		await expect( page ).toMatchElement( '.product-total .amount', { text: total } );
		await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: cartSubtotal } );
	},

	goToCart: async () => {
		await page.goto( SHOP_CART_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	login: async () => {
		await page.goto( SHOP_MY_ACCOUNT_PAGE, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'My account' );

		await page.type( '#username', config.get('users.customer.username') );
		await page.type( '#password', config.get('users.customer.password') );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( 'button[name="login"]' ),
		] );
	},

	productIsInCart: async ( productTitle, quantity = null ) => {
		const cartItemArgs = quantity ? { qty: quantity } : {};
		const cartItemXPath = getCartItemExpression( productTitle, cartItemArgs );

		await expect( page.$x( cartItemXPath ) ).resolves.toHaveLength( 1 );
	},

	fillBillingDetails: async (	customerBillingDetails ) => {
		await expect( page ).toFill( '#billing_first_name', customerBillingDetails.firstname );
		await expect( page ).toFill( '#billing_last_name', customerBillingDetails.lastname );
		await expect( page ).toFill( '#billing_company', customerBillingDetails.company );
		await expect( page ).toSelect( '#billing_country', customerBillingDetails.country );
		await expect( page ).toFill( '#billing_address_1', customerBillingDetails.addressfirstline );
		await expect( page ).toFill( '#billing_address_2', customerBillingDetails.addresssecondline );
		await expect( page ).toFill( '#billing_city', customerBillingDetails.city );
		await expect( page ).toSelect( '#billing_state', customerBillingDetails.state );
		await expect( page ).toFill( '#billing_postcode', customerBillingDetails.postcode );
		await expect( page ).toFill( '#billing_phone', customerBillingDetails.phone );
		await expect( page ).toFill( '#billing_email', customerBillingDetails.email );
	},

	fillShippingDetails: async ( customerShippingDetails ) => {
		await expect( page ).toFill( '#shipping_first_name', customerShippingDetails.firstname );
		await expect( page ).toFill( '#shipping_last_name', customerShippingDetails.lastname );
		await expect( page ).toFill( '#shipping_company', customerShippingDetails.company );
		await expect( page ).toSelect( '#shipping_country', customerShippingDetails.country );
		await expect( page ).toFill( '#shipping_address_1', customerShippingDetails.addressfirstline );
		await expect( page ).toFill( '#shipping_address_2', customerShippingDetails.addresssecondline );
		await expect( page ).toFill( '#shipping_city', customerShippingDetails.city );
		await expect( page ).toSelect( '#shipping_state', customerShippingDetails.state );
		await expect( page ).toFill( '#shipping_postcode', customerShippingDetails.postcode );
	},

	removeFromCart: async ( productTitle ) => {
		const cartItemXPath = getCartItemExpression(productTitle);
		const removeItemXPath = cartItemXPath + '//' + getRemoveExpression();

		const [removeButton] = await page.$x(removeItemXPath);
		await removeButton.click();
	},

	setCartQuantity: async ( productTitle, quantityValue ) => {
		const cartItemXPath = getCartItemExpression( productTitle );
		const quantityInputXPath = cartItemXPath + '//' + getQtyInputExpression();

		const [ quantityInput ] = await page.$x( quantityInputXPath );
		await quantityInput.focus();
		await pressKeyWithModifier( 'primary', 'a' );
		await quantityInput.type( quantityValue.toString() );
	},
};


const StoreOwnerFlow = {
	login: async () => {
		await page.goto( WP_ADMIN_LOGIN, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Log In' );

		await clearAndFillInput( '#user_login', ' ' );

		await page.type( '#user_login', config.get( 'users.admin.username' ) );
		await page.type( '#user_pass', config.get( 'users.admin.password' ) );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	logout: async () => {
		// Log out link in admin bar is not visible so can't be clicked directly.
		const logoutLinks = await page.$$eval(
			'#wp-admin-bar-logout a',
			( am ) => am.filter( ( e ) => e.href ).map( ( e ) => e.href )
		);

		await page.goto( logoutLinks[ 0 ], {
			waitUntil: 'networkidle0',
		} );
	},

	openAllOrdersView: async () => {
		await page.goto( WP_ADMIN_ALL_ORDERS_VIEW, {
			waitUntil: 'networkidle0',
		} );
	},

	openDashboard: async () => {
		await page.goto( WP_ADMIN_DASHBOARD, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewCoupon: async () => {
		await page.goto( WP_ADMIN_NEW_COUPON, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewOrder: async () => {
		await page.goto( WP_ADMIN_NEW_ORDER, {
			waitUntil: 'networkidle0',
		} );
	},

	openNewProduct: async () => {
		await page.goto( WP_ADMIN_NEW_PRODUCT, {
			waitUntil: 'networkidle0',
		} );
	},

	openPermalinkSettings: async () => {
		await page.goto( WP_ADMIN_PERMALINK_SETTINGS, {
			waitUntil: 'networkidle0',
		} );
	},

	openPlugins: async () => {
		await page.goto( WP_ADMIN_PLUGINS, {
			waitUntil: 'networkidle0',
		} );
	},

	openSettings: async ( tab, section = null ) => {
		let settingsUrl = WP_ADMIN_WC_SETTINGS + tab;

		if ( section ) {
			settingsUrl += `&section=${ section }`;
		}

		await page.goto( settingsUrl, {
			waitUntil: 'networkidle0',
		} );
	},

	runSetupWizard: async () => {
		await page.goto( WP_ADMIN_SETUP_WIZARD, {
			waitUntil: 'networkidle0',
		} );
	},
};

export { CustomerFlow, StoreOwnerFlow };
