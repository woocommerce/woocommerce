import { clickTab } from "./index";

/**
 * @format
 */

const baseUrl = process.env.WP_BASE_URL;

const WP_ADMIN_LOGIN = baseUrl + '/wp-login.php';
const WP_ADMIN_DASHBOARD = baseUrl + '/wp-admin/';
const WP_ADMIN_SETUP_WIZARD = baseUrl + '/wp-admin/admin.php?page=wc-setup';
const WP_ADMIN_NEW_COUPON = baseUrl + '/wp-admin/post-new.php?post_type=shop_coupon';
const WP_ADMIN_NEW_ORDER = baseUrl + '/wp-admin/post-new.php?post_type=shop_order';
const WP_ADMIN_NEW_PRODUCT = baseUrl + '/wp-admin/post-new.php?post_type=product';
const WP_ADMIN_WC_SETTINGS = baseUrl + '/wp-admin/admin.php?page=wc-settings&tab=';
const WP_ADMIN_PERMALINK_SETTINGS = baseUrl + '/wp-admin/options-permalink.php/';

const SHOP_PRODUCT = baseUrl + '/?p=';
const SHOP_CART_PAGE = baseUrl + '/cart/';

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

	removeFromCart: async ( productTitle ) => {
		const cartItemXPath = getCartItemExpression( productTitle );
		const removeItemXPath = cartItemXPath + '//' + getRemoveExpression();

		const [ removeButton ] = await page.$x( removeItemXPath );
		await removeButton.click();
	},

	goToProduct: async ( postID ) => {
		await page.goto( SHOP_PRODUCT + postID, {
			waitUntil: 'networkidle0',
		} );
	},

	goToCart: async () => {
		await page.goto( SHOP_CART_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	productIsInCart: async ( productTitle, quantity = null ) => {
		const cartItemArgs = quantity ? { qty: quantity } : {};
		const cartItemXPath = getCartItemExpression( productTitle, cartItemArgs );

		await expect( page.$x( cartItemXPath ) ).resolves.toHaveLength( 1 );
	},

};

const StoreOwnerFlow = {
	login: async () => {
		await page.goto( WP_ADMIN_LOGIN, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Log In' );

		await page.type( '#user_login', 'admin' );
		await page.type( '#user_pass', 'password' );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	logout: async () => {
		await page.goto(baseUrl + '/wp-login.php?action=logout', {
			waitUntil: 'networkidle0',
		});

		await expect(page).toMatch('You are attempting to log out');

		await Promise.all([
			page.waitForNavigation({ waitUntil: 'networkidle0' }),
			page.click('a'),
		]);
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
