---
post_title: Add or modify states
tags: code-snippet
---

Add your own or modify shipping states in WooCommerce.

> Note: you **must** replace both instances of XX with your country code. This means each state id in the array must have your two letter country code before the number you assign to the state.

```php
if ( ! function_exists( 'YOUR_PREFIX_add_or_modify_states' ) ) {
  /**
   * Add or modify States
   * 
   * @param array $states Existing country states.
   * @return array $states Modified country states.
   */
  function YOUR_PREFIX_add_or_modify_states( $states ) {
    $states['XX'] = array(
      'XX1' => __( 'State 1', 'YOUR-TEXTDOMAIN' ),
      'XX2' => __( 'State 2', 'YOUR-TEXTDOMAIN' ),
    );

    return $states;
  }
  add_filter( 'woocommerce_states', 'YOUR_PREFIX_add_or_modify_states' );
}
```
