# WooCommerce Blocks Handbook <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Contributors](#contributors)
-   [Internal developers](#internal-developers)
-   [Third-party developers](#third-party-developers)
-   [Designers](#designers)
-   [Developer Resources](#developer-resources)
    -   [Tools](#tools)
    -   [Articles](#articles)
    -   [Tutorials](#tutorials)

The WooCommerce Blocks Handbook provides documentation for designers and developers on how to extend or contribute to blocks, and how internal developers should handle new releases.

## Contributors

> Want to contribute to the WooCommerce Blocks plugin? The following documents offer information that can help you get started.

-   [Contributing](contributors/README.md)
    -   [Getting Started](contributors/getting-started.md)
    -   [Coding Guidelines](contributors/coding-guidelines.md)
    -   [Block Script Assets](contributors/block-assets.md)
    -   [CSS Build System](contributors/css-build-system.md)
    -   [JavaScript Build System](contributors/javascript-build-system.md)
    -   [JavaScript Testing](contributors/javascript-testing.md)
    -   [Storybook & Components](contributors/storybook-and-components.md)

## Internal developers

> Are you an internal developer? The following documents offer information about the different blocks, the Block Client APIs, the Store API, the templates and the testing process.

-   [Blocks](internal-developers/blocks/README.md)
    -   [Stock Reservation during Checkout](internal-developers/blocks/stock-reservation.md)
    -   [Features Flags and Experimental interfaces](internal-developers/blocks/feature-flags-and-experimental-interfaces.md)
-   [Block Data](../assets/js/data/README.md)
    -   [Collections Store](../assets/js/data/collections/README.md)
    -   [Query State Store](../assets/js/data/query-state/README.md)
    -   [Schema Store](../assets/js/data/schema/README.md)
-   [Block Client APIs](internal-developers/block-client-apis/README.md)
    -   [Checkout API interface](internal-developers/block-client-apis/checkout/checkout-api.md)
    -   [Checkout Flow and Events](internal-developers/block-client-apis/checkout/checkout-flow-and-events.md)
    -   [Notices](internal-developers/block-client-apis/notices.md)
-   [Data store](internal-developers/data-store/README.md)
    -   [Validation](internal-developers/data-store/validation.md)
-   [Editor Components](../assets/js/editor-components/README.md)
    -   [SearchListControl](../assets/js/editor-components/search-list-control/README.md)
    -   [Tag](../assets/js/editor-components/tag/README.md)
    -   [TextToolbarButton](../assets/js/editor-components/text-toolbar-button/README.md)
-   [Icons](../assets/js/icons/README.md)
-   [Store API (REST API)](../../woocommerce/src/StoreApi/README.md)
-   [Storybook](../storybook/README.md)
-   [Templates](internal-developers/templates/README.md)
    -   [BlockTemplateController.php](internal-developers/templates/block-template-controller.md)
    -   [ClassicTemplate.php](internal-developers/templates/classic-template.md)
    -   [Classic Template Block](../assets/js/blocks/classic-template/README.md)
-   [Testing](internal-developers/testing/README.md)
    -   [When to employ end to end testing](internal-developers/testing/when-to-employ-e2e-testing.md)
    -   [Smoke Testing](internal-developers/testing/smoke-testing.md)
    -   [Cart and Checkout Testing](internal-developers/testing/cart-checkout/README.md)
        -   [General Flow](internal-developers/testing/cart-checkout/general-flow.md)
        -   [Editor](internal-developers/testing/cart-checkout/editor.md)
        -   [Shipping](internal-developers/testing/cart-checkout/shipping.md)
        -   [Payments](internal-developers/testing/cart-checkout/payment.md)
        -   [Items](internal-developers/testing/cart-checkout/items.md)
        -   [Taxes](internal-developers/testing/cart-checkout/taxes.md)
        -   [Coupons](internal-developers/testing/cart-checkout/coupons.md)
        -   [Compatibility](internal-developers/testing/cart-checkout/compatibility.md)
    -   [Releases](internal-developers/testing/releases/README.md)
-   [Translations](internal-developers/translations/README.md)
    -   [Translation basics](internal-developers/translations/translation-basics.md)
    -   [Translations in PHP files](internal-developers/translations/translations-in-PHP-files.md)
    -   [Translations in JS/TS files](internal-developers/translations/translations-in-JS-TS-files.md)
    -   [Translations in FSE templates](internal-developers/translations/translations-in-FSE-templates.md)
    -   [Translations for lazy-loaded components](internal-developers/translations/translations-for-lazy-loaded-components.md)
    -   [Translation loading](internal-developers/translations/translation-loading.md)
    -   [Translation management](internal-developers/translations/translation-management.md)

## Third-party developers

> Are you a third-party developer? The following documents explain how to extend the WooCommerce Blocks plugin with your custom extension.

-   [Extensibility](third-party-developers/extensibility/README.md)
    -   Hooks
        -   [Actions](third-party-developers/extensibility/hooks/actions.md)
        -   [Filters](third-party-developers/extensibility/hooks/filters.md)
    -   REST API
        -   [Exposing your data in the Store API](third-party-developers/extensibility/rest-api/extend-rest-api-add-data.md)
        -   [Available endpoints to extend with ExtendSchema](third-party-developers/extensibility/rest-api/available-endpoints-to-extend.md)
        -   [Adding an endpoint to ExtendSchema](internal-developers/rest-api/extend-rest-api-new-endpoint.md)
        -   [Available Formatters](third-party-developers/extensibility/rest-api/extend-rest-api-formatters.md)
        -   [Updating the cart with the Store API](third-party-developers/extensibility/rest-api/extend-rest-api-update-cart.md)
    -   Checkout Payment Methods
        -   [Checkout Flow and Events](third-party-developers/extensibility/checkout-payment-methods/checkout-flow-and-events.md)
        -   [Payment Method Integration](third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md)
        -   [Filtering Payment Methods](third-party-developers/extensibility/checkout-payment-methods/filtering-payment-methods.md)
    -   Checkout Blocks
        -   [IntegrationInterface](third-party-developers/extensibility/checkout-block/integration-interface.md)
        -   [Available Filters](third-party-developers/extensibility/checkout-block/available-filters.md)
        -   [Slots and Fills](third-party-developers/extensibility/checkout-block/slot-fills.md)
        -   [Available Slot Fills](third-party-developers/extensibility/checkout-block/available-slot-fills.md)
        -   [DOM Events](third-party-developers/extensibility/checkout-block/dom-events.md)
        -   [Blocks Registry](../packages/checkout/blocks-registry/README.md)
        -   [Components](../packages/checkout/components/README.md)
        -   [Filter Registry](../packages/checkout/filter-registry/README.md)
        -   [Slot and Fill](../packages/checkout/slot/README.md)
        -   [Utilities](../packages/checkout/utils/README.md)

## Designers

> Are you a designer? The following documents explain how to apply design-changes to the WooCommerce Blocks plugin.

-   [Theming](designers/theming/README.md)
    -   [Filter blocks](designers/theming/filter-blocks.md)
    -   [Cart and Checkout](designers/theming/cart-and-checkout.md)
    -   [Class names update in 4.6.0](designers/theming/class-names-update-460.md)
    -   [Class names update in 3.4.0](designers/theming/class-names-update-340.md)
    -   [Class names update in 3.3.0](designers/theming/class-names-update-330.md)
    -   [Class names update in 2.8.0](designers/theming/class-names-update-280.md)
    -   [Product grid blocks style update in 2.7.0](designers/theming/product-grid-270.md)

## Developer Resources

### Tools

-   [@woocommerce/extend-cart-checkout-block](https://www.npmjs.com/package/@woocommerce/extend-cart-checkout-block) This is a template to be used with @wordpress/create-block to create a WooCommerce Blocks extension starting point. It also showcases how to use some extensibility points, e.g. registering an inner block in the Checkout Block, applying filters to certain texts such as the place order button, using Slot/Fill and how to change the behaviour of the Store API.

### Articles

The following posts from [developer.woo.com](https://developer.woo.com/category/developer-resources/) provide deeper insights into the WooCommerce Blocks development.

-   [Store API is now considered stable](https://developer.woo.com/2022/03/25/store-api-is-now-considered-stable/)
-   [Available Extensibility Interfaces for The Cart and Checkout Blocks](https://developer.woo.com/2021/11/09/available-extensibility-interfaces-for-the-cart-and-checkout-blocks/)
-   [How The Checkout Block Processes An Order](https://developer.woo.com/2022/10/06/how-the-checkout-block-processes-an-order/)
-   [New @wordpress/data stores in WooCommerce Blocks](https://developer.woo.com/2022/10/17/new-wordpress-data-stores-in-woocommerce-blocks/)

### Tutorials

The following tutorials from [developer.woo.com](https://developer.woo.com/category/tutorials/) help you with extending the WooCommerce Blocks plugin.

-   [📺 Tutorial: Extending the WooCommerce Checkout Block](https://developer.woo.com/2023/08/07/extending-the-woocommerce-checkout-block-to-add-custom-shipping-options/)
-   [Hiding Shipping and Payment Options in the Cart and Checkout Blocks](https://developer.woo.com/2022/05/20/hiding-shipping-and-payment-options-in-the-cart-and-checkout-blocks/)
-   [Integrating your Payment Method with Cart and Checkout Blocks](https://developer.woo.com/2021/03/15/integrating-your-payment-method-with-cart-and-checkout-blocks/)
-   [Exposing Payment Options in the Checkout Block](https://developer.woo.com/2022/07/07/exposing-payment-options-in-the-checkout-block/)

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/README.md)

<!-- /FEEDBACK -->
