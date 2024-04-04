# Class names update in 3.3.0

In [WooCommerce Blocks 3.3.0](https://developer.woocommerce.com/2020/09/02/woocommerce-blocks-3-3-0-release-notes/), we introduced express payment methods in the Cart block. In order to make it easy to write styles for express payment methods for the Cart and Checkout blocks separately, we updated several class names:

## Replaced classes

| Removed                                                      | New class name                                                                 |
| ------------------------------------------------------------ | ------------------------------------------------------------------------------ |
| `wc-block-components-express-checkout`                       | `wc-block-components-express-payment` (generic)                                |
| `wc-block-components-express-checkout`                       | `wc-block-components-express-payment--checkout` (Checkout block)               |
| `wc-block-components-express-checkout`                       | `wc-block-components-express-payment--cart` (Cart block)                       |
| `wc-block-components-express-checkout__title-container`      | `wc-block-components-express-payment__title-container`                         |
| `wc-block-components-express-checkout__title`                | `wc-block-components-express-payment__title`                                   |
| `wc-block-components-express-checkout__content`              | `wc-block-components-express-payment__content`                                 |
| `wc-block-components-express-checkout-continue-rule`         | `wc-block-components-express-payment-continue-rule` (generic)                  |
| `wc-block-components-express-checkout-continue-rule`         | `wc-block-components-express-payment-continue-rule--checkout` (Checkout block) |
| `wc-block-components-express-checkout-continue-rule`         | `wc-block-components-express-payment-continue-rule--cart` (Cart block)         |
| `wc-block-components-express-checkout-payment-event-buttons` | `wc-block-components-express-payment__event-buttons`                           |

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/designers/theming/class-names-update-330.md)

<!-- /FEEDBACK -->

