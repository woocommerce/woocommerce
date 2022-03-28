/**
 * External dependencies
 */
import {
	shopper as wcShopper,
	uiUnblocked,
	SHOP_CART_PAGE,
} from '@woocommerce/e2e-utils';
import { pressKeyWithModifier } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { BASE_URL } from '../e2e/utils';
import {
	getCartItemPathExpression,
	getQtyInputPathExpression,
	getQtyPlusButtonPathExpression,
	getQtyMinusButtonPathExpression,
} from './path-expressions';

export const shopper = {
	...wcShopper,

	// We use the .block property to avoid overriding any wcShopper functionality
	// This is important as we might one day merge this into core WC.
	block: {
		// All block pages have a title composed of the block name followed by "Block".
		// E.g. "Checkout Block or Mini Cart Block". The permalinks are generated from
		// the page title so we can derive them directly
		goToBlockPage: async ( blockName ) => {
			const pageTitle = `${ blockName } Block`;
			const url = BASE_URL + pageTitle.toLowerCase().replace( / /g, '-' );
			await page.goto( url, {
				waitUntil: 'networkidle0',
			} );

			await expect( page ).toMatchElement( 'h1', {
				text: blockName,
			} );
		},

		goToCart: async () => {
			await shopper.block.goToBlockPage( 'Cart' );
		},

		goToCheckout: async () => {
			await shopper.block.goToBlockPage( 'Checkout' );
		},

		productIsInCheckout: async ( productTitle, quantity, total ) => {
			// Make sure Order summary is expanded
			const [ button ] = await page.$x(
				`//button[contains(@aria-expanded, 'false')]//span[contains(text(), 'Order summary')]`
			);
			if ( button ) {
				await button.click();
			}
			await expect( page ).toMatchElement( 'span', {
				text: productTitle,
			} );
			await expect(
				page
			).toMatchElement(
				'div.wc-block-components-order-summary-item__quantity',
				{ text: quantity }
			);
			await expect( page ).toMatchElement(
				'span.wc-block-components-product-price__value',
				{
					text: total,
				}
			);
		},

		/**
		 * For some reason "wcShopper.emptyCart" sometimes result in an error, but using the same
		 * implementation here fixes the problem.
		 */
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

		placeOrder: async () => {
			await Promise.all( [
				expect( page ).toClick(
					'.wc-block-components-checkout-place-order-button',
					{
						text: 'Place Order',
					}
				),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			] );
		},

		/* We need to overwrite this function from wcShopper because clicking through to the
		product doesn't work. There is a fix in https://github.com/woocommerce/woocommerce/pull/31915
		We can delete this function once the PR is merged
		*/
		searchForProduct: async ( productname ) => {
			const searchFieldSelector = '.wp-block-search__input';
			await expect( page ).toMatchElement( searchFieldSelector );
			// await page.waitForSelector( searchFieldSelector, { timeout: 5000 } );
			await expect( page ).toFill( searchFieldSelector, productname );
			await expect( page ).toClick( '.wp-block-search__button' );
			// Single search results may go directly to product page
			if ( await page.waitForSelector( 'h2.entry-title' ) ) {
				await expect( page ).toMatchElement( 'h2.entry-title', {
					text: productname,
				} );
				await expect( page ).toClick( 'h2.entry-title > a', {
					text: productname,
				} );
			}
			await page.waitForSelector( 'h1.entry-title' );
			await expect( page.title() ).resolves.toMatch( productname );
			await expect( page ).toMatchElement(
				'h1.entry-title',
				productname
			);
		},

		addCoupon: async ( couponCode ) => {
			const title = await page.title();
			if ( ! title.includes( 'Cart Block' ) ) {
				await shopper.block.goToCart();
			}
			// Make sure the coupon panel is open
			const applyButton = await page.$(
				'.wc-block-components-totals-coupon__button'
			);
			if ( ! applyButton ) {
				await page.click( '.wc-block-components-panel__button' );
			}
			await page.type(
				'.wc-block-components-totals-coupon__input input',
				couponCode
			);
			await page.click( '.wc-block-components-totals-coupon__button' );
			await expect( page ).toMatchElement(
				'.wc-block-components-chip__text',
				{
					text: couponCode,
				}
			);
		},

		fillInCheckoutWithTestData: async () => {
			const shippingOrBilling = ( await page.$( '#shipping-first_name' ) )
				? 'shipping'
				: 'billing';
			const testData = {
				first_name: 'John',
				last_name: 'Doe',
				shipping_address_1: '123 Easy Street',
				country: 'United States (US)',
				city: 'New York',
				state: 'New York',
				postcode: '90210',
			};
			await shopper.block.fillInCheckoutAddress(
				testData,
				shippingOrBilling
			);
		},

		fillInCheckoutAddress: async (
			address,
			shippingOrBilling = 'shipping'
		) => {
			await expect( page ).toFill(
				`#${ shippingOrBilling }-first_name`,
				address.first_name
			);
			await expect( page ).toFill(
				`#${ shippingOrBilling }-first_name`,
				address.first_name
			);
			await expect( page ).toFill(
				`#${ shippingOrBilling }-last_name`,
				address.last_name
			);
			await expect( page ).toFill(
				`#${ shippingOrBilling }-address_1`,
				address.shipping_address_1
			);
			await expect( page ).toFill(
				`#${ shippingOrBilling }-country input`,
				address.country
			);
			await expect( page ).toFill(
				`#${ shippingOrBilling }-city`,
				address.city
			);
			await expect( page ).toFill(
				`#${ shippingOrBilling }-state input`,
				address.state
			);
			await expect( page ).toFill(
				`#${ shippingOrBilling }-postcode`,
				address.postcode
			);
		},

		fillBillingDetails: async ( customerBillingDetails ) => {
			await expect( page ).toFill(
				'#billing-first_name',
				customerBillingDetails.firstname
			);
			await expect( page ).toFill(
				'#billing-last_name',
				customerBillingDetails.lastname
			);
			await expect( page ).toFill(
				'#components-form-token-input-0',
				customerBillingDetails.country
			);
			await expect( page ).toFill(
				'#billing-address_1',
				customerBillingDetails.addressfirstline
			);
			await expect( page ).toFill(
				'#billing-address_2',
				customerBillingDetails.addresssecondline
			);
			await expect( page ).toFill(
				'#billing-city',
				customerBillingDetails.city
			);
			await expect( page ).toFill(
				'#components-form-token-input-2',
				customerBillingDetails.state
			);
			await expect( page ).toFill(
				'#billing-postcode',
				customerBillingDetails.postcode
			);
			await expect( page ).toFill(
				'#phone',
				customerBillingDetails.phone
			);
			await expect( page ).toFill(
				'#email',
				customerBillingDetails.email
			);
		},

		/**
		 * Instead of using the permalink to go to checkout (e.g. "shopper.block.goToCheckout"),
		 * with this method we actually click on the "Proceed to Checkout" button
		 */
		proceedToCheckout: async () => {
			await Promise.all( [
				expect( page ).toClick( 'a.wc-block-cart__submit-button', {
					text: 'Proceed to Checkout',
				} ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			] );
		},

		selectAndVerifyShippingOption: async (
			shippingName,
			shippingPrice
		) => {
			await expect( page ).toClick(
				'.wc-block-components-radio-control__label',
				{
					text: shippingName,
				}
			);
			await page.waitForTimeout( 1000 );
			await expect( page ).toMatchElement(
				'.wc-block-components-totals-shipping .wc-block-formatted-money-amount',
				{
					text: shippingPrice,
				}
			);
		},

		/* The express payment button added by Woo Pay or WC Stripe Gateway needs HTTPS and a logged in
			 account on Google Pay or other express payment methods. This is impossible in this env,
			 so instead we mock an express payment method using the `registerExpressPaymentMethod()` function
			 from the wc.wcBlocksRegistry global. This function needs to be run for each page navigation as the
			 update to the block resgistry is not persisted.

			 eslint-disable because we use global vars within the page context
		*/
		mockExpressPaymentMethod: async () => {
			await page.evaluate( () => {
				// eslint-disable-next-line
				const { registerExpressPaymentMethod } = wc.wcBlocksRegistry;
				registerExpressPaymentMethod( {
					name: 'mock_express_payment',
					// eslint-disable-next-line
					content: React.createElement(
						'div',
						[],
						[ 'Mock Express Payment' ]
					),
					// eslint-disable-next-line
					edit: React.createElement(
						'div',
						[],
						[ 'Mock Express Payment' ]
					),
					canMakePayment: () => true,
				} );
			} );
		},

		selectPayment: async ( payment ) => {
			await expect( page ).toClick(
				'.wc-block-components-payment-method-label',
				{
					text: payment,
				}
			);
		},

		setCartQuantity: async ( productTitle, quantityValue ) => {
			const cartItemXPath = getCartItemPathExpression( productTitle );
			const quantityInputXPath =
				cartItemXPath + '//' + getQtyInputPathExpression();

			const [ quantityInput ] = await page.$x( quantityInputXPath );
			await quantityInput.focus();
			await pressKeyWithModifier( 'primary', 'a' );
			await quantityInput.type( quantityValue.toString() );
			await quantityInput.evaluate( ( e ) => e.blur() );
		},

		increaseCartQuantityByOne: async ( productTitle ) => {
			const cartItemXPath = getCartItemPathExpression( productTitle );

			const quantityPlusButtonXPath =
				cartItemXPath + '//' + getQtyPlusButtonPathExpression();

			const [ quantityPlusButton ] = await page.$x(
				quantityPlusButtonXPath
			);
			await quantityPlusButton.click();
		},

		decreaseCartQuantityByOne: async ( productTitle ) => {
			const cartItemXPath = getCartItemPathExpression( productTitle );
			const quantityMinusButtonXPath =
				cartItemXPath + '//' + getQtyMinusButtonPathExpression();

			const [ quantityMinusButton ] = await page.$x(
				quantityMinusButtonXPath
			);
			await quantityMinusButton.click();
		},

		productIsInCart: async ( productTitle, quantity = null ) => {
			const cartItemArgs = quantity ? { qty: quantity } : {};
			const cartItemXPath = getCartItemPathExpression(
				productTitle,
				cartItemArgs
			);

			await expect( page.$x( cartItemXPath ) ).resolves.toHaveLength( 1 );
		},
	},

	isLoggedIn: async () => {
		await shopper.gotoMyAccount();

		await expect( page.title() ).resolves.toMatch( 'My account' );
		const loginForm = await page.$( 'form.woocommerce-form-login' );

		return ! loginForm;
	},

	loginFromMyAccountPage: async ( username, password ) => {
		await page.type( '#username', username );
		await page.type( '#password', password );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( 'button[name="login"]' ),
		] );
	},
};
