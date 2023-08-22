# Add or modify States

> This is a **Developer level** doc. If you are unfamiliar with code and resolving potential conflicts, select a [WooExpert or Developer](https://woocommerce.com/customizations/) for assistance. We are unable to provide support for customizations under our [Support Policy](http://www.woocommerce.com/support-policy/).

Add this code to your child theme’s `functions.php` file or via a plugin that allows custom functions to be added, such as the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin. Avoid adding custom code directly to your parent theme’s functions.php file, as this will be wiped entirely when you update the theme.

Add your own or modify shipping states in WooCommerce.

> **Note**
> You must replace both instances of XX with your country code. This means each state id in the array must have your two letter country code before the number you assign to the state.

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
      'XX1' => 'State 1', 
      'XX2' => 'State 2'
    );

    return $states;
  }
  add_filter( 'woocommerce_states', 'YOUR_PREFIX_add_or_modify_states' );
}
```
