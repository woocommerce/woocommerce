---
post_title: Cart and Checkout Filters - Totals footer item
menu_title: Totals Footer Item
tags: reference
---

<!-- markdownlint-disable MD024 -->

The following Totals Footer Item filter are available:

-   `totalLabel`
-   `totalValue`

## `totalLabel`

The following object is used in the filter:

-   [Cart object](#cart-object)

### Description <!-- omit in toc -->

The `totalLabel` filter allows to change the label of the total item in the footer of the Cart and Checkout blocks.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `Total`) - The total label.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).

### Returns <!-- omit in toc -->

-   `string` - The updated total label.

### Code example <!-- omit in toc -->

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyTotalLabel = ( defaultValue, extensions, args ) => {
	return 'Deposit due today';
};

registerCheckoutFilters( 'example-extension', {
	totalLabel: modifyTotalLabel,
} );
```

> Filters can be also combined. See [Combined filters](./category/cart-and-checkout-blocks/available-filters/) for an example.

### Screenshots <!-- omit in toc -->

| Before                                                                 | After                                                                 |
|:---------------------------------------------------------------------:|:---------------------------------------------------------------------:|
|![Before applying the Total Label filter](https://github.com/woocommerce/woocommerce-blocks/assets/3323310/5b2fb8ab-db84-4ed0-a676-d5203edc84d2) |![After applying the Total Label filter](https://github.com/woocommerce/woocommerce-blocks/assets/3323310/07955eea-cb17-48e9-9cb5-6548dd6a3b24) |


## `totalValue`

The following object is used in the filter:

-   [Cart object](#cart-object)

### Description <!-- omit in toc -->

The `totalValue` filter allows to format the total price in the footer of the Cart and Checkout blocks.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `Total`) - The total label.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
-   _validation_ `boolean` - Checks if the return value contains the substring `<price/>`.

### Returns <!-- omit in toc -->

-   `string` - The modified format of the total price, which must contain the substring `<price/>`, or the original price format.

### Code example <!-- omit in toc -->

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyTotalsPrice = ( defaultValue, extensions, args, validation ) => {
	return 'Pay <price/> now';
};

registerCheckoutFilters( 'my-extension', {
	totalValue: modifyTotalsPrice,
} );
```

> Filters can be also combined. See [Combined filters](./category/cart-and-checkout-blocks/available-filters/) for an example.

### Screenshots <!-- omit in toc -->

| Before                                                                 | After                                                                 |
|:---------------------------------------------------------------------:|:---------------------------------------------------------------------:|
|![Before applying the Total Value filter](https://github.com/woocommerce/woocommerce/assets/3323310/4b788bdd-6fbd-406c-a9ad-4fb13f901c23) |![After applying the Total Value filter](https://github.com/woocommerce/woocommerce/assets/3323310/1b1b5f72-7f2f-4ee5-b2a4-1d8eb2208deb) |


## Cart object

The Cart object of the filters above has the following keys:

-   _billingAddress_ `object` - The billing address object with the following keys:
    -   _address_1_ `string` - The first line of the address.
    -   _address_2_ `string` - The second line of the address.
    -   _city_ `string` - The city of the address.
    -   _company_ `string` - The company of the address.
    -   _country_ `string` - The country of the address.
    -   _email_ `string` - The email of the address.
    -   _first_name_ `string` - The first name of the address.
    -   _last_name_ `string` - The last name of the address.
    -   _phone_ `string` - The phone of the address.
    -   _postcode_ `string` - The postcode of the address.
    -   _state_ `string` - The state of the address.
-   ~~_billingData_~~ `object` - The billing data object with the same keys as the `billingAddress` object.
-   _cartCoupons_ `array` - The cart coupons array.
-   _cartErrors_ `array` - The cart errors array.
-   _cartFees_ `array` - The cart fees array.
-   _cartHasCalculatedShipping_ `boolean` - Whether the cart has calculated shipping.
-   _cartIsLoading_ `boolean` - Whether the cart is loading.
-   _cartItemErrors_ `array` - The cart item errors array.
-   _cartItems_ `array` - The cart items array with cart item objects, see [Cart Item object](#cart-item-object).
-   _cartItemsCount_ `number` - The cart items count.
-   _cartItemsWeight_ `number` - The cart items weight.
-   _cartNeedsPayment_ `boolean` - Whether the cart needs payment.
-   _cartNeedsShipping_ `boolean` - Whether the cart needs shipping.
-   _cartTotals_ `object` - The cart totals object with the following keys:
    -   _currency_code_ `string` - The currency code.
    -   _currency_decimal_separator_ `string` - The currency decimal separator.
    -   _currency_minor_unit_ `number` - The currency minor unit.
    -   _currency_prefix_ `string` - The currency prefix.
    -   _currency_suffix_ `string` - The currency suffix.
    -   _currency_symbol_ `string` - The currency symbol.
    -   _currency_thousand_separator_ `string` - The currency thousand separator.
    -   _tax_lines_ `array` - The tax lines array with tax line objects with the following keys:
        -   _name_ `string` - The name of the tax line.
        -   _price_ `number` - The price of the tax line.
        -   _rate_ `string` - The rate ID of the tax line.
    -   _total_discount_ `string` - The total discount.
    -   _total_discount_tax_ `string` - The total discount tax.
    -   _total_fees_ `string` - The total fees.
    -   _total_fees_tax_ `string` - The total fees tax.
    -   _total_items_ `string` - The total items.
    -   _total_items_tax_ `string` - The total items tax.
    -   _total_price_ `string` - The total price.
    -   _total_shipping_ `string` - The total shipping.
    -   _total_shipping_tax_ `string` - The total shipping tax.
    -   _total_tax_ `string` - The total tax.
-   _crossSellsProducts_ `array` - The cross sells products array with cross sells product objects.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _isLoadingRates_ `boolean` - Whether the cart is loading rates.
-   _paymentRequirements_ `array` - The payment requirements array.
-   _shippingAddress_ `object` - The shipping address object with the same keys as the `billingAddress` object.
-   _shippingRates_ `array` - The shipping rates array.

## Cart Item object

The Cart Item object of the filters above has the following keys:

-   _backorders_allowed_ `boolean` - Whether backorders are allowed.
-   _catalog_visibility_ `string` - The catalog visibility.
-   _decsription_ `string` - The cart item description.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _id_ `number` - The item ID.
-   _images_ `array` - The item images array.
-   _item_data_ `array` - The item data array.
-   _key_ `string` - The item key.
-   _low_stock_remaining_ `number` - The low stock remaining.
-   _name_ `string` - The item name.
-   _permalink_ `string` - The item permalink.
-   _prices_ `object` - The item prices object with the following keys:
    -   _currency_code_ `string` - The currency code.
    -   _currency_decimal_separator_ `string` - The currency decimal separator.
    -   _currency_minor_unit_ `number` - The currency minor unit.
    -   _currency_prefix_ `string` - The currency prefix.
    -   _currency_suffix_ `string` - The currency suffix.
    -   _currency_symbol_ `string` - The currency symbol.
    -   _currency_thousand_separator_ `string` - The currency thousand separator.
    -   _price_ `string` - The price.
    -   _price_range_ `string` - The price range.
    -   _raw_prices_ `object` - The raw prices object with the following keys:
        -   _precision_ `number` - The precision.
        -   _price_ `number` - The price.
        -   _regular_price_ `number` - The regular price.
        -   _sale_price_ `number` - The sale price.
    -   _regular_price_ `string` - The regular price.
    -   _sale_price_ `string` - The sale price.
-   _quantity_ `number` - The item quantity.
-   _quantity_limits_ `object` - The item quantity limits object with the following keys:
    -   _editable_ `boolean` - Whether the quantity is editable.
    -   _maximum_ `number` - The maximum quantity.
    -   _minimum_ `number` - The minimum quantity.
    -   _multiple_of_ `number` - The multiple of quantity.
-   _short_description_ `string` - The item short description.
-   _show_backorder_badge_ `boolean` - Whether to show the backorder badge.
-   _sku_ `string` - The item SKU.
-   _sold_individually_ `boolean` - Whether the item is sold individually.
-   _totals_ `object` - The item totals object with the following keys:
    -   _currency_code_ `string` - The currency code.
    -   _currency_decimal_separator_ `string` - The currency decimal separator.
    -   _currency_minor_unit_ `number` - The currency minor unit.
    -   _currency_prefix_ `string` - The currency prefix.
    -   _currency_suffix_ `string` - The currency suffix.
    -   _currency_symbol_ `string` - The currency symbol.
    -   _currency_thousand_separator_ `string` - The currency thousand separator.
    -   _line_subtotal_ `string` - The line subtotal.
    -   _line_subtotal_tax_ `string` - The line subtotal tax.
    -   _line_total_ `string` - The line total.
    -   _line_total_tax_ `string` - The line total tax.
-   _type_ `string` - The item type.
-   _variation_ `array` - The item variation array.
