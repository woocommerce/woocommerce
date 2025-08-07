---
sidebar_label: Cart store
---

# Cart Store (`wc/store/cart`) 

> 💡 What's the difference between the Cart Store and the Checkout Store?
>
> The **Cart Store (`wc/store/cart`)** manages and retrieves data about the shopping cart, including items, customer data, and interactions like coupons.
>
> The **Checkout Store (`wc/store/checkout`)** manages and retrieves data related to the checkout process, customer IDs, order IDs, and checkout status.


## Overview

The Cart Store provides a collection of selectors and methods to manage and retrieve cart-related data for WooCommerce Blocks. It offers functionality ranging from fetching cart details to managing customer interactions, such as applying coupons or updating shipping information.

## Usage

To utilize this store you will import the `cartStore` `StoreDescriptor` in any module referencing it. Assuming `@woocommerce/block-data` is registered as an external pointing to `wc.wcBlocksData` you can import the `StoreDescriptor` via:

```js
const { cartStore } = window.wc.wcBlocksData;
```

## Actions

### setCartData

This action is used to set the cart data in the store.

#### _Parameters_ 

-   _cartData_ `object`: The current cart data with the following keys:
    -   _coupons_ `array`: The coupon items in the cart.
    -   _shippingRates_ `array`: The cart shipping rates (see `getShippingRates` selector).
    -   _shippingAddress_ `object`: The shipping address (see `getCustomerData` selector).
    -   _billingAddress_ `object`: The billing address (see `getCustomerData` selector).
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

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( setCartData( newCartData ) );
```

### setErrorData

This action is used to set the error data in the store.

#### _Parameters_ 

-   _errorData_ `object`: The error data that needs to be set in the store.
    -   _code_ `string`: The error code.
    -   _message_ `string`: The error message.
    -   _data_ `object`: Additional error data. This is an optional object with the following keys:
        -   _status_ `number`: The error status.
        -   _params_ `string`: The error params.
        -   _message_ `string`: The error message.
        -   _cart_ `object`: The cart data. This is an optional object with the following keys:
            -   _coupons_ `array`: The coupon items in the cart.
            -   _shippingRates_ `array`: The cart shipping rates (see `getShippingRates` selector).
            -   _shippingAddress_ `object`: The shipping address (see `getCustomerData` selector).
            -   _billingAddress_ `object`: The billing address (see `getCustomerData` selector).
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

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( setErrorData( newErrorData ) );
```

### receiveCartContents

This action returns an action object used in updating the store with the provided cart. It omits the customer addresses so that only updates to cart items and totals are received.

#### _Parameters_ 

-   _cartContents_ `object`: A cart contents API response.
    -   _coupons_ `array`: The coupon items in the cart.
    -   _shippingRates_ `array`: The cart shipping rates (see `getShippingRates` selector).
    -   _shippingAddress_ `object`: The shipping address (see `getCustomerData` selector).
    -   _billingAddress_ `object`: The billing address (see `getCustomerData` selector).
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

#### _Returns_ 

-   `object`: The action object with the following keys:
    -   _type_ `string`: The action type.
    -   _cartContents_ `object`: The cart contents with the following keys:
        -   _coupons_ `array`: The coupon items in the cart.
        -   _shippingRates_ `array`: The cart shipping rates (see `getShippingRates` selector).
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

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( receiveCartContents( newCartContents ) );
```

### receiveApplyingCoupon

This action returns an action object used to track when a coupon is applying.

#### _Parameters_ 

-   _couponCode_ `string`: The code of the coupon being applied.

#### _Returns_ 

-   `object`: The action object with following keys:
    -   _type_ `string`: The action type.
    -   _couponCode_ `string`: The code of the coupon being applied.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( receiveApplyingCoupon( couponCode ) );
```

### receiveRemovingCoupon

This action returns an action object used to track when a coupon is removing.

#### _Parameters_ 

-   _couponCode_ `string`: The code of the coupon being removed.

#### _Returns_ 

-   `object`: The action object with the following keys:
    -   _type_ `string`: The action type.
    -   _couponCode_ `string`: The code of the coupon being removed.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( receiveRemovingCoupon( couponCode ) );
```

### receiveCartItem

This action is used to update a specific item in the cart.

#### _Parameters_ 

-   _cartResponseItem_ `object`: Cart response object with the following keys:
    -   _cartItem_ `object`: The cart item (see `getCartItem` selector).

#### _Returns_ 

-   `object`: The action object with the following keys:
    -   _type_ `string`: The action type.
    -   _cartItem_ `object`: The cart item (see `getCartItem` selector).

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( receiveCartItem( CartResponseItem ) );
```

### itemIsPendingQuantity

This action returns an action object to indicate if the specified cart item quantity is being updated.

#### _Parameters_ 

-   _cartItemKey_ `string`: The key of the cart item.
-   _isPending_ `boolean` (default: `true`): Whether the cart item quantity is being updated.

#### _Returns_ 

-   `object`: The action object with following keys:
    -   _type_ `string`: The action type.
    -   _cartItemKey_ `string`: The key of the cart item.
    -   _isPending_ `boolean`: Whether the cart item quantity is being updated.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( itemIsPendingQuantity( cartItemKey, isPending ) );
```

### itemIsPendingDelete

This action returns an action object to indicate if the specified cart item is being deleted.

#### _Parameters_ 

-   _cartItemKey_ `string`: The key of the cart item.
-   _isPending_ `boolean` (default: `true`): Whether the cart item is being deleted.

#### _Returns_ 

-   `object`: The action object with the following keys:
    -   _type_ `string`: The action type.
    -   _cartItemKey_ `string`: The key of the cart item.
    -   _isPending_ `boolean`: Whether the cart item is being deleted.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( itemIsPendingDelete( cartItemKey, isPending ) );
```

### setIsCartDataStale

This action returns an action object to indicate if the cart data is stale.

#### _Parameters_ 

-   _isCartDataStale_ `boolean` (default: `true`): Flag to mark cart data as stale; true if `lastCartUpdate` timestamp is newer than the one in wcSettings.

#### _Returns_ 

-   `object`: The action object with the following keys:
    -   _type_ `string`: The action type.
    -   _isCartDataStale_ `boolean`: Flag to mark cart data as stale; true if `lastCartUpdate` timestamp is newer than the one in wcSettings.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( setIsCartDataStale( isCartDataStale ) );
```

### updatingCustomerData

This action returns an action object to indicate if the customer data (billing and/or shipping address) is being updated.

#### _Parameters_ 

-   _isResolving_ `boolean`: Whether the customer data is being updated.

#### _Returns_ 

-   `object`: The action object with the following keys:
    -   _type_ `string`: The action type.
    -   _isResolving_ `boolean`: Whether the customer data is being updated.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( updatingCustomerData( isResolving ) );
```

### shippingRatesBeingSelected

This action returns an action object to indicate if the shipping rates are being selected.

#### _Parameters_ 

-   _isResolving_ `boolean`: True if shipping rate is being selected.

#### _Returns_ 

-   `object`: The action object with the following keys:
    -   _type_ `string`: The action type.
    -   _isResolving_ `boolean`: True if shipping rate is being selected.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( shippingRatesBeingSelected( isResolving ) );
```

### applyExtensionCartUpdate

This action is used to send POSTs request to the /cart/extensions endpoint with the data supplied by the extension.

#### _Parameters_ 

-   _args_ `object`: The arguments for the request with the following keys:
    -   _extensionId_ `string`: The extension ID.
    -   _data_ `object`: The data to send to the endpoint with the following keys:
        -   _key_ `string`: The key of the extension.
        -   _value_ `string`: The value of the extension.
    -   _overwriteDirtyCustomerData_ `boolean`: Whether to overwrite the customer data in the client with the data returned from the server, even if it is dirty (i.e. it hasn't been pushed to the server yet).

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( applyExtensionCartUpdate( args ) );
```

### applyCoupon

This action is used to apply a coupon to the cart.

#### _Parameters_ 

-   _couponCode_ `string`: The code of the coupon to apply.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( applyCoupon( couponCode ) );
```

### removeCoupon

This action is used to remove a coupon from the cart.

#### _Parameters_ 

-   _couponCode_ `string`: The code of the coupon to remove.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( removeCoupon( couponCode ) );
```

### addItemToCart

This action is used to add an item to the cart.

#### _Parameters_ 

-   _productId_ `number`: Product ID to add to cart.
-   _quantity_ `number` (default: `1`): The quantity of the product to add.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( addItemToCart( productId, quantity ) );
```

### removeItemFromCart

This action is used to remove an item from the cart.

#### _Parameters_ 

-   _cartItemKey_ `string`: Cart item being updated.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( removeItemFromCart( cartItemKey ) );
```

### changeCartItemQuantity

This action is used to change the quantity of an item in the cart.

#### _Parameters_ 

-   _cartItemKey_ `string`: Cart item being updated.
-   _quantity_ `number`: Quantity of the item.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( changeCartItemQuantity( cartItemKey, quantity ) );
```

### selectShippingRate

This action is used to select a shipping rate for the cart.

#### _Parameters_ 

-   _rateId_ `string`: The ID of the shipping rate to select.
-   _packageId_ `number | string` (default: `null`): The key of the packages that will select within the shipping rate.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( selectShippingRate( rateId, packageId ) );
```

### setBillingAddress

This action is used to set the billing address for the cart locally, as opposed to updateCustomerData which sends it to the server.

#### _Parameters_ 

-   _billingAddress_ `object`: Billing address that needs to be set. The keys are as following:
    -   _first_name_ `string`: The first name.
    -   _last_name_ `string`: The last name.
    -   _company_ `string`: The company name.
    -   _address_1_ `string`: The address line 1.
    -   _address_2_ `string`: The address line 2.
    -   _city_ `string`: The city name.
    -   _state_ `string`: The state name.
    -   _postcode_ `string`: The postcode.
    -   _country_ `string`: The country name.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( setBillingAddress( billingAddress ) );
```

### setShippingAddress

This action is used to set the shipping address for the cart locally, as opposed to updateCustomerData which sends it to the server.

#### _Parameters_ 

-   _shippingAddress_ `object`: Shipping address that needs to be set. The keys are as following:
    -   _first_name_ `string`: The first name.
    -   _last_name_ `string`: The last name.
    -   _company_ `string`: The company name.
    -   _address_1_ `string`: The address line 1.
    -   _address_2_ `string`: The address line 2.
    -   _city_ `string`: The city name.
    -   _state_ `string`: The state name.
    -   _postcode_ `string`: The postcode.
    -   _country_ `string`: The country name.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( setShippingAddress( shippingAddress ) );
```

### updateCustomerData

This action is used to updates the shipping and/or billing address for the customer and returns an updated cart.

#### _Parameters_ 

-   _customerData_ `object`: Customer billing and shipping address. The keys are as following:
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
-   `editing: boolean` (default: `true`): If the address is being edited, we don't update the customer data in the store from the response.

#### _Example_ 

```js
const { dispatch } = useDispatch( cartStore );
dispatch( updateCustomerData( customerData, editing ) );
```

## Selectors

### getCartData

Returns the Cart data from the state.

#### _Returns_ 

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

#### _Example_ 

```js
const store = select( cartStore );
const cartData = store.getCartData();
```

### getCustomerData

Returns the shipping and billing address from the state.

#### _Returns_ 

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

#### _Example_ 

```js
const store = select( cartStore );
const customerData = store.getCustomerData();
```

### getShippingRates

Returns the shipping rates from the state.

#### _Returns_ 

-   `array`: The shipping rates. They keys are as following:
    -   _id_ `string`: The shipping rate ID.
    -   _label_ `string`: The shipping rate label.
    -   _cost_ `string`: The shipping rate cost.
    -   _package_id_ `number`: The shipping rate package ID.
    -   _meta_data_ `array`: The shipping rate meta data. The keys are as following:
        -   _id_ `number`: The shipping rate meta data ID.
        -   _key_ `string`: The shipping rate meta data key.
        -   _value_ `string`: The shipping rate meta data value.
    -   _taxes_ `array`: The shipping rate taxes.

#### _Example_ 

```js
const store = select( cartStore );
const shippingRates = store.getShippingRates();
```

### getNeedsShipping

Queries whether the cart needs shipping.

#### _Returns_ 

-   `boolean`: True if the cart needs shipping.

#### _Example_ 

```js
const store = select( cartStore );
const needsShipping = store.getNeedsShipping();
```

### getHasCalculatedShipping

Queries whether the cart shipping has been calculated.

#### _Returns_ 

-   `boolean`: True if the shipping has been calculated.

#### _Example_ 

```js
const store = select( cartStore );
const hasCalculatedShipping = store.getHasCalculatedShipping();
```

### getCartTotals

Returns the cart totals from state.

#### _Returns_ 

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

#### _Example_ 

```js
const store = select( cartStore );
const cartTotals = store.getCartTotals();
```

### getCartMeta

Returns the cart meta from state.

#### _Returns_ 

-   `object`: The current cart meta with the following keys:
    -   _updatingCustomerData_ `boolean`: If the customer data (billing and/or shipping address) is being updated.
    -   _updatingSelectedRate_ `boolean`: If the selected rate is being updated.
    -   _isCartDataStale_ `boolean`: If the cart data is stale.
    -   _applyingCoupon_ `string`: The coupon code being applied.
    -   _removingCoupon_ `string`: The coupon code being removed.

#### _Example_ 

```js
const store = select( cartStore );
const cartMeta = store.getCartMeta();
```

### getCartErrors

Returns the cart errors from state if cart receives customer facing errors from the API.

#### _Returns_ 

-   `array`: The cart errors with the following keys:
    -   _code_ `string`: The error code.
    -   _message_ `string`: The error message.
    -   _data_ `object`: API response data.

#### _Example_ 

```js
const store = select( cartStore );
const cartErrors = store.getCartErrors();
```

### isApplyingCoupon

Queries whether a coupon is being applied.

#### _Returns_ 

-   `boolean`: True if a coupon is being applied.

#### _Example_ 

```js
const store = select( cartStore );
const isApplyingCoupon = store.isApplyingCoupon();
```

### isCartDataStale

Queries whether the cart data is stale.

#### _Returns_ 

-   `boolean`: True if the cart data is stale.

#### _Example_ 

```js
const store = select( cartStore );
const isCartDataStale = store.isCartDataStale();
```

### getCouponBeingApplied

Returns the coupon code being applied.

#### _Returns_ 

-   `string`: The coupon code being applied.

#### _Example_ 

```js
const store = select( cartStore );
const couponBeingApplied = store.getCouponBeingApplied();
```

### isRemovingCoupon

Queries whether a coupon is being removed.

#### _Returns_ 

-   `boolean`: True if a coupon is being removed.

#### _Example_ 

```js
const store = select( cartStore );
const isRemovingCoupon = store.isRemovingCoupon();
```

### getCouponBeingRemoved

Returns the coupon code being removed.

#### _Returns_ 

-   `string`: The coupon code being removed.

#### _Example_ 

```js
const store = select( cartStore );
const couponBeingRemoved = store.getCouponBeingRemoved();
```

### getCartItem( cartItemKey )

Returns a cart item from the state.

#### _Parameters_ 

-   _cartItemKey_ `string`: The cart item key.

#### _Returns_ 

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

#### _Example_ 

```js
const store = select( cartStore );
const cartItem = store.getCartItem( cartItemKey );
```

### isItemPendingQuantity( cartItemKey )

Queries whether a cart item is pending quantity.

#### _Parameters_ 

-   _cartItemKey_ `string`: The cart item key.

#### _Returns_ 

-   `boolean`: True if the cart item is pending quantity.

#### _Example_ 

```js
const store = select( cartStore );
const isItemPendingQuantity = store.isItemPendingQuantity( cartItemKey );
```

### isItemPendingDelete( cartItemKey )

Queries whether a cart item is pending delete.

#### _Parameters_ 

-   _cartItemKey_ `string`: The cart item key.

#### _Returns_ 

-   `boolean`: True if the cart item is pending delete.

#### _Example_ 

```js
const store = select( cartStore );
const isItemPendingDelete = store.isItemPendingDelete( cartItemKey );
```

### isCustomerDataUpdating

Queries whether the customer data is being updated.

#### _Returns_ 

-   `boolean`: True if the customer data is being updated.

#### _Example_ 

```js
const store = select( cartStore );
const isCustomerDataUpdating = store.isCustomerDataUpdating();
```

### isAddressFieldsForShippingRatesUpdating

Queries whether shipping address fields impacting the shipping rates are being updated.
By default, Store API considers the following shipping fields as essential for shipping rate calculations: `state`, `country`, `postcode`, and `city`.

#### _Returns_ 

-   `boolean`: True if shipping address fields impacting the shipping rates are being updated.

#### _Example_ 

```js
const store = select( cartStore );
const isAddressFieldsForShippingRatesUpdating = store.isAddressFieldsForShippingRatesUpdating();
```

### hasPendingItemsOperations

Queries whether there are any pending cart operations (add, quantity update, or delete items).

#### _Returns_ 

-   `boolean`: True if there are pending cart operations (adding products, updating quantities, or deleting items).

#### _Example_ 

```js
const store = select( cartStore );
const hasPendingItemsOperations = store.hasPendingItemsOperations();
```

### isShippingRateBeingSelected

Queries whether a shipping rate is being selected.

#### _Returns_ 

-   `boolean`: True if a shipping rate is being selected.

#### _Example_ 

```js
const store = select( cartStore );
const isShippingRateBeingSelected = store.isShippingRateBeingSelected();
```

### getItemsPendingQuantityUpdate

Retrieves the item keys for items whose quantity is currently being updated.

#### _Returns_ 

-   `string[]`: An array with the item keys for items whose quantity is currently being updated.

#### _Example_ 

```js
const store = select( cartStore );
const itemsPendingQuantityUpdate = store.getItemsPendingQuantityUpdate();
```

### getItemsPendingDelete

Retrieves the item keys for items that are currently being deleted.

#### _Returns_ 

-   `string[]`: An array with the item keys for items that are currently being deleted.

#### _Example_ 

```js
const store = select( cartStore );
const itemsPendingDelete = store.getItemsPendingDelete();
```
