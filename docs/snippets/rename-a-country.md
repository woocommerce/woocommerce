# Rename a country

> This is a **Developer level** doc. If you are unfamiliar with code and resolving potential conflicts, select a [WooExpert or Developer](https://woocommerce.com/customizations/) for assistance. We are unable to provide support for customizations under our [Support Policy](http://www.woocommerce.com/support-policy/).

Add this code to your child theme’s `functions.php` file or via a plugin that allows custom functions to be added, such as the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin. Avoid adding custom code directly to your parent theme’s functions.php file, as this will be wiped entirely when you update the theme.

```php
if ( ! function_exists( 'YOUR_PREFIX_rename_country' ) ) {
  /**
   * Rename a country
   *
   * @param array $countries Existing country names
   * @return array $countries Updated country name(s)
   */
  function YOUR_PREFIX_rename_country( $countries ) {
     $countries['IE'] = __( 'Ireland', 'YOUR-TEXTDOMAIN' ),

     return $countries;
  }
  add_filter( 'woocommerce_countries', 'YOUR_PREFIX_rename_country' );
}
```
