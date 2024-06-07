# Order Summary Items

The following Order Summary Items filters are available:

-   [`cartItemClass`](#cartitemclass)
-   [`cartItemPrice`](#cartitemprice)
-   [`itemName`](#itemname)
-   [`subtotalPriceFormat`](#subtotalpriceformat)

The following objects are shared between the filters:

-   [Cart object](#cart-object)
-   [Cart Item object](#cart-item-object)

The following screenshot shows which parts the individual filters affect:

![Order Summary Items](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-26-at-16.29.45.png)

## `cartItemClass`

### Description <!-- omit in toc -->

The `cartItemClass` filter allows to change the order summary item class.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `''`) - The default order summary item class.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The order summary item object from `wc/store/cart`, see [order summary item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.

### Returns <!-- omit in toc -->

-   `string` - The modified order summary item class, or an empty string.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyCartItemClass = ( defaultValue, extensions, args ) => {
	const isOrderSummaryContext = args?.context === 'summary';

	if ( ! isOrderSummaryContext ) {
		return defaultValue;
	}

	return 'my-custom-class';
};

registerCheckoutFilters( 'example-extension', {
	cartItemClass: modifyCartItemClass,
} );
```

#### Advanced example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyCartItemClass = ( defaultValue, extensions, args ) => {
	const isOrderSummaryContext = args?.context === 'summary';

	if ( ! isOrderSummaryContext ) {
		return defaultValue;
	}

	if ( args?.cartItem?.name === 'Beanie with Logo' ) {
		return 'cool-class';
	}

	if ( args?.cartItem?.name === 'Sunglasses' ) {
		return 'hot-class';
	}

	return 'my-custom-class';
};

registerCheckoutFilters( 'example-extension', {
	cartItemClass: modifyCartItemClass,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="377" alt="Before applying the Cart Item Class filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/ff555a84-8d07-4889-97e1-8f7d50d47350">
</td>
<td valign="top">After:
<br><br>
<img width="383" alt="After applying the Cart Item Class filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/183809d8-03dc-466d-a415-d8d2062d880f">
</td>
</tr>
</table>

## `cartItemPrice`

### Description <!-- omit in toc -->

The `cartItemPrice` filter allows to format the order summary item price.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `<price/>`) - The default order summary item price.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The order summary item object from `wc/store/cart`, see [order summary item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.
-   _validation_ `boolean` - Checks if the return value contains the substring `<price/>`.

### Returns <!-- omit in toc -->

-   `string` - The modified format of the order summary item price, which must contain the substring `<price/>`, or the original price format.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyCartItemPrice = ( defaultValue, extensions, args, validation ) => {
	const isOrderSummaryContext = args?.context === 'summary';

	if ( ! isOrderSummaryContext ) {
		return defaultValue;
	}

	return '<price/> for all items';
};

registerCheckoutFilters( 'example-extension', {
	cartItemPrice: modifyCartItemPrice,
} );
```

#### Advanced example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyCartItemPrice = ( defaultValue, extensions, args, validation ) => {
	const isOrderSummaryContext = args?.context === 'summary';

	if ( ! isOrderSummaryContext ) {
		return defaultValue;
	}

	if ( args?.cartItem?.name === 'Beanie with Logo' ) {
		return '<price/> to keep you ☀️';
	}

	if ( args?.cartItem?.name === 'Sunglasses' ) {
		return '<price/> to keep you ❄️';
	}

	return '<price/> for all items';
};

registerCheckoutFilters( 'example-extension', {
	cartItemPrice: modifyCartItemPrice,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="381" alt="Before applying the Cart Item Price filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/58137fc4-884d-4783-9275-5f78abec1473">
</td>
<td valign="top">After:
<br><br>
<img width="382" alt="Before applying the Cart Item Price filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/fb502b74-6447-49a8-8d35-241e738f089d">
</td>
</tr>
</table>

## `itemName`

### Description <!-- omit in toc -->

The `itemName` filter allows to change the order summary item name.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` - The default order summary item name.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The order summary item object from `wc/store/cart`, see [order summary item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.

### Returns <!-- omit in toc -->

-   `string` - The original or modified order summary item name.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyItemName = ( defaultValue, extensions, args ) => {
	const isOrderSummaryContext = args?.context === 'summary';

	if ( ! isOrderSummaryContext ) {
		return defaultValue;
	}

	return `🪴 ${ defaultValue } 🪴`;
};

registerCheckoutFilters( 'example-extension', {
	itemName: modifyItemName,
} );
```

#### Advanced example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyItemName = ( defaultValue, extensions, args ) => {
	const isOrderSummaryContext = args?.context === 'summary';

	if ( ! isOrderSummaryContext ) {
		return defaultValue;
	}

	if ( args?.cartItem?.name === 'Beanie with Logo' ) {
		return `⛷️ ${ defaultValue } ⛷️`;
	}

	if ( args?.cartItem?.name === 'Sunglasses' ) {
		return `🏄‍♂️ ${ defaultValue } 🏄‍♂️`;
	}

	return `🪴 ${ defaultValue } 🪴`;
};

registerCheckoutFilters( 'example-extension', {
	itemName: modifyItemName,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="389" alt="Before applying the Item Name filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/3dc0bda7-fccf-4f35-a2e2-aa04e616563a">
</td>
<td valign="top">After:
<br><br>
<img width="383" alt="After applying the Item Name filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/c96b8394-03a7-45f6-813b-5335f4bf83b5">
</td>
</tr>
</table>

## `subtotalPriceFormat`

### Description <!-- omit in toc -->

The `subtotalPriceFormat` filter allows to format the order summary item subtotal price.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `<price/>`) - The default order summary item subtotal price.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The order summary item object from `wc/store/cart`, see [order summary item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.
-   _validation_ `boolean` - Checks if the return value contains the substring `<price/>`.

### Returns <!-- omit in toc -->

-   `string` - The modified format of the order summary item subtotal price, which must contain the substring `<price/>`, or the original price format.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifySubtotalPriceFormat = (
	defaultValue,
	extensions,
	args,
	validation
) => {
	const isOrderSummaryContext = args?.context === 'summary';

	if ( ! isOrderSummaryContext ) {
		return defaultValue;
	}

	return '<price/> per item';
};

registerCheckoutFilters( 'example-extension', {
	subtotalPriceFormat: modifySubtotalPriceFormat,
} );
```

#### Advanced example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifySubtotalPriceFormat = (
	defaultValue,
	extensions,
	args,
	validation
) => {
	const isOrderSummaryContext = args?.context === 'summary';

	if ( ! isOrderSummaryContext ) {
		return defaultValue;
	}

	if ( args?.cartItem?.name === 'Beanie with Logo' ) {
		return '<price/> per warm beanie';
	}

	if ( args?.cartItem?.name === 'Sunglasses' ) {
		return '<price/> per cool sunglasses';
	}

	return '<price/> per item';
};

registerCheckoutFilters( 'example-extension', {
	subtotalPriceFormat: modifySubtotalPriceFormat,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="382" alt="Before applying the Subtotal Price Format filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/3574e7ae-9857-4651-ac9e-e6b597e3a589">
</td>
<td valign="top">After:
<br><br>
<img width="390" alt="After applying the Subtotal Price Format filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/94e18439-6d6b-44a4-ade1-8302c5984641">
</td>
</tr>
</table>

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

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/checkout-block/available-filters/order-summary-items.md)
