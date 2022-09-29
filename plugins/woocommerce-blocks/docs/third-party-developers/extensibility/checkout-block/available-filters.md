# Available filters <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Cart Line Items](#cart-line-items)
-   [Order Summary Items](#order-summary-items)
-   [Totals footer item (in Mini Cart, Cart and Checkout)](#totals-footer-item-in-mini-cart-cart-and-checkout)
-   [Coupons](#coupons)
-   [Snackbar notices](#snackbar-notices)
-   [Place Order Button Label](#place-order-button-label)
-   [Examples](#examples)
    -   [Changing the wording of the Totals label in the Mini Cart, Cart and Checkout](#changing-the-wording-of-the-totals-label-in-the-mini-cart-cart-and-checkout)
    -   [Changing the format of the item's single price](#changing-the-format-of-the-items-single-price)
    -   [Change the name of a coupon](#change-the-name-of-a-coupon)
    -   [Hide a snackbar notice containing a certain string](#hide-a-snackbar-notice-containing-a-certain-string)
    -   [Change the label of the Place Order button](#change-the-label-of-the-place-order-button)
-   [Troubleshooting](#troubleshooting)

This document lists the filters that are currently available to extensions and offers usage information for each one of them. Information on registering filters can be found on the [Checkout - Filter Registry](../../../../packages/checkout/filter-registry/README.md) page.

## Cart Line Items

Line items refer to each item listed in the cart or checkout. For instance, the "Sunglasses" and "Beanie with Logo" in this image are the line items.

![Cart Line Items](https://user-images.githubusercontent.com/5656702/117027554-b7c3eb00-acf4-11eb-8af1-b8bedbe20e05.png)

The following filters are available for line items:

| Filter name            | Description                                                                                                                            | Return type                                                                           |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| `itemName`             | Used to change the name of the item before it is rendered onto the page                                                                | `string`                                                                              |
| `cartItemPrice`        | This is the price of the item, multiplied by the number of items in the cart.                                                          | `string` and **must** contain the substring `<price/>` where the price should appear. |
| `cartItemClass`        | This is the className of the item cell.                                                                                                | `string`                                                                              |
| `subtotalPriceFormat`  | This is the price of a single item. Irrespective of the number in the cart, this value will always be the current price of _one_ item. | `string` and **must** contain the substring `<price/>` where the price should appear. |
| `saleBadgePriceFormat` | This is the amount of money saved when buying this item. It is the difference between the item's regular price and its sale price.     | `string` and **must** contain the substring `<price/>` where the price should appear. |

Each of these filters has the following arguments passed to it: `{ context: 'cart', cartItem: CartItem }` ([CartItem](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/c00da597efe4c16fcf5481c213d8052ec5df3766/assets/js/type-defs/cart.ts#L113))

## Order Summary Items

In the Checkout block, there is a sidebar that contains a summary of what the customer is about to purchase. There are some filters available to modify the way certain elements are displayed on each item. The sale badges are not shown here, so those filters are not applied in the Order Summary.

![Order Summary Items](https://user-images.githubusercontent.com/5656702/117026942-1b014d80-acf4-11eb-8515-b9b777d96a74.png)

| Filter name           | Description                                                                                                                            | Return type                                                                           |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| `itemName`            | Used to change the name of the item before it is rendered onto the page                                                                | `string`                                                                              |
| `cartItemPrice`       | This is the price of the item, multiplied by the number of items in the cart.                                                          | `string` and **must** contain the substring `<price/>` where the price should appear. |
| `cartItemClass`       | This is the className of the item cell.                                                                                                | `string`                                                                              |
| `subtotalPriceFormat` | This is the price of a single item. Irrespective of the number in the cart, this value will always be the current price of _one_ item. | `string` and **must** contain the substring `<price/>` where the price should appear. |

Each of these filters has the following additional arguments passed to it: `{ context: 'summary', cartItem: CartItem }` ([CartItem](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/c00da597efe4c16fcf5481c213d8052ec5df3766/assets/js/type-defs/cart.ts#L113))

## Totals footer item (in Mini Cart, Cart and Checkout)

The word 'Total' that precedes the amount due, present in both the Cart _and_ Checkout blocks, is also passed through filters.

| Filter name  | Description                                                                                                   | Return type |
| ------------ | ------------------------------------------------------------------------------------------------------------- | ----------- |
| `totalLabel` | This is the label for the cart total. It defaults to 'Total' (or the word for 'Total' if using translations). | `string`    |

There are no additional arguments passed to this filter.

## Coupons

The current functionality is to display the coupon codes in the Cart and Checkout sidebars. This could be undesirable if you dynamically generate a coupon code that is not user-friendly. It may, therefore, be desirable to change the way this code is displayed. To achieve this, the filter `coupons` exists. This filter could also be used to show or hide coupons. This filter must _not_ be used to alter the value/totals of a coupon. This will not carry through to the Cart totals.

| Filter name | Description                                                | Return type    |
| ----------- | ---------------------------------------------------------- | -------------- |
| `coupons`   | This is an array of coupons currently applied to the cart. | `CartCoupon[]` |

The additional argument supplied to this filter is: `{ context: 'summary' }`. A `CartCoupon` has the following shape:

```ts
CartCoupon {
  code: string
  label: string
  discount_type: string
  totals: {
    currency_code: string
    currency_decimal_separator: string
    currency_minor_unit: number
    currency_prefix: string
    currency_suffix: string
    currency_symbol: string
    currency_thousand_separator: string
    total_discount: string
    total_discount_tax: string
  }
}
```

## Snackbar notices

There is a snackbar at the bottom of the page used to display notices to the customer, it looks like this:

![Snackbar notices](https://user-images.githubusercontent.com/5656702/120882329-d573c100-c5ce-11eb-901b-d7f206f74a66.png)

It may be desirable to hide this if there's a notice you don't want the shopper to see.

| Filter name                | Description                                                                                                                           | Return type |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| `snackbarNoticeVisibility` | An object keyed by the content of the notices slated to be displayed. The value of each member of this object will initially be true. | `object`    |

The filter passes an object whose keys are the `content` of each notice.

If there are two notices slated to be displayed ('Coupon code "10off" has been applied to your basket.', and 'Coupon code "50off" has been removed from your basket.'), the value passed to the filter would look like so:

```js
{
  'Coupon code "10off" has been applied to your basket.': true,
  'Coupon code "50off" has been removed from your basket.': true
}
```

To reiterate, the _value_ here will determine whether this notice gets displayed or not. It will display if true.

## Place Order Button Label

The Checkout block contains a button which is labelled 'Place Order' by default, but can be changed using the following filter.

| Filter name             | Description                                 | Return type |
| ----------------------- | ------------------------------------------- | ----------- |
| `placeOrderButtonLabel` | The wanted label of the Place Order button. | `string`    |

## Examples

### Changing the wording of the Totals label in the Mini Cart, Cart and Checkout

For this example, let's suppose we are building an extension that lets customers pay a deposit, and defer the full amount until a later date. To make it easier to understand what the customer is paying and why, let's change the value of `Total` to `Deposit due today`.

1. We need to create a `CheckoutFilterFunction`.

```ts
const replaceTotalWithDeposit = () => 'Deposit due today';
```

2. Now we need to register this filter function, and have it executed when the `totalLabel` filter is applied. We can access the `__experimentalRegisterCheckoutFilters` function on the `window.wc.blocksCheckout` object. As long as your extension's script is enqueued _after_ WooCommerce Blocks' scripts (i.e. by registering `wc-blocks-checkout` as a dependency), then this will be available.

```ts
const { __experimentalRegisterCheckoutFilters } = window.wc.blocksCheckout;
__experimentalRegisterCheckoutFilters( 'my-hypothetical-deposit-plugin', {
	totalLabel: replaceTotalWithDeposit,
} );
```

| Before                                                                                                                           | After                                                                                                                           |
| -------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| ![Snackbar notices before](https://user-images.githubusercontent.com/5656702/117032889-cc56b200-acf9-11eb-9bf7-ae5f6a0b1538.png) | ![Snackbar notices after](https://user-images.githubusercontent.com/5656702/117033039-ec867100-acf9-11eb-95d5-50c06bf2923c.png) |

### Changing the format of the item's single price

Let's say we want to add a little bit of text after an item's single price **in the Mini Cart and Cart blocks only**, just to make sure our customers know that's the price per item.

1. We will need to register a function to be executed when the `subtotalPriceFormat` is applied. Since we only want this to happen in the Cart context, our function will have to check the additional arguments passed to it to ensure the `context` value is `cart`. We can see from the table above, that our function needs to return a string that contains a substring of `<price/>`. This is a placeholder for the numeric value. The Mini Cart and Cart blocks will interpolate the value into the string we return.

```ts
const appendTextToPriceInCart = ( value, extensions, args ) => {
	if ( args?.context !== 'cart' ) {
		// Return early since this filter is not being applied in the Cart context.
		// We must return the original value we received here.
		return value;
	}
	return '<price/> per item';
};
```

2. Now we must register it. Refer to the first example for information about `__experimentalRegisterCheckoutFilters`.

```ts
const { __experimentalRegisterCheckoutFilters } = window.wc.blocksCheckout;
__experimentalRegisterCheckoutFilters( 'my-hypothetical-price-plugin', {
	subtotalPriceFormat: appendTextToPriceInCart,
} );
```

| Before                                                                                                         | After                                                                                                          |
| -------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| ![image](https://user-images.githubusercontent.com/5656702/117035086-d5488300-acfb-11eb-9954-feb326916168.png) | ![image](https://user-images.githubusercontent.com/5656702/117035616-70415d00-acfc-11eb-98d3-6c8096817e5b.png) |

### Change the name of a coupon

Let's say we're the author of an extension that automatically creates coupons for users, and applies them to the cart when certain items are bought in combination. Due to the internal workings of our extension, our automatically generated coupons are named something like `autocoupon_2020_06_29` - this doesn't look fantastic, so we want to change this to look a bit nicer. Our filtering function may look like this:

```ts
const filterCoupons = ( coupons ) => {
	return coupons.map( ( coupon ) => {
		// Regex to match autocoupon then unlimited undersores and numbers
		if ( ! coupon.label.match( /autocoupon(?:_\d+)+/ ) ) {
			return coupon;
		}
		return {
			...coupon,
			label: 'Automatic coupon',
		};
	} );
};
```

We'd register our filter like this:

```ts
import { __experimentalRegisterCheckoutFilters } from '@woocommerce/blocks-checkout';

__experimentalRegisterCheckoutFilters( 'automatic-coupon-extension', {
	coupons: filterCoupons,
} );
```

| Before                                                                                                         | After                                                                                                          |
| -------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| ![image](https://user-images.githubusercontent.com/5656702/123768988-bc55eb80-d8c0-11eb-9262-5d629837706d.png) | ![image](https://user-images.githubusercontent.com/5656702/124126048-2c57a380-da72-11eb-9b45-b2cae0cffc37.png) |

### Hide a snackbar notice containing a certain string

Let's say we want to hide all notices that contain the string `auto-generated-coupon`. We would do this by setting the value of the `snackbarNoticeVisibility` to false for the notices we would like to hide.

```ts
import { __experimentalRegisterCheckoutFilters } from '@woocommerce/blocks-checkout';

__experimentalRegisterCheckoutFilters( 'automatic-coupon-extension', {
	snackbarNoticeVisibility: ( value ) => {
		// Copy the value so we don't mutate what is being passed by the filter.
		const valueCopy = Object.assign( {}, value );
		Object.keys( value ).forEach( ( key ) => {
			valueCopy[ key ] = key.indexOf( 'auto-generated-coupon' ) === -1;
		} );
		return valueCopy;
	},
} );
```

### Change the label of the Place Order button

Let's assume a merchant want to change the label of the Place Order button _Place Order_ to _Pay now_. A merchant can achieve this by using the `placeOrderButtonLabel` filter.

1. We need to get the total price `placeOrderButtonLabel`.

```ts
const label = () => `Pay now`;
```

2. Now we have to register this filter function, and have it executed when the `placeOrderButtonLabel` filter is applied. We can access the `__experimentalRegisterCheckoutFilters` function on the `window.wc.blocksCheckout` object. As long as your extension's script is enqueued _after_ WooCommerce Blocks' scripts (i.e. by registering `wc-blocks-checkout` as a dependency), then this will be available.

```ts
const { __experimentalRegisterCheckoutFilters } = window.wc.blocksCheckout;
__experimentalRegisterCheckoutFilters( 'custom-place-order-button-label', {
	placeOrderButtonLabel: label,
} );
```

| Before                                                                                                         | After                                                                                                          |
| -------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| ![image](https://user-images.githubusercontent.com/3323310/190539657-f9097cce-5a57-4aa8-92cd-3475d755c991.png) | ![image](https://user-images.githubusercontent.com/3323310/190539830-cac0536f-df97-4773-bfda-129e23cec02a.png) |

## Troubleshooting

If you are logged in to the store as an administrator, you should be shown an error like this if your filter is not
working correctly.

![Troubleshooting](https://user-images.githubusercontent.com/5656702/117035848-b4ccf880-acfc-11eb-870a-31ae86dd6496.png)

The error will also be shown in your console.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/checkout-block/available-filters.md)

<!-- /FEEDBACK -->
