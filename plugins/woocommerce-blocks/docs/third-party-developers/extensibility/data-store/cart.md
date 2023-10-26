# Cart Store (`wc/store/cart`) <!-- omit in toc -->

> 💡 What's the difference between the Cart Store and the Checkout Store?
>
> The **Cart Store (`wc/store/cart`)** manages and retrieves data about the shopping cart, including items, customer data, and interactions like coupons.
>
> The **Checkout Store (`wc/store/checkout`)** manages and retrieves data related to the checkout process, customer IDs, order IDs, and checkout status.

## Table of Contents <!-- omit in toc -->

-   [Overview](#overview)
-   [Usage](#usage)
-   [Selectors](#selectors)
    -   [getCartData](#getcartdata)
    -   [getCustomerData](#getcustomerdata)
    -   [getShippingRates](#getshippingrates)
    -   [getNeedsShipping](#getneedsshipping)
    -   [getHasCalculatedShipping](#gethascalculatedshipping)
    -   [getCartTotals](#getcarttotals)
    -   [getCartMeta](#getcartmeta)
    -   [getCartErrors](#getcarterrors)
    -   [isApplyingCoupon](#isapplyingcoupon)
    -   [isCartDataStale](#iscartdatastale)
    -   [getCouponBeingApplied](#getcouponbeingapplied)
    -   [isRemovingCoupon](#isremovingcoupon)
    -   [getCouponBeingRemoved](#getcouponbeingremoved)
    -   [getCartItem( cartItemKey )](#getcartitem-cartitemkey-)
    -   [isItemPendingQuantity( cartItemKey )](#isitempendingquantity-cartitemkey-)
    -   [isItemPendingDelete( cartItemKey )](#isitempendingdelete-cartitemkey-)
    -   [isCustomerDataUpdating](#iscustomerdataupdating)
    -   [isShippingRateBeingSelected](#isshippingratebeingselected)
    -   [getItemsPendingQuantityUpdate](#getitemspendingquantityupdate)
    -   [getItemsPendingDelete](#getitemspendingdelete)

## Overview

The Cart Store provides a collection of selectors and methods to manage and retrieve cart-related data for WooCommerce Blocks. It offers functionality ranging from fetching cart details to managing customer interactions, such as applying coupons or updating shipping information.

## Usage

To utilize this store you will import the `CART_STORE_KEY` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the key via:

```js
import { CART_STORE_KEY } from '@woocommerce/block-data';
```

## Selectors

### getCartData

Returns the Cart data from the state.

#### _Returns_ <!-- omit in toc -->

-   `object`: The current cart data with the following keys:
    -   _coupons_ `array`: The coupon items in the cart.
    -   _shippingRates_ `array`: The cart shipping rates (see `getShippingRates` selector).
    -   _shippingAddress_ `object`: The shipping address (see `getCustomerData` selector).
    -   _billingAddress_ `object`: The billing address.
    -   _items_ `array`: The cart items.
    -   _itemsCount_ `number`: The total number of items in the cart
    -   _itemsWeight_ `number`: The total weight of items in the cart.
    -   _crossSells_ `array`: The cross sells items.
    -   _needsPayment_ `boolean`: If the cart needs payment.
    -   _needsShipping_ `boolean`: If the cart needs shipping.
    -   _hasCalculatedShipping_ `boolean`: If the cart has calculated shipping.
    -   _fees_ `array`: The cart fees.
    -   _totals_ `object`: The cart totals (see `getCartTotals` selector).
    -   _errors_ `array`: The cart errors (see `getCartErrors` selector).
    -   _paymentRequirements_ `object`: The payment requirements for the cart.
    -   _extensions_ `object`: The extensions data.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const cartData = store.getCartData();
```

### getCustomerData

Returns the shipping and billing address from the state.

#### _Returns_ <!-- omit in toc -->

-   `object`: The current shipping and billing address with the following keys:
    -   _shippingAddress_ `object`: The shipping address with the following keys:
        -   _first_name_ `string`: The first name.
        -   _last_name_ `string`: The last name.
        -   _company_ `string`: The company name.
        -   _address_1_ `string`: The address line 1.
        -   _address_2_ `string`: The address line 2.
        -   _city_ `string`: The city name.
        -   _state_ `string`: The state name.
        -   _postcode_ `string`: The postcode.
        -   _country_ `string`: The country name.
    -   _billingAddress_ `object`: The billing address (same keys as shipping address).

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const customerData = store.getCustomerData();
```

### getShippingRates

Returns the shipping rates from the state.

#### _Returns_ <!-- omit in toc -->

-   `array`: The shipping rates.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const shippingRates = store.getShippingRates();
```

### getNeedsShipping

Queries whether the cart needs shipping.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the cart needs shipping.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const needsShipping = store.getNeedsShipping();
```

### getHasCalculatedShipping

Queries whether the cart shipping has been calculated.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the shipping has been calculated.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const hasCalculatedShipping = store.getHasCalculatedShipping();
```

### getCartTotals

Returns the cart totals from state.

#### _Returns_ <!-- omit in toc -->

-   `object`: The current cart totals with the following keys:
    -   _total_items_ `string`: The sum total of items in the cart without discount, tax or shipping.
    -   _total_items_tax_ `string`: The total tax on all items before discount.
    -   _total_fees_ `string`: The total transaction fees.
    -   _total_fees_tax_ `string`: The tax on the total transaction fees.
    -   _total_discount_ `string`: The total discount applied to the cart.
    -   _total_discount_tax_ `string`: The tax applied to the total discount amount.
    -   _total_shipping_ `string`: The total shipping cost.
    -   _total_shipping_tax_ `string`: The tax applied to the total shipping cost.
    -   _total_tax_ `string`: The total tax applied to the cart.
    -   _total_price_ `string`: The total price of the cart including discount, tax or shipping.
    -   _tax_lines_ `array` of object: The tax lines: `name`, `price`, and `rate`.
    -   _currency_code_ `string`: The currency code for the cart.
    -   _currency_symbol_ `string`: The currency symbol for the cart.
    -   _currency_minor_unit_ `integer`: The currency minor unit for the cart.
    -   _currency_decimal_separator_ `string`: The currency decimal separator for the cart.
    -   _currency_thousand_separator_ `string`: The currency thousand separator for the cart.
    -   _currency_prefix_ `string`: The currency prefix for the cart.
    -   _currency_suffix_ `string`: The currency suffix for the cart.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const cartTotals = store.getCartTotals();
```

### getCartMeta

Returns the cart meta from state.

#### _Returns_ <!-- omit in toc -->

-   `object`: The current cart meta with the following keys:
    -   _updatingCustomerData_ `boolean`: If the customer data is being updated.
    -   _updatingSelectedRate_ `boolean`: If the selected rate is being updated.
    -   _isCartDataStale_ `boolean`: If the cart data is stale.
    -   _applyingCoupon_ `string`: The coupon code being applied.
    -   _removingCoupon_ `string`: The coupon code being removed.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const cartMeta = store.getCartMeta();
```

### getCartErrors

Returns the cart errors from state if cart receives customer facing errors from the API.

#### _Returns_ <!-- omit in toc -->

-   `array`: The cart errors.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const cartErrors = store.getCartErrors();
```

### isApplyingCoupon

Queries whether a coupon is being applied.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if a coupon is being applied.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const isApplyingCoupon = store.isApplyingCoupon();
```

### isCartDataStale

Queries whether the cart data is stale.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the cart data is stale.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const isCartDataStale = store.isCartDataStale();
```

### getCouponBeingApplied

Returns the coupon code being applied.

#### _Returns_ <!-- omit in toc -->

-   `string`: The coupon code being applied.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const couponBeingApplied = store.getCouponBeingApplied();
```

### isRemovingCoupon

Queries whether a coupon is being removed.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if a coupon is being removed.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const isRemovingCoupon = store.isRemovingCoupon();
```

### getCouponBeingRemoved

Returns the coupon code being removed.

#### _Returns_ <!-- omit in toc -->

-   `string`: The coupon code being removed.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const couponBeingRemoved = store.getCouponBeingRemoved();
```

### getCartItem( cartItemKey )

Returns a cart item from the state.

#### _Parameters_ <!-- omit in toc -->

-   _cartItemKey_ `string`: The cart item key.

#### _Returns_ <!-- omit in toc -->

-   `object`: The cart item with the following keys:
    -   _key_ `string`: The cart item key.
    -   _id_ `number`: The cart item id.
    -   _catalog_visibility_ `string`: The catalog visibility.
    -   _quantity_limits_ `object`: The quantity limits.
    -   _name_ `string`: The cart item name.
    -   _summary_ `string`: The cart item summary.
    -   _short_description_ `string`: The cart item short description.
    -   _description_ `string`: The cart item description.
    -   _sku_ `string`: The cart item sku.
    -   _low_stock_remaining_ `null` or `number`: The low stock remaining.
    -   _backorders_allowed_ `boolean` indicating if backorders are allowed.
    -   _show_backorder_badge_ `boolean` indicating if the backorder badge should be shown.
    -   _sold_individually_ `boolean` indicating if the item is sold individually.
    -   _permalink_ `string`: The cart item permalink.
    -   _images_ `array`: The cart item images.
    -   _variation_ `array`: The cart item variation.
    -   _prices_ `object`: The cart item prices with the following keys:
        -   _currency_code_ `string`: The currency code.
        -   _currency_symbol_ `string`: The currency symbol.
        -   _currency_minor_unit_ `number`: The currency minor unit.
        -   _currency_decimal_separator_ `string`: The currency decimal separator.
        -   _currency_thousand_separator_ `string`: The currency thousand separator.
        -   _currency_prefix_ `string`: The currency prefix.
        -   _currency_suffix_ `string`: The currency suffix.
        -   _price_ `string`: The cart item price.
        -   _regular_price_ `string`: The cart item regular price.
        -   _sale_price_ `string`: The cart item sale price.
        -   _price_range_ `string`: The cart item price range.
    -   _totals_ `object`: The cart item totals with the following keys:
        -   _currency_code_ `string`: The currency code.
        -   _currency_symbol_ `string`: The currency symbol.
        -   _currency_minor_unit_ `number`: The currency minor unit.
        -   _currency_decimal_separator_ `string`: The currency decimal separator.
        -   _currency_thousand_separator_ `string`: The currency thousand separator.
        -   _currency_prefix_ `string`: The currency prefix.
        -   _currency_suffix_ `string`: The currency suffix.
        -   _line_subtotal_ `string`: The cart item line subtotal.
        -   _line_subtotal_tax_ `string`: The cart item line subtotal tax.
        -   _line_total_ `string`: The cart item line total.
        -   _line_total_tax_ `string`: The cart item line total tax.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const cartItem = store.getCartItem( cartItemKey );
```

### isItemPendingQuantity( cartItemKey )

Queries whether a cart item is pending quantity.

#### _Parameters_ <!-- omit in toc -->

-   _cartItemKey_ `string`: The cart item key.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the cart item is pending quantity.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const isItemPendingQuantity = store.isItemPendingQuantity( cartItemKey );
```

### isItemPendingDelete( cartItemKey )

Queries whether a cart item is pending delete.

#### _Parameters_ <!-- omit in toc -->

-   _cartItemKey_ `string`: The cart item key.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the cart item is pending delete.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const isItemPendingDelete = store.isItemPendingDelete( cartItemKey );
```

### isCustomerDataUpdating

Queries whether the customer data is being updated.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if the customer data is being updated.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const isCustomerDataUpdating = store.isCustomerDataUpdating();
```

### isShippingRateBeingSelected

Queries whether a shipping rate is being selected.

#### _Returns_ <!-- omit in toc -->

-   `boolean`: True if a shipping rate is being selected.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const isShippingRateBeingSelected = store.isShippingRateBeingSelected();
```

### getItemsPendingQuantityUpdate

Retrieves the item keys for items whose quantity is currently being updated.

#### _Returns_ <!-- omit in toc -->

-   `string[]`: An array with the item keys for items whose quantity is currently being updated.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const itemsPendingQuantityUpdate = store.getItemsPendingQuantityUpdate();
```

### getItemsPendingDelete

Retrieves the item keys for items that are currently being deleted.

#### _Returns_ <!-- omit in toc -->

-   `string[]`: An array with the item keys for items that are currently being deleted.

#### _Example_ <!-- omit in toc -->

```js
const store = select( 'wc/store/cart' );
const itemsPendingDelete = store.getItemsPendingDelete();
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/data-store/cart.md)

<!-- /FEEDBACK -->
