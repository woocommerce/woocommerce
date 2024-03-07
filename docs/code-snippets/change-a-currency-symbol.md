---
post_title: Change a currency symbol
tags: code-snippet
---

See the [currency list](https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-core-functions.html#source-view.475) for reference on currency codes.

Add this code to your child theme's `functions.php` file or via a plugin that allows custom functions to be added, such as the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin. Avoid adding custom code directly to your parent theme's functions.php file, as this will be wiped entirely when you update the theme.

```php
if ( ! function_exists( 'YOUR_PREFIX_change_currency_symbol' ) ) {
  /**
   * Change a currency symbol
   * 
   * @param string $currency_symbol Existing currency symbols.
   * @param string $currency Currency code.
   * @return string $currency_symbol Updated currency symbol(s).
   */  
  function YOUR_PREFIX_change_currency_symbol( $currency_symbol, $currency ) {
    switch ( $currency ) {
      case 'AUD': $currency_symbol = 'AUD$'; break;
    }

    return $currency_symbol;       
  }
  add_filter( 'woocommerce_currency_symbol', 'YOUR_PREFIX_change_currency_symbol', 10, 2 );  
}
```
