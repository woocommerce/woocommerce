---
category_title: Available Filters
category_slug: cart-and-checkout-available-filters
post_title: Cart and Checkout - Available Filters
---

This document lists the filters that are currently available to extensions and offers usage information for each one of them. Information on registering filters can be found on the [Checkout - Filter Registry](../../../plugins/woocommerce/client/blocks/packages/checkout/filter-registry/README.md) page.

## Cart Line Items filters

The following [Cart Line Items filters](https://developer.woocommerce.com/docs/cart-and-checkout-filters-cart-line-items/) are available:

-   `cartItemClass`
-   `cartItemPrice`
-   `itemName`
-   `saleBadgePriceFormat`
-   `showRemoveItemLink`
-   `subtotalPriceFormat`

The following screenshot shows which parts the individual filters affect:

![Cart Line Items](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-26-at-13.12.33.png)

## Order Summary Items filters

The following [Order Summary Items filters](https://developer.woocommerce.com/docs/cart-and-checkout-filters-order-summary-items/) are available:

-   `cartItemClass`
-   `cartItemPrice`
-   `itemName`
-   `subtotalPriceFormat`

The following screenshot shows which parts the individual filters affect:

![Order Summary Items](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-26-at-16.29.45.png)

## Totals Footer Item filter

The following [Totals Footer Item filter](https://developer.woocommerce.com/docs/cart-and-checkout-filters-totals-footer-item/) is available:

-   `totalLabel`
-   `totalValue`

## Checkout and place order button filters

The following [Checkout and place order button filters](https://developer.woocommerce.com/docs/cart-and-checkout-filters-checkout-and-place-order-button/) are available:

-   `proceedToCheckoutButtonLabel`
-   `proceedToCheckoutButtonLink`
-   `placeOrderButtonLabel`

## Coupon filters

The following [Coupon filters](https://developer.woocommerce.com/docs/cart-and-checkout-filters-coupons/) are available:

-   `coupons`
-   `showApplyCouponNotice`
-   `showRemoveCouponNotice`

## Additional Cart and Checkout inner block types filter

The following [Additional Cart and Checkout inner block types filter](https://developer.woocommerce.com/docs/cart-and-checkout-filters-inner-block-types/) is available:

-   `additionalCartCheckoutInnerBlockTypes`

## Combined filters

Filters can also be combined. The following example shows how to combine some of the available filters.

```tsx
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const isOrderSummaryContext = ( args ) => args?.context === 'summary';

const modifyCartItemClass = ( defaultValue, extensions, args ) => {
	if ( isOrderSummaryContext( args ) ) {
		return 'my-custom-class';
	}
	return defaultValue;
};

const modifyCartItemPrice = ( defaultValue, extensions, args ) => {
	if ( isOrderSummaryContext( args ) ) {
		return '&lt;price/&gt; for all items';
	}
	return defaultValue;
};

const modifyItemName = ( defaultValue, extensions, args ) => {
	if ( isOrderSummaryContext( args ) ) {
		return `${ defaultValue }`;
	}
	return defaultValue;
};

const modifySubtotalPriceFormat = ( defaultValue, extensions, args ) => {
	if ( isOrderSummaryContext( args ) ) {
		return '&lt;price/&gt; per item';
	}
	return defaultValue;
};

registerCheckoutFilters( 'example-extension', {
	cartItemClass: modifyCartItemClass,
	cartItemPrice: modifyCartItemPrice,
	itemName: modifyItemName,
	subtotalPriceFormat: modifySubtotalPriceFormat,
} );
```

## Troubleshooting

If you are logged in to the store as an administrator, you should be shown an error like this if your filter is not
working correctly. The error will also be shown in your console.

![Troubleshooting](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-30-at-10.52.53.png)
