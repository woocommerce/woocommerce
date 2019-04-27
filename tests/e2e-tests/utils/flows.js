/**
 * @format
 */

/** 
 * External dependencies
 */
const config = require( 'config' );
// NOTE: change this to @wordpress/e2e-test-utils when it gets updated.
const { pressKeyWithModifier } = require( './press-key-with-modifier' );

const baseUrl = config.get( 'url' );

const WP_ADMIN_LOGIN = baseUrl + 'wp-login.php';
const WP_ADMIN_NEW_COUPON = baseUrl + 'wp-admin/post-new.php?post_type=shop_coupon';
const WP_ADMIN_NEW_ORDER = baseUrl + 'wp-admin/post-new.php?post_type=shop_order';
const WP_ADMIN_NEW_PRODUCT = baseUrl + 'wp-admin/post-new.php?post_type=product';
const WP_ADMIN_WC_SETTINGS = baseUrl + 'wp-admin/admin.php?page=wc-settings&tab=';

const SHOP_PAGE = baseUrl + 'shop/';
const SHOP_PRODUCT = baseUrl + 'product/';
const SHOP_CART_PAGE = baseUrl + 'cart/';
const SHOP_CHECKOUT_PAGE = baseUrl + 'checkout/';
const SHOP_MY_ACCOUNT_PAGE = baseUrl + 'my-account/';

const getCartItemExpression = ( productTitle, args ) => (
	'//tr[contains(@class, "cart_item") and ' +
		getProductColumnExpression( productTitle ) +
		' and ' +
		getQtyColumnExpression( args ) +
	']'
);

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

	goToProduct: async ( productSlug ) => {
		await page.goto( SHOP_PRODUCT + productSlug, {
			waitUntil: 'networkidle0',
		} );
	},

	goToCart: async () => {
		await page.goto( SHOP_CART_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	goToCheckout: async () => {
		await page.goto( SHOP_CHECKOUT_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	goToShop: async () => {
		await page.goto( SHOP_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	login: async () => {
        await page.goto( SHOP_MY_ACCOUNT_PAGE, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'My account' );

		await page.type( '#username', config.get( 'users.customer.username' ) );
		await page.type( '#password', config.get( 'users.customer.password' ) );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( 'button[name="login"]' ),
		] );
	},

	placeOrder: async () => {
		await Promise.all( [
			expect( page ).toClick( '#place_order' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
        ] );
	},

	productIsInCart: async ( productTitle, quantity = null ) => {
		const cartItemArgs = quantity ? { qty: quantity } : {};
		const cartItemXPath = getCartItemExpression( productTitle, cartItemArgs );

		await expect( page.$x( cartItemXPath ) ).resolves.toHaveLength( 1 );
	},

	productIsInCheckout: async ( productTitle, quantity, total ) => {
		const checkoutItemXPath =
			'//tr[@class="cart_item" and ' +
				`.//td[contains(., "${ productTitle }") and contains(., "× ${ quantity }")] and ` +
				`.//td[contains(., "${ total }")]` +
			']';

		await expect( page.$x( checkoutItemXPath ) ).resolves.toHaveLength( 1 );
	},

	removeCartProduct: async ( productTitle ) => {
		const cartItemXPath = getCartItemExpression( productTitle );
		const removeItemXPath = cartItemXPath + '//' + getRemoveExpression();

		const [ removeButton ] = await page.$x( removeItemXPath );
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

		await page.type( '#user_login', config.get( 'users.admin.username' ) );
		await page.type( '#user_pass', config.get( 'users.admin.password' ) );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	logout: async () => {
		await page.goto( baseUrl + 'wp-login.php?action=logout', {
			waitUntil: 'networkidle0',
		} );

		await expect( page ).toMatch( 'You are attempting to log out' );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( 'a' ),
		] );
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

	openSettings: async ( tab, section = null ) => {
		let settingsUrl = WP_ADMIN_WC_SETTINGS + tab;

		if ( section ) {
			settingsUrl += `&section=${ section }`;
		}

		await page.goto( settingsUrl, {
			waitUntil: 'networkidle0',
		} );
	},
};

module.exports = {
	CustomerFlow,
    StoreOwnerFlow,
};
