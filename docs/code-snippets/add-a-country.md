---
post_title: Add a country
menu_title: Add a country
tags: code-snippet
---

Add this code to your child theme's `functions.php` file or via a plugin that allows custom functions to be added, such as the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin. Avoid adding custom code directly to your parent theme's functions.php file, as this will be wiped entirely when you update the theme.

```php
if ( ! function_exists( 'YOUR_PREFIX_add_country_to_countries_list' ) ) {
  /**
   * Add a country to countries list
   * 
   * @param array $countries Existing country list.
   * @return array $countries Modified country list.
   */
  function YOUR_PREFIX_add_country_to_countries_list( $countries ) {
    $new_countries = array(
      'NIRE'  => __( 'Northern Ireland', 'YOUR-TEXTDOMAIN' ),
    );

    return array_merge( $countries, $new_countries );
  }
  add_filter( 'woocommerce_countries',  'YOUR_PREFIX_add_country_to_countries_list' );
}

if ( ! function_exists( 'YOUR_PREFIX_add_country_to_continents_list' ) ) {
  /**
   * Add a country to continents list
   * 
   * @param array $continents Existing continents list.
   * @return array $continents Modified continents list.
   */
  function YOUR_PREFIX_add_country_to_continents_list( $continents ) {
    $continents['EU']['countries'][] = 'NIRE';

    return $continents;
  }
  add_filter( 'woocommerce_continents', 'YOUR_PREFIX_add_country_to_continents_list' );
}
```
