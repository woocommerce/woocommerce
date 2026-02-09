---
sidebar_label: On-Sale Badge block
category_slug: product-sale-badge
post_title: On-Sale Badge block
---

# On-Sale Badge block

The On-Sale Badge block displays a "Sale" badge on products that are on sale.

> **Note:** This block uses the slug `woocommerce/product-sale-badge`.

## `woocommerce_sale_badge_text`

### Description <!-- omit in toc -->

The `woocommerce_sale_badge_text` filter allows customization of the sale badge text based on product context.

### Parameters <!-- omit in toc -->

-   _$sale_text_ `string` (default: `'Sale'`) - The sale badge text.
-   _$product_ `WC_Product` - The product object.

### Returns <!-- omit in toc -->

-   `string` - The filtered sale badge text.

### Code examples <!-- omit in toc -->

#### Basic example <!-- omit in toc -->

```php
add_filter( 'woocommerce_sale_badge_text', 'custom_sale_badge_text', 10, 2 );

function custom_sale_badge_text( $sale_text, $product ) {
	return __( 'On Sale', 'your-textdomain' );
}
```

#### Product-specific customization <!-- omit in toc -->

```php
add_filter( 'woocommerce_sale_badge_text', 'custom_sale_badge_by_product_type', 10, 2 );

function custom_sale_badge_by_product_type( $sale_text, $product ) {
	if ( $product->is_type( 'variable' ) ) {
		return __( 'Save Now', 'your-textdomain' );
	}

	if ( $product->is_type( 'simple' ) ) {
		return __( 'Limited Offer', 'your-textdomain' );
	}

	return $sale_text;
}
```

#### Discount percentage <!-- omit in toc -->

```php
add_filter( 'woocommerce_sale_badge_text', 'show_discount_percentage_badge', 10, 2 );

function show_discount_percentage_badge( $sale_text, $product ) {
	if ( $product->is_type( 'simple' ) || $product->is_type( 'external' ) ) {
		$regular_price = (float) $product->get_regular_price();
		$sale_price    = (float) $product->get_sale_price();

		if ( $regular_price > 0 ) {
			$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
			return sprintf( __( '-%s%%', 'your-textdomain' ), $percentage );
		}
	}

	return $sale_text;
}
```

## Difference from `woocommerce_sale_flash`

| Aspect | `woocommerce_sale_badge_text` | `woocommerce_sale_flash` |
| --- | --- | --- |
| **Context** | On-Sale Badge block | Classic templates (`loop/sale-flash.php`, `single-product/sale-flash.php`) |
| **Output** | Plain text | HTML markup |
| **Parameters** | `$sale_text`, `$product` | `$html`, `$post`, `$product` |
| **Default** | `'Sale'` | `'<span class="onsale">Sale!</span>'` |
| **Since** | WooCommerce 10.0.0 | WooCommerce 2.x |

### Output handling

The block filter expects plain text only. HTML tags will be escaped and displayed as text.

```php
// Correct - plain text
add_filter( 'woocommerce_sale_badge_text', function( $text, $product ) {
	return 'Hot Deal';
}, 10, 2 );

// Incorrect - HTML will be escaped
add_filter( 'woocommerce_sale_badge_text', function( $text, $product ) {
	return '<strong>Hot Deal</strong>'; // Displays as "&lt;strong&gt;Hot Deal&lt;/strong&gt;"
}, 10, 2 );
```

The classic filter expects HTML markup:

```php
add_filter( 'woocommerce_sale_flash', function( $html, $post, $product ) {
	return '<span class="onsale">Hot Deal</span>';
}, 10, 3 );
```

### Supporting both

To support both block and classic themes, implement both filters:

```php
// Block filter
add_filter( 'woocommerce_sale_badge_text', 'my_custom_sale_badge', 10, 2 );

function my_custom_sale_badge( $sale_text, $product ) {
	return __( 'Special Offer', 'your-textdomain' );
}

// Classic filter
add_filter( 'woocommerce_sale_flash', 'my_classic_sale_flash', 10, 3 );

function my_classic_sale_flash( $html, $post, $product ) {
	return '<span class="onsale">' . __( 'Special Offer', 'your-textdomain' ) . '</span>';
}
```

## Notes

-   The sale badge only renders when `$product->is_on_sale()` returns `true`.
-   Filter output is escaped with `esc_html()` by the block.
-   For Cart and Checkout blocks, use the [`saleBadgePriceFormat` filter](/docs/block-development/extensible-blocks/cart-and-checkout-blocks/filters-in-cart-and-checkout/cart-line-items/#salebadgepriceformat) instead.
