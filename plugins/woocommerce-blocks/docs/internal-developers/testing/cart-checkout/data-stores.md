# Testing

## Payments

In general, perform smoke tests, ensure you can check out with carts containing:

-   Only physical products,
-   Only virtual products,
-   A mix of physical and virtual products,
-   Products on sale,
-   Products with different tax rates.
-   Check out using coupons, and the amount charged to the payment method is correct.

Ensure the amount charged is correct.

### Subscriptions

-   Check out using WooCommerce Subscriptions products, ensure the checkout works and the stripe key is saved to the order.
-   Go to the Subscription in the dashboard, process a renewal for it, ensure the payment goes through and is collected in your stripe account. (This check is to ensure the tokens are saved correctly)

### Stripe failures

Check out using the following cards to ensure following scenarios happen: (use any CVV and expiration date in the future)

-   `4242 4242 4242 4242` - Checkout succeeds
-   `4000 0025 0000 3155` - SCA prompt appears, if you choose to fail, then checkout fails, if you choose to succeed then the checkout succeeds
-   `4000 0082 6000 3178` - Choose to succeed, but the payment should still fail because of insufficient funds
-   `4000 0000 0000 9979`- Checkout fails with generic card error. (This card is marked stolen).

### Express Payment methods

Basic card has been deprecated, so we can either: use Google/Apple Pay, or [an older version of Chromium](https://commondatastorage.googleapis.com/chromium-browser-snapshots/index.html?prefix=Mac/625897/) you use any version up to 96 but this the only version I had to hand when writing these instructions.

If using the old version of Chromium, ensure your site is using HTTPS, and set up a payment method in Chromium: `Settings -> Payment Methods -> Add` use the Stripe test cards from the section above. We will need to test each scenario mentioned above, but in Express Payments.

If using GPay, then ensure your account is linked in Chrome. You may need to disable 1Password, but I'm not sure if it's necessary.

### Event emitters

Prerequisite: Install [`woocommerce-gateway-stripe`](https://github.com/woocommerce/woocommerce-gateway-stripe) from GitHub, we will need to edit code here. Set it up and get it running in dev mode.
Go to: [https://github.com/woocommerce/woocommerce-gateway-stripe/blob/8ffd22aff3b06eda02a1ae2fd8368b71450b36a9/client/blocks/credit-card/use-payment-processing.js#L66](https://github.com/woocommerce/woocommerce-gateway-stripe/blob/8ffd22aff3b06eda02a1ae2fd8368b71450b36a9/client/blocks/credit-card/use-payment-processing.js#L66)

-   Add a `console.log` to the `onSubmit` at the very top of the function.
-   In your browser, open dev tools and view the console. Ensure `preserve log` is enabled!
-   Check out (both successfully and unsuccessfully) and ensure the console log you added to `onSubmit` is called **both** times.

### Interaction with unusable payment methods

-   Install and activate [WooCommerce Bookings](woocommerce.com/products/woocommerce-bookings/). Add a bookable product, ensure to add a cost to it on the edit product page, then:
-   Add a _normal_ (i.e. Beanie, Hoodie etc.) product to the cart and ensure you can check out successfully.
-   Then add a bookable product, ensure you can check out successfully.

### Interaction with another payment method added by extension

-   Edit the bookable product and set the `Check this box if the booking requires admin approval/confirmation. Payment will not be taken during checkout.` option to true.
-   Add this product to the cart and ensure you can check out.

### Payment filtering by extensions

-   Enable the Cash on Delivery payment method.
-   Install the `@woocommerce/extend-cart-checkout-block` template by using the following command. Run this from your `wp-content/plugins` directory: `npx @wordpress/create-block -t @woocommerce/extend-cart-checkout-block payment-test-plugin`.
-   This will install a plugin called `Payment Test Plugin`. Find this and activate it.
-   By default, this example template has the following code [https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/extend-cart-checkout-block/src/js/filters.js.mustache#L17](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/extend-cart-checkout-block/src/js/filters.js.mustache#L17) which will disable COD if the billing city is Denver.
-   Go to the front-end and enter Denver in the billing city.
-   Ensure COD is removed as an option.
-   Change Denver to something else and ensure COD reappears as an option.

## Checkout

### Event emitters - code changes required to test

Again, this requires us to make changes to the Stripe plugin as this is the easiest way to test event emitters. Go to:

1. [https://github.com/woocommerce/woocommerce-gateway-stripe/blob/9a30800f2aab8e280b61a4f7ed97885f5ba81a56/client/blocks/credit-card/use-checkout-subscriptions.js#L55-L64](https://github.com/woocommerce/woocommerce-gateway-stripe/blob/9a30800f2aab8e280b61a4f7ed97885f5ba81a56/client/blocks/credit-card/use-checkout-subscriptions.js#L55-L64)
2. [https://github.com/woocommerce/woocommerce-gateway-stripe/blob/8ffd22aff3b06eda02a1ae2fd8368b71450b36a9/client/blocks/credit-card/use-payment-processing.js#L47](https://github.com/woocommerce/woocommerce-gateway-stripe/blob/8ffd22aff3b06eda02a1ae2fd8368b71450b36a9/client/blocks/credit-card/use-payment-processing.js#L47)
3. [https://github.com/woocommerce/woocommerce-gateway-stripe/blob/8ffd22aff3b06eda02a1ae2fd8368b71450b36a9/client/blocks/credit-card/use-payment-processing.js#L146](https://github.com/woocommerce/woocommerce-gateway-stripe/blob/8ffd22aff3b06eda02a1ae2fd8368b71450b36a9/client/blocks/credit-card/use-payment-processing.js#L146)

In the position linked in point 1, add `eventRegistration` as a new argument to the `usePaymentProcessing` function. It should now look like this:

```js
usePaymentProcessing(
	onStripeError,
	error,
	stripe,
	billing,
	emitResponse,
	sourceId,
	setSourceId,
	onPaymentSetup,
	eventRegistration
);
```

In the location linked on point 2, add `eventRegistration` as the last argument. The function signature should look like this:

```js
export const usePaymentProcessing = (
  onStripeError,
  error,
  stripe,
  billing,
  emitResponse,
  sourceId,
  setSourceId,
  onPaymentSetup,
  eventRegistration
) => {
...
```

In the location linked in point 3, add the following:

#### `onCheckoutValidation`

```js
const unsubscribeValidation = eventRegistration.onCheckoutValidation( ( a ) => {
	console.log( 'onCheckoutValidation', a );
} );
```

#### `onCheckoutFail`

```js
const unsubscribeOnCheckoutFail = eventRegistration.onCheckoutFail( ( a ) => {
	console.log( 'onCheckoutFail', a );
} );
```

#### `onCheckoutSuccess`

```js
const unsubscribeOnCheckoutSuccess = eventRegistration.onCheckoutSuccess(
	( a ) => {
		console.log( 'onCheckoutSuccess', a );
	}
);
```

#### `onCheckoutBeforeProcessing` (deprecated)

```js
const unsubscribeOnCheckoutBeforeProcessing =
	eventRegistration.onCheckoutBeforeProcessing( ( a ) => {
		// Expect a deprecated message here.
		console.log( 'onCheckoutBeforeProcessing', a );
	} );
```

Then, in the returned function, [https://github.com/woocommerce/woocommerce-gateway-stripe/blob/8ffd22aff3b06eda02a1ae2fd8368b71450b36a9/client/blocks/credit-card/use-payment-processing.js#L147-L149](https://github.com/woocommerce/woocommerce-gateway-stripe/blob/8ffd22aff3b06eda02a1ae2fd8368b71450b36a9/client/blocks/credit-card/use-payment-processing.js#L147-L149) below, add:

```js
unsubscribeOnCheckoutValidation();
unsubscribeOnCheckoutSuccess();
unsubscribeOnCheckoutFail();
unsubscribeOnCheckoutBeforeProcessing();
```

After these changes have been made, your file should look like this: [https://gist.github.com/opr/1f71e72ea8bee0a58d33f6f0412af51f](https://gist.github.com/opr/1f71e72ea8bee0a58d33f6f0412af51f)

### Getting to some testing

1. Ensure you can check out correctly.
2. Press the `Place Order` button and ensure all checkout controls are disabled while processing is taking place.
3. After making the code changes above, you need to use the Stripe payment method (entering new card details in this component each time!)
   <img width="647" alt="image" src="https://user-images.githubusercontent.com/5656702/175654613-4f94853a-f96c-4cb8-9f07-d759d049db8a.png">

4. In the console section of dev tools, enable the `Preserve log` option.
5. Then do the following:

-   Check out using a valid card. You should see a message telling you `onCheckoutBeforeProcessing` is deprecated then when you check out you should see, in the same order, the following logs:

    -   `onCheckoutValidation` `{}`
    -   `onCheckoutBeforeProcessing` `{}`
    -   `onCheckoutSuccess` `{redirectUrl, orderId, customerId, orderNotes, paymentResult }

-   Check out using an invalid card, you should only see:

    -   `onCheckoutValidation` `{}`
    -   `onCheckoutBeforeProcessing` `{}`

-   Reload the checkout page and then go to [https://github.com/woocommerce/woocommerce-blocks/blob/029b379138906872dec3ed920fcb23d24404a3f2/src/StoreApi/Schemas/V1/CheckoutSchema.php#L26-L25](https://github.com/woocommerce/woocommerce-blocks/blob/029b379138906872dec3ed920fcb23d24404a3f2/src/StoreApi/Schemas/V1/CheckoutSchema.php#L26-L25) and introduce a syntax error. Try to check out using a valid card, then an invalid card you should see:
    -   `onCheckoutValidation` `{}`
    -   `onCheckoutBeforeProcessing` `{}`
    -   `onCheckoutFail` `{redirectUrl, orderId, customerId, orderNotes, paymentResult }`

## WordPress.com

It would be useful to test these on WordPress.com as well - run `npm run package-plugin:deploy` from the repo root, then upload the resulting zip file to a WordPress.com site. Set up the store and repeat the testing instructions there.
