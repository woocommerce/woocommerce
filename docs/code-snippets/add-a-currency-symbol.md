---
post_title: Add currencies and symbols
menu_title: Add currencies and symbols
tags: code-snippet
---

Add custom currencies to your currencies list:

```php
if ( ! function_exists( 'YOUR_PREFIX_add_currency_name' ) ) {
  /**
   * Add custom currency
   * 
   * @param array $currencies Existing currencies.
   * @return array $currencies Updated currencies.
   */
  function YOUR_PREFIX_add_currency_name( $currencies ) {
    $currencies['ABC'] = __( 'Currency name', 'YOUR-TEXTDOMAIN' );

    return $currencies;
  }
  add_filter( 'woocommerce_currencies', 'YOUR_PREFIX_add_currency_name' );
}

if ( ! function_exists( 'YOUR_PREFIX_add_currency_symbol' ) ) {
  /**
   * Add custom currency symbol
   * 
   * @param string $currency_symbol Existing currency symbols.
   * @param string $currency Currency code.
   * @return string $currency_symbol Updated currency symbol(s).
   */
  function YOUR_PREFIX_add_currency_symbol( $currency_symbol, $currency ) {
    switch( $currency ) {
      case 'ABC': $currency_symbol = '$'; break;
    }

    return $currency_symbol;
  }
  add_filter('woocommerce_currency_symbol', 'YOUR_PREFIX_add_currency_symbol', 10, 2);
}
```
