---
post_title: Rename a country
tags: code-snippet
---

Add this code to your child theme's `functions.php` file or via a plugin that allows custom functions to be added, such as the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin. Avoid adding custom code directly to your parent theme's functions.php file, as this will be wiped entirely when you update the theme.

```php
if ( ! function_exists( 'YOUR_PREFIX_rename_country' ) ) {
  /**
   * Rename a country
   *
   * @param array $countries Existing country names
   * @return array $countries Updated country name(s)
   */
  function YOUR_PREFIX_rename_country( $countries ) {
     $countries['IE'] = __( 'Ireland (Changed)', 'YOUR-TEXTDOMAIN' );

     return $countries;
  }
  add_filter( 'woocommerce_countries', 'YOUR_PREFIX_rename_country' );
}
```
