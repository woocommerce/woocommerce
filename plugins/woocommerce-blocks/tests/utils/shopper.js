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
import { BASE_URL } from '../e2e-jest/utils';
import {
	getCartItemPathExpression,
	getQtyInputPathExpression,
	getQtyPlusButtonPathExpression,
	getQtyMinusButtonPathExpression,
} from './path-expressions';

const checkCustomerPushCompleted = async (
	shippingOrBilling,
	addressToCheck
) => {
	// Blur active field to trigger customer information update, then wait for requests to finish.
	await page.evaluate( 'document.activeElement.blur()' );

	await page.waitForResponse( async ( response ) => {
		const isBatch = response.url().includes( '/wp-json/wc/store/v1/batch' );
		const responseJson = await response.text();
		const parsedResponse = JSON.parse( responseJson );
		if ( ! Array.isArray( parsedResponse?.responses ) || ! isBatch ) {
			return false;
		}

		const keyToCheck =
			shippingOrBilling === 'shipping'
				? 'shipping_address'
				: 'billing_address';

		return parsedResponse.responses.some( ( singleResponse ) => {
			const firstname =
				singleResponse.body[ keyToCheck ].first_name ===
				addressToCheck.firstname;
			const lastname =
				singleResponse.body[ keyToCheck ].last_name ===
				addressToCheck.lastname;
			const address1 =
				singleResponse.body[ keyToCheck ].address_1 ===
				addressToCheck.addressfirstline;
			const address2 =
				singleResponse.body[ keyToCheck ].address_2 ===
				addressToCheck.addresssecondline;
			const postcode =
				singleResponse.body[ keyToCheck ].postcode ===
				addressToCheck.postcode;
			const city =
				singleResponse.body[ keyToCheck ].city === addressToCheck.city;
			const phone =
				singleResponse.body[ keyToCheck ].phone ===
				addressToCheck.phone;
			const email =
				shippingOrBilling === 'billing'
					? singleResponse.body[ keyToCheck ].email ===
					  addressToCheck.email
					: true;

			// Note, we skip checking State and Country here because the value returned by the server is not the same as
			// what gets input into the form. The server returns the code, but the form accepts the full name.
			return (
				firstname &&
				lastname &&
				address1 &&
				address2 &&
				postcode &&
				city &&
				phone &&
				email
			);
		} );
	} );
	await page.waitForTimeout( 2000 );
};

export const shopper = {
	...wcShopper,

	// We use the .block property to avoid overriding any wcShopper functionality
	// This is important as we might one day merge this into core WC.
	block: {
		// All block pages have a title composed of the block name followed by "Block".
		// E.g. "Checkout Block or Mini-Cart Block". The permalinks are generated from
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

			// Wait for form to be hidden.
			await page.waitForSelector( '.woocommerce-cart-form', {
				hidden: true,
			} );
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

		fillInCheckoutWithTestData: async ( overrideData = {} ) => {
			const shippingOrBilling = ( await page.$( '#shipping-first_name' ) )
				? 'shipping'
				: 'billing';
			const testData = {
				...{
					firstname: 'John',
					lastname: 'Doe',
					addressfirstline: '123 Easy Street',
					addresssecondline: 'Testville',
					country: 'United States (US)',
					city: 'New York',
					state: 'New York',
					postcode: '90210',
					email: 'john.doe@test.com',
					phone: '01234567890',
				},
				...overrideData,
			};
			await expect( page ).toFill( `#email`, testData.email );
			if ( shippingOrBilling === 'shipping' ) {
				await shopper.block.fillShippingDetails( testData );
			} else {
				await shopper.block.fillBillingDetails( testData );
			}
			// Blur active field to trigger shipping rates update, then wait for requests to finish.
			await page.evaluate( 'document.activeElement.blur()' );
		},
		// prettier-ignore
		fillBillingDetails: async ( customerBillingDetails ) => {
			await page.waitForSelector( '#billing-fields' );
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

			const stateInputField = await page.$( '#billing-state input' );
			if ( stateInputField ) {
				await expect( page ).toFill( '#billing-state input', customerBillingDetails.state );
			}
			await expect( page ).toFill( '#billing-postcode', customerBillingDetails.postcode );
			await expect( page ).toFill( '#billing-phone', customerBillingDetails.phone );
			await expect( page ).toFill( '#email', customerBillingDetails.email );
			// Blur active field to trigger customer address update, then wait for requests to finish.
			await page.evaluate( 'document.activeElement.blur()' );
			await checkCustomerPushCompleted( 'billing', customerBillingDetails );

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
			const stateInputField = await page.$( '#shipping-state input' );
			if ( stateInputField ) {
				await expect( page ).toFill( '#shipping-state input', customerShippingDetails.state );
			}
			await expect( page ).toFill( '#shipping-postcode', customerShippingDetails.postcode );
			await expect( page ).toFill( '#shipping-phone', customerShippingDetails.phone );
			// Blur active field to customer address update, then wait for requests to finish.
			await page.evaluate( 'document.activeElement.blur()' );
			await checkCustomerPushCompleted( 'shipping', customerShippingDetails );
		},

		// prettier-ignore
		verifyBillingDetails: async ( customerBillingDetails, selector = '.woocommerce-column--billing-address' ) => {
			await page.waitForSelector( selector );
			await Promise.all( [
				expect( page ).toMatch(
					customerBillingDetails.firstname
				),
				expect( page ).toMatch( customerBillingDetails.lastname),
				// expect( page ).toMatch( customerBillingDetails.company),
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
				// expect( page ).toMatch( customerShippingDetails.company),
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
				'.wp-block-cart-cross-sells-product__product-add-to-cart .wc-block-components-product-button__button'
			);
			expect( page ).toClick(
				'.wp-block-cart-cross-sells-product__product-add-to-cart .wc-block-components-product-button__button'
			);
		},

		selectAndVerifyShippingOption: async (
			shippingName,
			shippingPrice
		) => {
			await page.waitForNetworkIdle( { idleTime: 1000 } );
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
			// As no dom elements are being added or removed, we cannot use `await page.waitForSelector()`
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
