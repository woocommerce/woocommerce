---
post_title: Change a currency symbol
tags: code-snippet
---

In WooCommerce, each currency is associated with a code and a symbol. For example, the Australian dollar has the code `AUD` and the symbol `$` (if you are interested, you can see a full list of codes and symbols in the [source code](https://github.com/woocommerce/woocommerce/blob/9.6.1/plugins/woocommerce/includes/wc-core-functions.php#L682)). 

However, there may be situations where you wish to change the symbol. Taking our example of the Australian dollar, it uses the same symbol as many other dollar currencies and so, in certain situations where this could lead to confusion, it might be useful to change it to `AUD$`. The following snippet outlines how this might be done:

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

You can add additional cases within the switch statements to make the same sort of change for any other currencies you support.
