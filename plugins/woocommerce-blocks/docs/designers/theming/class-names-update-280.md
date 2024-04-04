# Class names update in 2.8.0 <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Replaced classes](#replaced-classes)
-   [Deprecated classes](#deprecated-classes)

In [WooCommerce Blocks 2.8.0](https://developer.woocommerce.com/2020/06/24/woocommerce-blocks-2-8-release-notes/), we replaced and deprecated some some class names to simplify them, fix inconsistencies, and make it easier to differentiate frontend components from editor components.

## Replaced classes

Some classes that were introduced in 2.6.0 and 2.7.0 and didn't ship in WooCommerce Core have been replaced by new ones. They can be found in this table:

| Removed                                | New class name                                        |
| -------------------------------------- | ----------------------------------------------------- |
| `wc-block-address-form`                | `wc-block-components-address-form`                    |
| `wc-block-checkout-form`               | `wc-block-components-checkout-form`                   |
| `wc-block-checkout-step`               | `wc-block-components-checkout-step`                   |
| `wc-block-cart__payment-method-icons`  | `wc-block-components-payment-method-icons`            |
| `wc-blocks-payment-method-icon`        | `wc-block-components-payment-method-icon`             |
| `wc-block-cart__payment-method-label`  | `wc-block-components-payment-method-label`            |
| `wc-block-low-stock-badge`             | `wc-block-components-product-low-stock-badge`         |
| `wc-block-product-metadata`            | `wc-block-components-product-metadata`                |
| `wc-block-product-name`                | `wc-block-components-product-name`                    |
| `wc-block-product-price`               | `wc-block-components-product-price`                   |
| `wc-block-product-price--regular`      | `wc-block-components-product-price__regular`          |
| `wc-block-sale-badge`                  | `wc-block-components-sale-badge`                      |
| `wc-block-product-variation-data`      | `wc-block-components-product-variation-data`          |
| `wc-block-cart__shipping-calculator`   | `wc-block-components-shipping-calculator`             |
| `wc-block-shipping-calculator`         | `wc-block-components-shipping-calculator`             |
| `wc-block-cart__shipping-address`      | `wc-block-components-shipping-address`                |
| `wc-block-shipping-rates-control`      | `wc-block-components-shipping-rates-control`          |
| `wc-block-coupon-code`                 | `wc-block-components-totals-coupon`                   |
| `wc-block-cart-coupon-list`            | `wc-block-components-totals-discount__coupon-list`    |
| `wc-block-totals-footer-item`          | `wc-block-components-totals-footer-item`              |
| `wc-block-totals-table-item`           | `wc-block-components-totals-item`                     |
| `wc-block-shipping-totals`             | `wc-block-components-totals-shipping`                 |
| `wc-block-checkbox`                    | `wc-block-components-checkbox`                        |
| `wc-block-country-input`               | `wc-block-components-country-input`                   |
| `wc-block-loading-mask`                | `wc-block-components-loading-mask`                    |
| `wc-blocks-loading-mask__children`     | `wc-block-components-loading-mask__children`          |
| `wc-block-quantity-selector`           | `wc-block-components-quantity-selector`               |
| `wc-block-radio-control`               | `wc-block-components-radio-control`                   |
| `wc-block-select`                      | `wc-block-components-select`                          |
| `wc-block-main`                        | `wc-block-components-main`                            |
| `wc-block-sidebar-layout`              | `wc-block-components-sidebar-layout`                  |
| `wc-block-sidebar`                     | `wc-block-components-sidebar`                         |
| `wc-block-notices__snackbar`           | `wc-block-components-notices__snackbar`               |
| `wc-block-text-input`                  | `wc-block-components-text-input`                      |
| `wc-block-component__title`            | `wc-block-components-title`                           |
| `wc-block-form-input-validation-error` | `wc-block-components-validation-error`                |
| `wc-block-checkout__save-card-info`    | `wc-block-components-payment-methods__save-card-info` |

**Note:** If not listed, all items in the table above include derived classes.

For example, given that `wc-block-address-form` changed to `wc-block-components-address-form`:

-   `wc-block-address-form__company` also changed to `wc-block-components-address-form__company`
-   `wc-block-address-form__address_1` also changed to `wc-block-components-address-form__address_1`

In most cases, it should be safe to do a search & replace in the stylesheet replacing the removed class names with the new ones.

## Deprecated classes

Some classes that were introduced in previous versions or that have shipped in WooCommerce Core, have not been removed but are deprecated. Those classes will not be removed until the next major version but all themes are encouraged to update to the new ones as soon as possible:

| Deprecated                        | New class name                               |
| --------------------------------- | -------------------------------------------- |
| `wc-block-error`                  | `wc-block-components-error`                  |
| `wc-block-product-sort-select`    | `wc-block-components-product-sort-select`    |
| `wc-block-formatted-money-amount` | `wc-block-components-formatted-money-amount` |
| `wc-block-checkbox-list`          | `wc-block-components-checkbox-list`          |
| `wc-block-dropdown-selector`      | `wc-block-components-dropdown-selector`      |
| `wc-block-filter-submit-button`   | `wc-block-components-filter-submit-button`   |
| `wc-block-load-more`              | `wc-block-components-load-more`              |
| `wc-block-pagination`             | `wc-block-components-pagination`             |
| `wc-block-pagination-page`        | `wc-block-components-pagination__page`       |
| `wc-block-pagination-ellipsis`    | `wc-block-components-pagination__ellipsis`   |
| `wc-block-review-list`            | `wc-block-components-review-list`            |
| `wc-block-review-sort-select`     | `wc-block-components-review-sort-select`     |
| `wc-block-price-filter`           | `wc-block-components-price-slider`           |
| `wc-block-form-text-input`        | `wc-block-components-price-slider__amount`   |

**Note:** if not listed, all items in the table above include derived classes.

For example, given that `wc-block-error` changed to `wc-block-components-error`

-   `wc-block-error__company` also changed to `wc-block-components-error__content`
-   `wc-block-error__address_1` also changed to `wc-block-components-error__image`

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/designers/theming/class-names-update-280.md)

<!-- /FEEDBACK -->

