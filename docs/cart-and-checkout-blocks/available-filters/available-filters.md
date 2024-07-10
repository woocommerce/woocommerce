---
post_title: Cart and Checkout - Available filters
menu_title: Available Filters
tags: reference
---

This document lists the filters that are currently available to extensions and offers usage information for each one of them. Information on registering filters can be found on the [Checkout - Filter Registry](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/packages/checkout/filter-registry/README.md) page.

## Cart Line Items filters

The following [Cart Line Items filters](./cart-line-items.md) are available:

-   [`cartItemClass`](./cart-line-items.md#cartitemclass)
-   [`cartItemPrice`](./cart-line-items.md#cartitemprice)
-   [`itemName`](./cart-line-items.md#itemname)
-   [`saleBadgePriceFormat`](./cart-line-items.md#salebadgepriceformat)
-   [`showRemoveItemLink`](./cart-line-items.md#showremoveitemlink)
-   [`subtotalPriceFormat`](./cart-line-items.md#subtotalpriceformat)

The following screenshot shows which parts the individual filters affect:

![Cart Line Items](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-26-at-13.12.33.png)

## Order Summary Items filters

The following [Order Summary Items filters](./order-summary-items.md) are available:

-   [`cartItemClass`](./order-summary-items.md#cartitemclass)
-   [`cartItemPrice`](./order-summary-items.md#cartitemprice)
-   [`itemName`](./order-summary-items.md#itemname)
-   [`subtotalPriceFormat`](./order-summary-items.md#subtotalpriceformat)

The following screenshot shows which parts the individual filters affect:

![Order Summary Items](https://woocommerce.com/wp-content/uploads/2023/10/Screenshot-2023-10-26-at-16.29.45.png)

## Totals Footer Item filter

The following [Totals Footer Item filter](./totals-footer-item.md) is available:

-   [`totalLabel`](./totals-footer-item.md#totallabel)
-   [`totalValue`](./totals-footer-item.md#totalvalue)

## Checkout and place order button filters

The following [Checkout and place order button filters](./checkout-and-place-order-button.md) are available:

-   [`proceedToCheckoutButtonLabel`](./checkout-and-place-order-button.md#proceedtocheckoutbuttonlabel)
-   [`proceedToCheckoutButtonLink`](./checkout-and-place-order-button.md#proceedtocheckoutbuttonlink)
-   [`placeOrderButtonLabel`](./checkout-and-place-order-button.md#placeorderbuttonlabel)

## Coupon filters

The following [Coupon filters](./coupons.md) are available:

-   [`coupons`](./coupons.md#coupons-1)
-   [`showApplyCouponNotice`](./coupons.md#showapplycouponnotice)
-   [`showRemoveCouponNotice`](./coupons.md#showremovecouponnotice)

## Additional Cart and Checkout inner block types filter

The following [Additional Cart and Checkout inner block types filter](./additional-cart-checkout-inner-block-types.md) is available:

-   [`additionalCartCheckoutInnerBlockTypes`](./additional-cart-checkout-inner-block-types.md#additionalcartcheckoutinnerblocktypes)

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
