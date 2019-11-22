import { clickTab } from "./index";

/**
 * @format
 */

const baseUrl = process.env.WP_BASE_URL;

const WP_ADMIN_NEW_PRODUCT = baseUrl + '/wp-admin/post-new.php?post_type=product';
const WP_ADMIN_WC_SETTINGS = baseUrl + '/wp-admin/admin.php?page=wc-settings&tab=';
const WP_ADMIN_NEW_COUPON = baseUrl + '/wp-admin/post-new.php?post_type=shop_coupon';
const WP_ADMIN_NEW_ORDER = baseUrl + '/wp-admin/post-new.php?post_type=shop_order';

const SHOP_PAGE = baseUrl + '/shop/';
const SHOP_PRODUCT = baseUrl + '/?p=';
const SHOP_CART_PAGE = baseUrl + '/cart/';
const SHOP_CHECKOUT_PAGE = baseUrl + '/checkout/';

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

	productIsInCart: async ( productTitle, quantity = null ) => {
		const cartItemArgs = quantity ? { qty: quantity } : {};
		const cartItemXPath = getCartItemExpression( productTitle, cartItemArgs );

		await expect( page.$x( cartItemXPath ) ).resolves.toHaveLength( 1 );
	},

	fillBillingDetails: async (
		firstName,
		lastName,
		company,
		country,
		addressFirst,
		addressSecond,
		city,
		state,
		postcode,
		phone,
		email
	) => {
		await expect( page ).toFill( '#billing_first_name', firstName );
		await expect( page ).toFill( '#billing_last_name', lastName );
		await expect( page ).toFill( '#billing_company', company );
		await expect( page ).toSelect( '#billing_country', country );
		await expect( page ).toFill( '#billing_address_1', addressFirst );
		await expect( page ).toFill( '#billing_address_2', addressSecond );
		await expect( page ).toFill( '#billing_city', city );
		await expect( page ).toSelect( '#billing_state', state );
		await expect( page ).toFill( '#billing_postcode', postcode );
		await expect( page ).toFill( '#billing_phone', phone );
		await expect( page ).toFill( '#billing_email', email );
	},

	fillShippingDetails: async (
		firstName,
		lastName,
		company,
		country,
		addressFirst,
		addressSecond,
		city,
		state,
		postcode
	) => {
		await expect( page ).toFill( '#shipping_first_name', firstName );
		await expect( page ).toFill( '#shipping_last_name', lastName );
		await expect( page ).toFill( '#shipping_company', company );
		await expect( page ).toSelect( '#shipping_country', country );
		await expect( page ).toFill( '#shipping_address_1', addressFirst );
		await expect( page ).toFill( '#shipping_address_2', addressSecond );
		await expect( page ).toFill( '#shipping_city', city );
		await expect( page ).toSelect( '#shipping_state', state );
		await expect( page ).toFill( '#shipping_postcode', postcode );
	},

};

const StoreOwnerFlow = {
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

export { CustomerFlow, StoreOwnerFlow };
