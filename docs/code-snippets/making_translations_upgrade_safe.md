---
post_title: Making your translation upgrade safe
menu_title: Translation upgrade safety
tags: code-snippet
current wccom url: https://woocommerce.com/document/woocommerce-localization/#making-your-translation-upgrade-safe
---

Like all other plugins, WooCommerce keeps translations in `wp-content/languages/plugins`. 

However, if you want to include a custom translation, you can add them to `wp-content/languages/woocommerce`, or you can use a snippet to load a custom translation stored elsewhere:

```php
// Code to be placed in functions.php of your theme or a custom plugin file.
add_filter( 'load_textdomain_mofile', 'load_custom_plugin_translation_file', 10, 2 );

/*
 * Replace 'textdomain' with your plugin's textdomain. e.g. 'woocommerce'. 
 * File to be named, for example, yourtranslationfile-en_GB.mo
 * File to be placed, for example, wp-content/lanaguages/textdomain/yourtranslationfile-en_GB.mo
 */
function load_custom_plugin_translation_file( $mofile, $domain ) {
  if ( 'textdomain' === $domain ) {
    $mofile = WP_LANG_DIR . '/textdomain/yourtranslationfile-' . get_locale() . '.mo';
  }
  return $mofile;
}
```
