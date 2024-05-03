# Available filters <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Cart Line Items filters](#cart-line-items-filters)
-   [Order Summary Items filters](#order-summary-items-filters)
-   [Totals Footer Item filter](#totals-footer-item-filter)
-   [Checkout and place order button filters](#checkout-and-place-order-button-filters)
-   [Coupon filters](#coupon-filters)
-   [Additional Cart and Checkout inner block types filter](#additional-cart-and-checkout-inner-block-types-filter)
-   [Combined filters](#combined-filters)
-   [Troubleshooting](#troubleshooting)

This document lists the filters that are currently available to extensions and offers usage information for each one of them. Information on registering filters can be found on the [Checkout - Filter Registry](../../../../packages/checkout/filter-registry/README.md) page.

## Cart Line Items filters

The following [Cart Line Items filters](./available-filters/cart-line-items.md) are available:

-   [`cartItemClass`](./available-filters/cart-line-items.md#cartitemclass)
-   [`cartItemPrice`](./available-filters/cart-line-items.md#cartitemprice)
-   [`itemName`](./available-filters/cart-line-items.md#itemname)
-   [`saleBadgePriceFormat`](./available-filters/cart-line-items.md#salebadgepriceformat)
-   [`showRemoveItemLink`](./available-filters/cart-line-items.md#showremoveitemlink)
-   [`subtotalPriceFormat`](./available-filters/cart-line-items.md#subtotalpriceformat)

The following screenshot shows which parts the individual filters affect:

![Cart Line Items](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-26-at-13.12.33.png)

## Order Summary Items filters

The following [Order Summary Items filters](./available-filters/order-summary-items.md) are available:

-   [`cartItemClass`](./available-filters/order-summary-items.md#cartitemclass)
-   [`cartItemPrice`](./available-filters/order-summary-items.md#cartitemprice)
-   [`itemName`](./available-filters/order-summary-items.md#itemname)
-   [`subtotalPriceFormat`](./available-filters/order-summary-items.md#subtotalpriceformat)

The following screenshot shows which parts the individual filters affect:

![Order Summary Items](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-26-at-16.29.45.png)

## Totals Footer Item filter

The following [Totals Footer Item filter](./available-filters/totals-footer-item.md) is available:

-   [`totalLabel`](./available-filters/totals-footer-item.md#totallabel)
-   [`totalValue`](./available-filters/totals-footer-item.md#totalvalue)

## Checkout and place order button filters

The following [Checkout and place order button filters](./available-filters/checkout-and-place-order-button.md) are available:

-   [`proceedToCheckoutButtonLabel`](./available-filters/checkout-and-place-order-button.md#proceedtocheckoutbuttonlabel)
-   [`proceedToCheckoutButtonLink`](./available-filters/checkout-and-place-order-button.md#proceedtocheckoutbuttonlink)
-   [`placeOrderButtonLabel`](./available-filters/checkout-and-place-order-button.md#placeorderbuttonlabel)

## Coupon filters

The following [Coupon filters](./available-filters/coupons.md) are available:

-   [`coupons`](./available-filters/coupons.md#coupons-1)
-   [`showApplyCouponNotice`](./available-filters/coupons.md#showapplycouponnotice)
-   [`showRemoveCouponNotice`](./available-filters/coupons.md#showremovecouponnotice)

## Additional Cart and Checkout inner block types filter

The following [Additional Cart and Checkout inner block types filter](./available-filters/additional-cart-checkout-inner-block-types.md) is available:

-   [`additionalCartCheckoutInnerBlockTypes`](./available-filters/additional-cart-checkout-inner-block-types.md#additionalcartcheckoutinnerblocktypes)

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
		return '<price/> for all items';
	}
	return defaultValue;
};

const modifyItemName = ( defaultValue, extensions, args ) => {
	if ( isOrderSummaryContext( args ) ) {
		return `🪴 ${ defaultValue } 🪴`;
	}
	return defaultValue;
};

const modifySubtotalPriceFormat = ( defaultValue, extensions, args ) => {
	if ( isOrderSummaryContext( args ) ) {
		return '<price/> per item';
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

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/checkout-block/available-filters.md)

<!-- /FEEDBACK -->
