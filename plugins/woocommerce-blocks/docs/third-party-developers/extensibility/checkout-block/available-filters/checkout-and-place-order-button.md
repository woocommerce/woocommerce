# Checkout and place order button

The following Checkout and place order button filters are available:

-   [`proceedToCheckoutButtonLabel`](#proceedtocheckoutbuttonlabel)
-   [`proceedToCheckoutButtonLink`](#proceedtocheckoutbuttonlink)
-   [`placeOrderButtonLabel`](#placeorderbuttonlabel)

The following objects are shared between the filters:

-   [Cart object](#cart-object)
-   [Cart Item object](#cart-item-object)

## `proceedToCheckoutButtonLabel`

### Description <!-- omit in toc -->

The `proceedToCheckoutButtonLabel` filter allows change the label of the "Proceed to checkout" button.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `Proceed to Checkout`) - The label of the "Proceed to checkout" button.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).

### Returns <!-- omit in toc -->

-   `string` - The label of the "Proceed to checkout" button.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyProceedToCheckoutButtonLabel = (
	defaultValue,
	extensions,
	args
) => {
	if ( ! args?.cart.items ) {
		return defaultValue;
	}

	return 'Go to checkout';
};

registerCheckoutFilters( 'example-extension', {
	proceedToCheckoutButtonLabel: modifyProceedToCheckoutButtonLabel,
} );
```

#### Advanced example <!-- omit in toc -->

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyProceedToCheckoutButtonLabel = (
	defaultValue,
	extensions,
	args
) => {
	if ( ! args?.cart.items ) {
		return defaultValue;
	}

	const isSunglassesInCart = args?.cart.items.some(
		( item ) => item.name === 'Sunglasses'
	);

	if ( isSunglassesInCart ) {
		return '😎 Proceed to checkout 😎';
	}

	return defaultValue;
};

registerCheckoutFilters( 'example-extension', {
	proceedToCheckoutButtonLabel: modifyProceedToCheckoutButtonLabel,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="379" alt="Before applying the Proceed To Checkout Button Label filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/fb0216c1-a091-4d58-b443-f49ccff98ed8">
</td>
<td valign="top">After:
<br><br>
<img width="379" alt="After applying the Proceed To Checkout Button Label filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/ef15b6df-fbd7-43e7-a359-b4adfbba961a">
</td>
</tr>
</table>

## `proceedToCheckoutButtonLink`

### Description <!-- omit in toc -->

The `proceedToCheckoutButtonLink` filter allows change the link of the "Proceed to checkout" button.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `/checkout`) - The link of the "Proceed to checkout" button.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](../available-filters.md#cart-object).

### Returns <!-- omit in toc -->

-   `string` - The link of the "Proceed to checkout" button.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyProceedToCheckoutButtonLink = (
	defaultValue,
	extensions,
	args
) => {
	if ( ! args?.cart.items ) {
		return defaultValue;
	}

	return '/custom-checkout';
};

registerCheckoutFilters( 'example-extension', {
	proceedToCheckoutButtonLink: modifyProceedToCheckoutButtonLink,
} );
```

#### Advanced example <!-- omit in toc -->

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyProceedToCheckoutButtonLink = (
	defaultValue,
	extensions,
	args
) => {
	if ( ! args?.cart.items ) {
		return defaultValue;
	}

	const isSunglassesInCart = args?.cart.items.some(
		( item ) => item.name === 'Sunglasses'
	);

	if ( isSunglassesInCart ) {
		return '/custom-checkout';
	}

	return defaultValue;
};

registerCheckoutFilters( 'example-extension', {
	proceedToCheckoutButtonLink: modifyProceedToCheckoutButtonLink,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="380" alt="Before applying the Proceed To Checkout Button Link filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/3f657e0f-4fcc-4746-a554-64221e071b2e">
</td>
<td valign="top">After:
<br><br>
<img width="384" alt="After applying the Proceed To Checkout Button Link filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/064df213-439e-4d8f-b29c-55962604cb97">
</td>
</tr>
</table>

## `placeOrderButtonLabel`

### Description <!-- omit in toc -->

The `placeOrderButtonLabel` filter allows change the label of the "Place order" button.

### Parameters <!-- omit in toc -->

-   _defaultValue_ (type: `string`, default: `Place order`) - The label of the "Place order" button.
-   _extensions_ `object` (default: `{}`) - The extensions object.

### Returns <!-- omit in toc -->

-   `string` - The label of the "Place order" button.

### Code example <!-- omit in toc -->

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyPlaceOrderButtonLabel = ( defaultValue, extensions ) => {
	return '😎 Pay now 😎';
};

registerCheckoutFilters( 'example-extension', {
	placeOrderButtonLabel: modifyPlaceOrderButtonLabel,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="358" alt="Before applying the Place Order Button Label filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/aa6d9b65-4d56-45f7-8162-a6bbfe171250">
</td>
<td valign="top">After:
<br><br>
<img width="358" alt="After applying the Place Order Button Label filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/a5cc2572-16e7-4781-a5ab-5d6cdced2ff6">
</td>
</tr>
</table>

<!-- FEEDBACK -->

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
-   _coupons_ `array` - The coupons array.
-   _crossSells_ `array` - The cross sell items array.
-   _errors_ `array` - The errors array.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _fees_ `array` - The fees array.
-   _hasCalculatedShipping_ `boolean` - Whether the cart has calculated shipping.
-   _items_ `array` - The cart items array with cart item objects, see [Cart Item object](#cart-item-object).
-   _itemsCount_ `number` - The number of items in the cart.
-   _itemsWeight_ `number` - The total weight of the cart items.
-   _needsPayment_ `boolean` - Whether the cart needs payment.
-   _needsShipping_ `boolean` - Whether the cart needs shipping.
-   _paymentMethods_ `array` - The payment methods array.
-   _paymentRequirements_ `array` - The payment requirements array.
-   _shippingAddress_ `object` - The shipping address object with the same keys as the billing address object.
-   _shippingRates_ `array` - The shipping rates array.
-   _totals_ `object` - The totals object with the following keys:
    -   _currency_code_ `string` - The currency code.
    -   _currency_decimal_separator_ `string` - The currency decimal separator.
    -   _currency_minor_unit_ `number` - The currency minor unit.
    -   _currency_prefix_ `string` - The currency prefix.
    -   _currency_suffix_ `string` - The currency suffix.
    -   _currency_symbol_ `string` - The currency symbol.
    -   _currency_thousand_separator_ `string` - The currency thousand separator.
    -   _tax_lines_ `array` - The tax lines array of objects with the following keys:
        -   _name_ `string` - The tax name.
        -   _price_ `string` - The tax price.
        -   _rate_ `string` - The tax rate.
    -   _total_discount_ `string` - The total discount.
    -   _total_discount_tax_ `string` - The total discount tax.
    -   _total_fee_ `string` - The total fee.
    -   _total_fee_tax_ `string` - The total fee tax.
    -   _total_items_ `string` - The total items.
    -   _total_items_tax_ `string` - The total items tax.
    -   _total_price_ `string` - The total price.
    -   _total_shipping_ `string` - The total shipping.
    -   _total_shipping_tax_ `string` - The total shipping tax.
    -   _total_tax_ `string` - The total tax.

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

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/checkout-block/available-filters/checkout-and-place-order-button.md)
