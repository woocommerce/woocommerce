# Cart Line Items

The following Cart Line Items filters are available:

-   [`cartItemClass`](#cartitemclass)
-   [`cartItemPrice`](#cartitemprice)
-   [`itemName`](#itemname)
-   [`saleBadgePriceFormat`](#salebadgepriceformat)
-   [`showRemoveItemLink`](#showremoveitemlink)
-   [`subtotalPriceFormat`](#subtotalpriceformat)

The following objects are shared between the filters:

-   [Cart object](#cart-object)
-   [Cart Item object](#cart-item-object)

The following screenshot shows which parts the individual filters affect:

![Cart Line Items](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-26-at-13.12.33.png)

## `cartItemClass`

### Description <!-- omit in toc -->

The `cartItemClass` filter allows to change the cart item class.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `object` (default: `''`) - The default cart item class.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The cart item object from `wc/store/cart`, see [Cart Item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.

### Returns <!-- omit in toc -->

-   `string` - The modified cart item class, or an empty string.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyCartItemClass = ( defaultValue, extensions, args ) => {
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
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
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
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
<img width="676" alt="Before applying the Cart Item Class filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/a587a6ce-d051-4ed0-bba5-815b5d72179d">
</td>
<td valign="top">After:
<br><br>
<img width="677" alt="After applying the Cart Item Class filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/9b25eeae-6d81-4e28-b177-32f942e1d0c2">
</td>
</tr>
</table>

## `cartItemPrice`

### Description <!-- omit in toc -->

The `cartItemPrice` filter allows to format the cart item price.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `<price/>`) - The default cart item price.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The cart item object from `wc/store/cart`, see [Cart Item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.
-   _validation_ `boolean` - Checks if the return value contains the substring `<price/>`.

### Returns <!-- omit in toc -->

-   `string` - The modified format of the cart item price, which must contain the substring `<price/>`, or the original price format.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyCartItemPrice = ( defaultValue, extensions, args, validation ) => {
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
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
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
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
<img width="673" alt="Before applying the Cart Item Price filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/78fdaabe-cb21-4697-bc64-56d0b95a501b">
</td>
<td valign="top">After:
<br><br>
<img width="675" alt="After applying the Cart Item Price filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/bbaeb68a-492e-41e7-87b7-4b8b05ca3709">
</td>
</tr>
</table>

## `itemName`

### Description <!-- omit in toc -->

The `itemName` filter allows to change the cart item name.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` - The default cart item name.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The cart item object from `wc/store/cart`, see [Cart Item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.

### Returns <!-- omit in toc -->

-   `string` - The original or modified cart item name.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyItemName = ( defaultValue, extensions, args ) => {
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
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
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
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
<img width="682" alt="Before applying the Item Name filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/97d0f501-138e-4448-93df-a4d865b524e6">
</td>
<td valign="top">After:
<br><br>
<img width="683" alt="After applying the Item Name filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/69381932-d064-4e8f-b378-c2477fef56ae">

</td>
</tr>
</table>

## `saleBadgePriceFormat`

### Description <!-- omit in toc -->

The `saleBadgePriceFormat` filter allows to format the cart item sale badge price.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `<price/>`) - The default cart item sale badge price.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The cart item object from `wc/store/cart`, see [Cart Item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.
-   _validation_ `boolean` - Checks if the return value contains the substring `<price/>`.

### Returns <!-- omit in toc -->

-   `string` - The modified format of the cart item sale badge price, which must contain the substring `<price/>`, or the original price format.

### Code examples <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifySaleBadgePriceFormat = (
	defaultValue,
	extensions,
	args,
	validation
) => {
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
		return defaultValue;
	}

	return '<price/> per item';
};

registerCheckoutFilters( 'example-extension', {
	saleBadgePriceFormat: modifySaleBadgePriceFormat,
} );
```

#### Advanced example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifySaleBadgePriceFormat = (
	defaultValue,
	extensions,
	args,
	validation
) => {
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
		return defaultValue;
	}

	if ( args?.cartItem?.name === 'Beanie with Logo' ) {
		return '<price/> per item while keeping warm';
	}

	if ( args?.cartItem?.name === 'Sunglasses' ) {
		return '<price/> per item while looking cool';
	}

	return '<price/> per item';
};

registerCheckoutFilters( 'example-extension', {
	saleBadgePriceFormat: modifySaleBadgePriceFormat,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="670" alt="Before applying the Sale Badge Price Format filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/d2aeb206-e620-44e0-93c1-31484cfcdca6">
</td>
<td valign="top">After:
<br><br>
<img width="677" alt="After applying the Sale Badge Price Format filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/6b929695-5d89-433b-8694-b9201a7c0519">
</td>
</tr>
</table>

## `showRemoveItemLink`

### Description <!-- omit in toc -->

The `showRemoveItemLink` is used to show or hide the cart item remove link.

### Parameters <!-- omit in toc -->

-   _defaultValue_ (type: `boolean`, default: `true`) - The default value of the remove link.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The cart item object from `wc/store/cart`, see [Cart Item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.

### Returns <!-- omit in toc -->

-   `boolean` - `true` if the cart item remove link should be shown, `false` otherwise.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyShowRemoveItemLink = ( defaultValue, extensions, args ) => {
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
		return defaultValue;
	}

	return false;
};

registerCheckoutFilters( 'example-extension', {
	showRemoveItemLink: modifyShowRemoveItemLink,
} );
```

#### Advanced example <!-- omit in toc -->

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyShowRemoveItemLink = ( defaultValue, extensions, args ) => {
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
		return defaultValue;
	}

	if ( args?.cartItem?.name === 'Beanie with Logo' ) {
		return false;
	}

	if ( args?.cartItem?.name === 'Sunglasses' ) {
		return false;
	}

	return true;
};

registerCheckoutFilters( 'example-extension', {
	showRemoveItemLink: modifyShowRemoveItemLink,
} );
```

> 💡 Filters can be also combined. See [Combined filters](../available-filters.md#combined-filters) for an example.

### Screenshots <!-- omit in toc -->

<table>
<tr>
<td valign="top">Before:
<br><br>
<img width="668" alt="Before applying the Show Remove Item Link filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/a4254f3b-f056-47ad-b34a-d5f6d5500e56">
</td>
<td valign="top">After:
<br><br>
<img width="669" alt="After applying the Show Remove Item Link filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/32c55dc7-ef65-4f35-ab90-9533bc79d362">
</td>
</tr>
</table>

## `subtotalPriceFormat`

### Description <!-- omit in toc -->

The `subtotalPriceFormat` filter allows to format the cart item subtotal price.

### Parameters <!-- omit in toc -->

-   _defaultValue_ `string` (default: `<price/>`) - The default cart item subtotal price.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _cart_ `object` - The cart object from `wc/store/cart`, see [Cart object](#cart-object).
    -   _cartItem_ `object` - The cart item object from `wc/store/cart`, see [Cart Item object](#cart-item-object).
    -   _context_ `string` (allowed values: `cart` or `summary`) - The context of the item.
-   _validation_ `boolean` - Checks if the return value contains the substring `<price/>`.

### Returns <!-- omit in toc -->

-   `string` - The modified format of the cart item subtotal price, which must contain the substring `<price/>`, or the original price format.

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
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
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
	const isCartContext = args?.context === 'cart';

	if ( ! isCartContext ) {
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
<img width="668" alt="Before applying the Subtotal Price Format filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/a392cb24-4c40-4e25-8396-bf4971830e22">
</td>
<td valign="top">After:
<br><br>
<img width="670" alt="After applying the Subtotal Price Format filter" src="https://github.com/woocommerce/woocommerce-blocks/assets/3323310/af69b26f-662a-4ef9-a288-3713b6e46373">
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

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/checkout-block/available-filters/cart-line-items.md)
