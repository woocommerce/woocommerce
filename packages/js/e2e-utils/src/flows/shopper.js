/**
 * External dependencies
 */
const { pressKeyWithModifier } = require( '@wordpress/e2e-test-utils' );
const config = require( 'config' );

/**
 * Internal dependencies
 */
const {
	getQtyInputExpression,
	getCartItemExpression,
	getRemoveExpression,
} = require( './expressions' );
const {
	MY_ACCOUNT_ADDRESSES,
	MY_ACCOUNT_ACCOUNT_DETAILS,
	MY_ACCOUNT_DOWNLOADS,
	MY_ACCOUNT_ORDERS,
	SHOP_MY_ACCOUNT_PAGE,
	SHOP_CART_PAGE,
	SHOP_CHECKOUT_PAGE,
	SHOP_PAGE,
	SHOP_PRODUCT_PAGE,
} = require( './constants' );

const { uiUnblocked, clickAndWaitForSelector } = require( '../page-utils' );

const gotoMyAccount = async () => {
	await page.goto( SHOP_MY_ACCOUNT_PAGE, {
		waitUntil: 'networkidle0',
	} );
};

const shopper = {
	addToCart: async () => {
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( '.single_add_to_cart_button' ),
		] );
	},

	addToCartFromShopPage: async ( productIdOrTitle ) => {
		if ( Number.isInteger( productIdOrTitle ) ) {
			const addToCart = `a[data-product_id="${ productIdOrTitle }"]`;
			await page.click( addToCart );
			await expect( page ).toMatchElement( addToCart + '.added' );
		} else {
			const addToCartXPath =
				`//li[contains(@class, "type-product") and a/h2[contains(text(), "${ productIdOrTitle }")]]` +
				'//a[contains(@class, "add_to_cart_button") and contains(@class, "ajax_add_to_cart")';

			const [ addToCartButton ] = await page.$x( addToCartXPath + ']' );
			await addToCartButton.click();

			// @todo: Update to waitForXPath when available in Puppeteer api.
			await page.waitFor(
				addToCartXPath + ' and contains(@class, "added")]'
			);
		}
	},

	goToCheckout: async () => {
		await page.goto( SHOP_CHECKOUT_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	goToProduct: async ( postID ) => {
		await page.goto( SHOP_PRODUCT_PAGE + postID, {
			waitUntil: 'networkidle0',
		} );
	},

	goToShop: async () => {
		await page.goto( SHOP_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	placeOrder: async () => {
		await Promise.all( [
			expect( page ).toClick( '#place_order' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	productIsInCheckout: async (
		productTitle,
		quantity,
		total,
		cartSubtotal
	) => {
		await expect( page ).toMatchElement( '.product-name', {
			text: productTitle,
		} );
		await expect( page ).toMatchElement( '.product-quantity', {
			text: quantity,
		} );
		await expect( page ).toMatchElement( '.product-total .amount', {
			text: total,
		} );
		await expect( page ).toMatchElement( '.cart-subtotal .amount', {
			text: cartSubtotal,
		} );
	},

	goToCart: async () => {
		await page.goto( SHOP_CART_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	productIsInCart: async ( productTitle, quantity = null ) => {
		const cartItemArgs = quantity ? { qty: quantity } : {};
		const cartItemXPath = getCartItemExpression(
			productTitle,
			cartItemArgs
		);

		await expect( page.$x( cartItemXPath ) ).resolves.toHaveLength( 1 );
	},

	fillBillingDetails: async ( customerBillingDetails ) => {
		await expect( page ).toFill(
			'#billing_first_name',
			customerBillingDetails.firstname
		);
		await expect( page ).toFill(
			'#billing_last_name',
			customerBillingDetails.lastname
		);
		await expect( page ).toFill(
			'#billing_company',
			customerBillingDetails.company
		);
		await expect( page ).toSelect(
			'#billing_country',
			customerBillingDetails.country
		);
		await expect( page ).toFill(
			'#billing_address_1',
			customerBillingDetails.addressfirstline
		);
		await expect( page ).toFill(
			'#billing_address_2',
			customerBillingDetails.addresssecondline
		);
		await expect( page ).toFill(
			'#billing_city',
			customerBillingDetails.city
		);
		if ( customerBillingDetails.state ) {
			await expect( page ).toSelect(
				'#billing_state',
				customerBillingDetails.state
			);
		}
		await expect( page ).toFill(
			'#billing_postcode',
			customerBillingDetails.postcode
		);
		await expect( page ).toFill(
			'#billing_phone',
			customerBillingDetails.phone
		);
		await expect( page ).toFill(
			'#billing_email',
			customerBillingDetails.email
		);
	},

	fillShippingDetails: async ( customerShippingDetails ) => {
		await expect( page ).toFill(
			'#shipping_first_name',
			customerShippingDetails.firstname
		);
		await expect( page ).toFill(
			'#shipping_last_name',
			customerShippingDetails.lastname
		);
		await expect( page ).toFill(
			'#shipping_company',
			customerShippingDetails.company
		);
		await expect( page ).toSelect(
			'#shipping_country',
			customerShippingDetails.country
		);
		await expect( page ).toFill(
			'#shipping_address_1',
			customerShippingDetails.addressfirstline
		);
		await expect( page ).toFill(
			'#shipping_address_2',
			customerShippingDetails.addresssecondline
		);
		await expect( page ).toFill(
			'#shipping_city',
			customerShippingDetails.city
		);
		if ( customerShippingDetails.state ) {
			await expect( page ).toSelect(
				'#shipping_state',
				customerShippingDetails.state
			);
		}
		await expect( page ).toFill(
			'#shipping_postcode',
			customerShippingDetails.postcode
		);
	},

	removeFromCart: async ( productIdOrTitle ) => {
		if ( Number.isInteger( productIdOrTitle ) ) {
			await page.click( `a[data-product_id="${ productIdOrTitle }"]` );
		} else {
			const cartItemXPath = getCartItemExpression( productIdOrTitle );
			const removeItemXPath =
				cartItemXPath + '//' + getRemoveExpression();

			const [ removeButton ] = await page.$x( removeItemXPath );
			await removeButton.click();
		}
	},

	emptyCart: async () => {
		await page.goto( SHOP_CART_PAGE, {
			waitUntil: 'networkidle0',
		} );

		// Remove products if they exist
		if ( ( await page.$( '.remove' ) ) !== null ) {
			let products = await page.$$( '.remove' );
			while ( products && products.length > 0 ) {
				await page.click( '.remove' );
				await uiUnblocked();
				products = await page.$$( '.remove' );
			}
		}

		// Remove coupons if they exist
		if ( ( await page.$( '.woocommerce-remove-coupon' ) ) !== null ) {
			await page.click( '.woocommerce-remove-coupon' );
			await uiUnblocked();
		}

		await page.waitForSelector( '.woocommerce-info' );
		// eslint-disable-next-line jest/no-standalone-expect
		await expect( page ).toMatchElement( '.woocommerce-info', {
			text: 'Your cart is currently empty.',
		} );
	},

	setCartQuantity: async ( productTitle, quantityValue ) => {
		const cartItemXPath = getCartItemExpression( productTitle );
		const quantityInputXPath =
			cartItemXPath + '//' + getQtyInputExpression();

		const [ quantityInput ] = await page.$x( quantityInputXPath );
		await quantityInput.focus();
		await pressKeyWithModifier( 'primary', 'a' );
		await quantityInput.type( quantityValue.toString() );
	},

	searchForProduct: async ( prouductName ) => {
		const searchFieldSelector = 'input.wp-block-search__input';
		await page.waitForSelector( searchFieldSelector, { timeout: 100000 } );
		await expect( page ).toFill( searchFieldSelector, prouductName );
		await expect( page ).toClick( '.wp-block-search__button' );
		// Single search results may go directly to product page
		if ( await page.waitForSelector( 'h2.entry-title' ) ) {
			await expect( page ).toMatchElement( 'h2.entry-title', {
				text: prouductName,
			} );
			await expect( page ).toClick( 'h2.entry-title > a', {
				text: prouductName,
			} );
		}
		await page.waitForSelector( 'h1.entry-title' );
		await expect( page.title() ).resolves.toMatch( prouductName );
		await expect( page ).toMatchElement( 'h1.entry-title', prouductName );
	},

	/*
	 * My Accounts flows.
	 */
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

	gotoMyAccount,

	login: async () => {
		await gotoMyAccount();

		await expect( page.title() ).resolves.toMatch( 'My account' );

		await page.type( '#username', config.get( 'users.customer.username' ) );
		await page.type( '#password', config.get( 'users.customer.password' ) );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( 'button[name="login"]' ),
		] );
	},
	logout: async () => {
		await gotoMyAccount();

		await expect( page.title() ).resolves.toMatch( 'My account' );
		await page.click(
			'.woocommerce-MyAccount-navigation-link--customer-logout a'
		);
	},
};

module.exports = shopper;
