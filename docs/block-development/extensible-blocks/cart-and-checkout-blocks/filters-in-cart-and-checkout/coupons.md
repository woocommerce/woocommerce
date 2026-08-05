---
post_title: Coupons
sidebar_label: Coupons

---

# Coupons

The following Coupon filters are available:

-   `coupons`
-   `showApplyCouponNotice`
-   `showRemoveCouponNotice`

## `coupons`

### Description 

The current functionality is to display the coupon codes in the Cart and Checkout sidebars. This could be undesirable if you dynamically generate a coupon code that is not user-friendly. It may, therefore, be desirable to change the way this code is displayed. To achieve this, the filter `coupons` exists. This filter could also be used to show or hide coupons. This filter must _not_ be used to alter the value/totals of a coupon. This will not carry through to the Cart totals.

### Parameters 

-   _coupons_ `object` - The coupons object with the following keys:
    -   _code_ `string` - The coupon code.
    -   _discount_type_ `string` - The type of discount. Can be `percent` or `fixed_cart`.
    -   _totals_ `object` - The totals object with the following keys:
        -   _currency_code_ `string` - The currency code.
        -   _currency_decimal_separator_ `string` - The currency decimal separator.
        -   _currency_minor_unit_ `number` - The currency minor unit.
        -   _currency_prefix_ `string` - The currency prefix.
        -   _currency_suffix_ `string` - The currency suffix.
        -   _currency_symbol_ `string` - The currency symbol.
        -   _currency_thousand_separator_ `string` - The currency thousand separator.
        -   _total_discount_ `string` - The total discount.
        -   _total_discount_tax_ `string` - The total discount tax.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following key:
    -   _context_ `string` (default: `summary`) - The context of the item.

### Returns 

-   `array` - The coupons array of objects with the same keys as above.

### Code example 

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyCoupons = ( coupons, extensions, args ) => {
	return coupons.map( ( coupon ) => {
		if ( ! coupon.label.match( /autocoupon(?:_\d+)+/ ) ) {
			return coupon;
		}

		return {
			...coupon,
			label: 'Automatic coupon',
		};
	} );
};

registerCheckoutFilters( 'example-extension', {
	coupons: modifyCoupons,
} );
```

> Filters can be also combined. See [Combined filters](/docs/block-development/extensible-blocks/cart-and-checkout-blocks/filters-in-cart-and-checkout/) for an example.

### Screenshots 

| Before                                                                 | After                                                                 |
|:---------------------------------------------------------------------:|:---------------------------------------------------------------------:|
|![Before applying the Coupons filter](https://github.com/woocommerce/woocommerce-blocks/assets/3323310/6cab1aff-e4b9-4909-b81c-5726c6a20c40) |![After applying the Coupons filter](https://github.com/woocommerce/woocommerce-blocks/assets/3323310/a5cc2572-16e7-4781-a5ab-5d6cdced2ff6) |

## `showApplyCouponNotice`

### Description 

### Parameters 

-   _value_ `boolean` (default: `true`) - Whether to show the apply coupon notice.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _context_ `string` (allowed values: `wc/cart` and `wc/checkout`) - The context of the coupon notice.
    -   _couponCode_ `string` - The coupon code.

### Returns 

-   `boolean` - Whether to show the apply coupon notice.

### Code examples 

#### Basic example 

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyShowApplyCouponNotice = ( defaultValue, extensions, args ) => {
	return false;
};

registerCheckoutFilters( 'example-extension', {
	showApplyCouponNotice: modifyShowApplyCouponNotice,
} );
```

#### Advanced example 

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyShowApplyCouponNotice = ( defaultValue, extensions, args ) => {
	if ( args?.couponCode === '10off' ) {
		return false;
	}

	return defaultValue;
};

registerCheckoutFilters( 'example-extension', {
	showApplyCouponNotice: modifyShowApplyCouponNotice,
} );
```

> Filters can be also combined. See [Combined filters](/docs/block-development/extensible-blocks/cart-and-checkout-blocks/filters-in-cart-and-checkout/) for an example.

### Screenshots 

| Before                                                                 | After                                                                 |
|:---------------------------------------------------------------------:|:---------------------------------------------------------------------:|
|![Before applying the Show Apply Coupon Notice filter](https://github.com/woocommerce/woocommerce-blocks/assets/3323310/374d4899-61f3-49b2-ae04-5541d4c130c2) |![After applying the Show Apply Coupon Notice filter](https://github.com/woocommerce/woocommerce-blocks/assets/3323310/c35dbd9b-eee4-4afe-9a29-9c554d467729) |

## `showRemoveCouponNotice`

### Description 

### Parameters 

-   _value_ `boolean` (default: `true`) - Whether to show the remove coupon notice.
-   _extensions_ `object` (default: `{}`) - The extensions object.
-   _args_ `object` - The arguments object with the following keys:
    -   _context_ `string` (allowed values: `wc/cart` and `wc/checkout`) - The context of the coupon notice.
    -   _couponCode_ `string` - The coupon code.

### Returns 

-   `boolean` - Whether to show the remove coupon notice.

### Code examples 

#### Basic example 

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyShowRemoveCouponNotice = ( defaultValue, extensions, args ) => {
	return false;
};

registerCheckoutFilters( 'example-extension', {
	showRemoveCouponNotice: modifyShowRemoveCouponNotice,
} );
```

#### Advanced example 

```ts
const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyShowRemoveCouponNotice = ( defaultValue, extensions, args ) => {
	if ( args?.couponCode === '10off' ) {
		return false;
	}

	return defaultValue;
};

registerCheckoutFilters( 'example-extension', {
	showRemoveCouponNotice: modifyShowRemoveCouponNotice,
} );
```

> Filters can be also combined. See [Combined filters](/docs/block-development/extensible-blocks/cart-and-checkout-blocks/filters-in-cart-and-checkout/) for an example.

### Screenshots 

| Before                                                                 | After                                                                 |
|:---------------------------------------------------------------------:|:---------------------------------------------------------------------:|
|![Before applying the Show Remove Coupon Notice filter](https://github.com/woocommerce/woocommerce-blocks/assets/3323310/9d8607fa-ab20-4181-b70b-7954e7aa49cb) |![After applying the Show Remove Coupon Notice filter](https://github.com/woocommerce/woocommerce-blocks/assets/3323310/83d5f65f-c4f3-4707-a250-077952514931) |
