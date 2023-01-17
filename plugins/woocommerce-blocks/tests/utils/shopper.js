/**
 * External dependencies
 */
import {
	shopper as wcShopper,
	uiUnblocked,
	SHOP_CART_PAGE,
	SHOP_PAGE,
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
		},

		goToShop: async () => {
			await page.goto( SHOP_PAGE );
			// Wait for Shop block to finish loading, otherwise we get flakey tests
			await page.waitForSelector( '.add_to_cart_button' );
		},

		goToCart: async () => {
			await shopper.block.goToBlockPage( 'Cart' );
			// Wait for Cart block to finish loading, otherwise we get flakey tests
			await page.waitForSelector(
				'.wp-block-woocommerce-cart:not(.is-loading)'
			);
		},

		goToCheckout: async () => {
			await shopper.block.goToBlockPage( 'Checkout' );
			// Wait for Checkout block to finish loading, otherwise we get flakey tests
			await page.waitForSelector(
				'.wp-block-woocommerce-checkout:not(.is-loading)'
			);
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
			await expect( page ).toMatchElement(
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
			await expect( page ).toMatchElement(
				'.woocommerce-info.cart-empty'
			);
		},

		placeOrder: async () => {
			// Wait for payment methods to be shown, otherwise we get flakey tests
			await page.waitForSelector(
				'.wc-block-components-payment-method-label'
			);
			// Wait for place order button to be clickable, otherwise we get flakey tests
			await page.waitForSelector(
				'.wc-block-components-checkout-place-order-button:not([disabled])'
			);
			await Promise.all( [
				page.click(
					'.wc-block-components-checkout-place-order-button'
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
				email: 'john.doe@test.com',
			};
			await expect( page ).toFill( `#email`, testData.email );
			await shopper.block.fillInCheckoutAddress(
				testData,
				shippingOrBilling
			);
		},

		// prettier-ignore
		fillInCheckoutAddress: async (
			address,
			shippingOrBilling = 'shipping'
		) => {
			await expect( page ).toFill( `#${ shippingOrBilling }-first_name`, address.first_name );
			await expect( page ).toFill( `#${ shippingOrBilling }-first_name`, address.first_name );
			await expect( page ).toFill( `#${ shippingOrBilling }-last_name`, address.last_name );
			await expect( page ).toFill( `#${ shippingOrBilling }-address_1`, address.shipping_address_1 );
			await expect( page ).toFill( `#${ shippingOrBilling }-country input`, address.country );
			await expect( page ).toFill( `#${ shippingOrBilling }-city`, address.city );
			await expect( page ).toFill( `#${ shippingOrBilling }-state input`, address.state );
			await expect( page ).toFill( `#${ shippingOrBilling }-postcode`, address.postcode );
		},

		// prettier-ignore
		fillBillingDetails: async ( customerBillingDetails ) => {
			await page.waitForSelector("#billing-fields");
			const companyInputField = await page.$( '#billing-company' );

			if ( companyInputField ) {
				await expect( page ).toFill( '#billing-company', customerBillingDetails.company );
			}

			await expect( page ).toFill( '#billing-first_name', customerBillingDetails.firstname );
			await expect( page ).toFill( '#billing-last_name', customerBillingDetails.lastname );
			await expect( page ).toFill( '#billing-country input', customerBillingDetails.country );
			await expect( page ).toFill( '#billing-address_1', customerBillingDetails.addressfirstline );
			await expect( page ).toFill( '#billing-address_2', customerBillingDetails.addresssecondline );
			await expect( page ).toFill( '#billing-city', customerBillingDetails.city );
			await expect( page ).toFill( '#billing-state input', customerBillingDetails.state );
			await expect( page ).toFill( '#billing-postcode', customerBillingDetails.postcode );
			await expect( page ).toFill( '#phone', customerBillingDetails.phone );
			await expect( page ).toFill( '#email', customerBillingDetails.email );
		},

		// prettier-ignore
		fillShippingDetails: async ( customerShippingDetails ) => {
			const companyInputField = await page.$( '#shipping-company' );

			if ( companyInputField ) {
				await expect( page ).toFill( '#shipping-company', customerShippingDetails.company );
			}

			await expect( page ).toFill( '#shipping-first_name', customerShippingDetails.firstname );
			await expect( page ).toFill( '#shipping-last_name', customerShippingDetails.lastname );
			await expect( page ).toFill( '#shipping-country input', customerShippingDetails.country );
			await expect( page ).toFill( '#shipping-address_1', customerShippingDetails.addressfirstline );
			await expect( page ).toFill( '#shipping-address_2', customerShippingDetails.addresssecondline );
			await expect( page ).toFill( '#shipping-city', customerShippingDetails.city );
			await expect( page ).toFill( '#shipping-state input', customerShippingDetails.state );
			await expect( page ).toFill( '#shipping-postcode', customerShippingDetails.postcode );
			await expect( page ).toFill( '#shipping-phone', customerShippingDetails.phone );
		},

		// prettier-ignore
		verifyBillingDetails: async ( customerBillingDetails ) => {
			await page.waitForSelector( '.woocommerce-column--billing-address' );
			await Promise.all( [
				expect( page ).toMatch(
					customerBillingDetails.firstname
				),
				expect( page ).toMatch( customerBillingDetails.lastname),
				expect( page ).toMatch( customerBillingDetails.company),
				expect( page ).toMatch(
					customerBillingDetails.addressfirstline
				),
				expect( page ).toMatch(
					customerBillingDetails.addresssecondline
				),
				// expect( page ).toMatch( customerBillingDetails.country ),
				expect( page ).toMatch( customerBillingDetails.city),
				expect( page ).toMatch( customerBillingDetails.state),
				expect( page ).toMatch( customerBillingDetails.postcode),
				expect( page ).toMatch( customerBillingDetails.phone),
			] );
		},

		// prettier-ignore
		verifyShippingDetails: async ( customerShippingDetails ) => {
			await page.waitForSelector(
				'.woocommerce-column--shipping-address'
			);
			await Promise.all( [
				expect( page ).toMatch(
					customerShippingDetails.firstname
				),
				expect( page ).toMatch(
					customerShippingDetails.lastname
				),
				expect( page ).toMatch( customerShippingDetails.company),
				expect( page ).toMatch(
					customerShippingDetails.addressfirstline
				),
				expect( page ).toMatch(
					customerShippingDetails.addresssecondline
				),
				// expect( page ).toMatch( customerShippingDetails.country ),
				expect( page ).toMatch( customerShippingDetails.city),
				expect( page ).toMatch( customerShippingDetails.state),
				expect( page ).toMatch(
					customerShippingDetails.postcode
				),
				expect( page ).toMatch( customerShippingDetails.phone),
			] );
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

		addCrossSellsProductToCart: async () => {
			await page.waitForSelector(
				'.wc-block-components-product-add-to-cart-button'
			);
			expect( page ).toClick(
				'.wc-block-components-product-add-to-cart-button'
			);
		},

		selectAndVerifyShippingOption: async (
			shippingName,
			shippingPrice
		) => {
			await page.waitForSelector(
				'.wc-block-components-radio-control__label'
			);
			await expect( page ).toClick(
				'.wc-block-components-radio-control__label',
				{
					text: shippingName,
				}
			);
			//eslint-disable-next-line no-shadow
			const checkIfShippingHasChanged = ( el, shippingName ) => {
				const checkShippingTotal = () => {
					if ( el.textContent === `via ${ shippingName }` ) {
						clearInterval( intervalId );
						clearTimeout( timeoutId );
					}
				};
				const intervalId = setInterval( checkShippingTotal, 500 );
				const timeoutId = setInterval( () => {
					clearInterval( intervalId );
				}, 30000 );
			};

			// We need to wait for the shipping total to update before we assert.
			// As no dom elements are being added or removed, we cannot use `await page.waitForSelectot()`
			// so instead we check when the `via <Shipping Method>` text changes
			await page.$eval(
				'.wc-block-components-totals-shipping .wc-block-components-totals-shipping__via',
				checkIfShippingHasChanged,
				shippingName
			);

			await expect( page ).toMatchElement(
				'.wc-block-components-totals-shipping .wc-block-formatted-money-amount',
				{
					text: shippingPrice,
					timeout: 30000,
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

		applyCouponFromCheckout: async ( couponCode ) => {
			const couponInputSelector =
				'#wc-block-components-totals-coupon__input-0';
			const couponApplyButtonSelector =
				'.wc-block-components-totals-coupon__button';
			const addCouponLinkSelector =
				'.wc-block-components-totals-coupon-link';

			await expect( page ).toClick( addCouponLinkSelector );
			await expect( page ).toFill( couponInputSelector, couponCode );
			await expect( page ).toClick( couponApplyButtonSelector );
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

			await page.waitForXPath(
				addToCartXPath + ' and contains(@class, "added")]'
			);
		}
	},
};
