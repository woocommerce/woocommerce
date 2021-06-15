We have feature gating system setup in our plugin that defines what is accessible to the public and what is not, it has three phases:

-   **Core flag `WOOCOMMERCE_BLOCKS_PHASE=1`:** anything that is not hidden behind a flag, falls under this category, and it contains all of the code that runs on WooCommerce Core plugin.
-   **Feature plugin flag `WOOCOMMERCE_BLOCKS_PHASE=2`**: anything that is behind this flag is code that is shipped to our [feature plugin](https://wordpress.org/plugins/woo-gutenberg-products-block/), the files of blocks behind this flag are also present in WooCommerce Core, just not active.
-   **Experimental flag `WOOCOMMERCE_BLOCKS_PHASE=3`**: This flag contains things that we're not shipping yet, so unfinished work mostly. These features are only available in developer builds of the plugin.

We also use an `__experimental` prefix for any experimental interfaces. This is a signal to those reading our code that it should not be implemented in for production use. Currently this prefix is used in the following ways:

-   Prefixing references that are experimental. An example would be PHP action or filter slugs.
-   Prefixing functions or methods that are experimental.

## Blocks behind flags

The majority of our feature flagging is blocks, this is a list of them:

### Feature plugin flag

-   Cart block ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/9b76ea7a1680e68cc20bfee01078e43ccfc996bd/assets/js/blocks/cart-checkout/cart/index.js#L53) | [PHP flag 1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e167b2c99c68e8113b4e371fefdd6f9a356ed2e8/src/Library.php#L74-L77) | [PHP flag 2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/src/Assets.php#L92-L95)).
-   Checkout block ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/9b76ea7a1680e68cc20bfee01078e43ccfc996bd/assets/js/blocks/cart-checkout/checkout/index.js#L52) | [PHP flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e167b2c99c68e8113b4e371fefdd6f9a356ed2e8/src/Library.php#L74-L77) | [PHP flag 2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/src/Assets.php#L92-L95)).

### Experimental flag

-   Single Product block ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/9b76ea7a1680e68cc20bfee01078e43ccfc996bd/assets/js/blocks/single-product/index.js#L43) | [PHP flag 1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e167b2c99c68e8113b4e371fefdd6f9a356ed2e8/src/Library.php#L79-L81) | [PHP flag 2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/src/Assets.php#L88-L90) | [webpack flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/bin/webpack-entries.js#L48-L50)).
-   ⚛️ Add to cart ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/add-to-cart/index.js#L29-L32)).
-   ⚛️ Product category list ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/category-list/index.js#L29-L32)).
-   ⚛️ Product SKU ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/sku/index.js#L29-L33)).
-   ⚛️ Product stock indicator ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/stock-indicator/index.js#L29-L33)).
-   ⚛️ Product tag list ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/tag-list/index.js#L29-L33)).

## Features behind flags

We also have individual features or code blocks behind a feature flag, this is a list of them:

### Feature plugin flag

-   Draft order and cleanup process ([PHP flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/e167b2c99c68e8113b4e371fefdd6f9a356ed2e8/src/Domain/Services/DraftOrders.php#L42-L51)).
-   Payment API ([PHP flag 1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/07387889ec3a03769eb490821ee608d4e741e942/src/Domain/Bootstrap.php#L92-L94) | [PHP flag 2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/07387889ec3a03769eb490821ee608d4e741e942/src/Domain/Bootstrap.php#L245-L254)).
-   ⚛️ Product Price new controls ([JS flag 1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/price/attributes.js#L13-L44) | [JS flag 2-1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/8d2f0ad8ade2c7217769b431f93de76d6cfacf6e/assets/js/atomic/blocks/product-elements/price/block.js#L116) | [JS flag 2-2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/8d2f0ad8ade2c7217769b431f93de76d6cfacf6e/assets/js/atomic/blocks/product-elements/price/block.js#L114) | [JS flag 2-3](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/8d2f0ad8ade2c7217769b431f93de76d6cfacf6e/assets/js/atomic/blocks/product-elements/price/block.js#L91) | [JS flag 2-4](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/8d2f0ad8ade2c7217769b431f93de76d6cfacf6e/assets/js/atomic/blocks/product-elements/price/block.js#L95) | [JS flag 2-5](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/8d2f0ad8ade2c7217769b431f93de76d6cfacf6e/assets/js/atomic/blocks/product-elements/price/block.js#L106) | [JS flag 3-1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/price/edit.js#L59-L108) | [JS flag 3-2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/price/edit.js#L114-L131)).
-   ⚛️ Product Title new controls ([JS flag 1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/title/attributes.js#L21-L40) | [JS flag 2-1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/title/block.js#L70-L72) | [JS flag 2-2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/title/block.js#L94-L95) | [JS flag 2-3](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/title/block.js#L104) | [JS flag 3-1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/title/edit.js#L47-L54) | [JS flag 3-2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/title/edit.js#L77-L107) | [JS flag 3-3](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/title/edit.js#L116-L129)).
-   Utility function to pass styles to a block ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/9ebddbc5d21eb3cc67fedddbccbd86453313eb64/assets/js/atomic/utils/block-styling.js#L6-L12)).
-   Feature to create an account from the Checkout block ([PHP flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/4cedb65367be0d1c4c1f9dd9c016e3b1325cf92e/src/Domain/Services/CreateAccount.php#L40)).

### Experimental plugin flag

-   Inserting Product Element blocks globally from the inserter ([JS flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/assets/js/atomic/blocks/product-elements/shared/config.js#L25-L27)).
-   Google Analytics scripts which are loaded when the Google Analytics Integration Extension is installed ([JS flag 1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/4cedb65367be0d1c4c1f9dd9c016e3b1325cf92e/src/Assets.php#L58) | [JS flag 2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/4cedb65367be0d1c4c1f9dd9c016e3b1325cf92e/src/Assets.php#L136)).

## Processes and commands that use a flag

-   `npm run build:deploy` uses the feature plugin flag ([env flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/c0de18ec0a798c072420c67a689e4cc4d3ac77c9/package.json#L28)).
-   GitHub actions uses the experimental flag when running automated tests ([env flags 1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/4cedb65367be0d1c4c1f9dd9c016e3b1325cf92e/.github/workflows/php-js-e2e-tests.yml) | [env flags 2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/4cedb65367be0d1c4c1f9dd9c016e3b1325cf92e/.github/workflows/unit-tests.yml)).
-   webpack creates a `blocks.ini` when running ([env flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/bin/webpack-configs.js#L95-L102)).
-   webpack filters out experimental blocks when building. ([env flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b3a9753d8b7dae18b36025d09fbff835b8365de0/bin/webpack-entries.js#L61-L66)).
-   certain e2e tests are skipped if the environment is not met ([env flag](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/50e7411aee51afb3fbfa8561e297085ee44e40c0/tests/e2e-tests/specs/backend/cart.test.js#L18)).

## Usages of `__experimental` prefix

-   `__experimental_woocommerce_blocks_payment_gateway_features_list` hook that allows modification of the features supported by PayPal Standard. ([experimental hook](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/4cedb65367be0d1c4c1f9dd9c016e3b1325cf92e/src/Payments/Integrations/PayPal.php#L86)).
-   `__experimental_woocommerce_blocks_checkout_update_order_meta` hook when the draft order has been created or updated from the cart and is now ready for extensions to modify the metadata ([experimental hook](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3686/files#diff-af2c90fa556cc086b780c8fad99b68373d87fd6007e6e2ff1b4c68ebe9ccb551R377-R393)).
-   `__experimental_woocommerce_blocks_checkout_order_processed` hook when order has completed processing and is ready for payment ([experimental hook](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/accd1bbf402e043b9fc322f118ab614ba7437c92/src/StoreApi/Routes/Checkout.php#L237)).
-   `__experimentalDeRegisterPaymentMethod` function used to deregister a payment method, only used in tests ([experimental function](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b07883b8b76feeb439d655b255507b24fc59e091/assets/js/blocks-registry/payment-methods/registry.js#L70)).
-   `__experimentalDeRegisterExpressPaymentMethod` function used to deregister an express payment method, only used in tests ([experimental function](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b07883b8b76feeb439d655b255507b24fc59e091/assets/js/blocks-registry/payment-methods/registry.js#L74)).
-   `__experimentalRegisterCheckoutFilters` and `__experimentalApplyCheckoutFilter` methods included with `@woocommerce/blocks-checkout` package. They allow registering and applying a filter to certain parts of the Cart and Checkout blocks ([experimental method 1](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/3e59ec9842464f783f6e087947e717fa0b0a7b1b/packages/checkout/registry/index.js#L2) | [experimental method 2](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/3e59ec9842464f783f6e087947e717fa0b0a7b1b/packages/checkout/registry/index.js#L17)).
-   `__experimental_woocommerce_blocks_hidden` property in a Cart item data array that allows overwriting the `hidden` property. This is useful to make some cart item data visible/hidden depending if it needs to be displayed in Blocks or shortcode ([experimental property](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/9c4288b0ee46960bdc2bf8ef351d05ac23073b0c/src/StoreApi/Schemas/CartItemSchema.php#L439-L441)).

### Slots

-   `__experimentalOrderMeta` slot that allows extensions to add content to the order meta in the Cart and Checkout blocks ([experimental slot](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/4cedb65367be0d1c4c1f9dd9c016e3b1325cf92e/packages/checkout/order-meta/index.js#L12)).
-   `__experimentalOrderShippingPackages` slot that allows extensions to add content to the shipping packages in the Cart and Checkout blocks ([experimental slot](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/4cedb65367be0d1c4c1f9dd9c016e3b1325cf92e/packages/checkout/order-shipping-packages/index.js#L12)).
-   `__experimentalDiscountsMeta` slot that allows extensions to add content to the shipping packages in the Cart and Checkout blocks ([experimental slot](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/b6a9cc6342696f47cc08686522bdaca7989a6bc7/packages/checkout/discounts-meta/index.js)).

## Usages of `experimental` prefix

`useStoreEvents` makes use of an `experimental__` prefix for wp-hook actions (since `__experimental` is not a valid prefix in that context).

-   `experimental__woocommerce_blocks-` is used for store events.
-   `experimental__woocommerce_blocks-checkout-` is used for checkout events.

Current list of events:

-   `experimental__woocommerce_blocks-cart-add-item` - Fired when an item is added to the cart.
-   `experimental__woocommerce_blocks-product-view-link` - Fired when a product link is clicked.
-   `experimental__woocommerce_blocks-product-list-render` - Fired when a product list is rendered.
-   `experimental__woocommerce_blocks-store-notice-create` - Fired when a store notice is created.
-   `experimental__woocommerce_blocks-cart-set-item-quantity` - Fired when cart item quantity is changed by the customer.
-   `experimental__woocommerce_blocks-cart-remove-item` - Fired when a cart item is removed from the cart.
-   `experimental__woocommerce_blocks-product-render` - Fired when a single product block is rendered.
-   `experimental__woocommerce_blocks-checkout-submit` - Fired when the checkout form is submitted.
-   `experimental__woocommerce_blocks-checkout-set-selected-shipping-rate` - Fired when a shipping rate is chosen on checkout.
-   `experimental__woocommerce_blocks-checkout-render-checkout-form` - Fired when the checkout form is rendered.
-   `experimental__woocommerce_blocks-checkout-set-email-address` - Fired when an email address is added during checkout.
-   `experimental__woocommerce_blocks-checkout-set-shipping-address` - Fired when a shipping address is added during checkout.
-   `experimental__woocommerce_blocks-checkout-set-phone-number` - Fired when a phone number is added during checkout.
-   `experimental__woocommerce_blocks-checkout-set-billing-address` - Fired when a billing address is added during checkout.
