# Translation basics

## Localization functions

Since [WordPress 2.1 "Ella"](https://wordpress.org/support/wordpress-version/version-2-1/), WordPress offers [internationalization (i18n)](https://developer.wordpress.org/plugins/internationalization/) for PHP files, and since [WordPress 5.0 "Bebo"](https://wordpress.org/support/wordpress-version/version-5-0/), it also offers i18n for JS files. Handling translations is pretty straight forward. Both PHP and JS handle translations similar. WordPress offers the functions:

-   [`__()`](https://developer.wordpress.org/reference/functions/__/) → Available in PHP & JS/TS.
-   [`_e()`](https://developer.wordpress.org/reference/functions/_e/) → Available in PHP only.
-   [`_ex()`](https://developer.wordpress.org/reference/functions/_ex/) → Available in PHP only.
-   [`_n()`](https://developer.wordpress.org/reference/functions/_n/) → Available in PHP & JS/TS.
-   [`_x()`](https://developer.wordpress.org/reference/functions/_x/) → Available in PHP & JS/TS.
-   [`_nx()`](https://developer.wordpress.org/reference/functions/_nx/) → Available in PHP & JS/TS.
-   [`esc_html__()`](https://developer.wordpress.org/reference/functions/esc_html__/) → Available in PHP only.
-   [`esc_html_e()`](https://developer.wordpress.org/reference/functions/esc_html_e/) → Available in PHP only.
-   [`esc_html_x()`](https://developer.wordpress.org/reference/functions/esc_html_x/) → Available in PHP only.
-   [`esc_attr__()`](https://developer.wordpress.org/reference/functions/esc_attr__/) → Available in PHP only.
-   [`esc_attr_e()`](https://developer.wordpress.org/reference/functions/esc_attr_e/) → Available in PHP only.
-   [`esc_attr_x()`](https://developer.wordpress.org/reference/functions/esc_attr_x/) → Available in PHP only.

## GlotPress

All translations are handled using [GlotPress](https://wordpress.org/plugins/glotpress/). As the WooCommerce Blocks plugin is hosted on <https://wordpress.org/>, all plugin-related translations can be found and managed on <https://translate.wordpress.org/projects/wp-plugins/woo-gutenberg-products-block/>.

## Text domain

Prior to [WordPress 4.6 “Pepper Adams”](https://wordpress.org/support/wordpress-version/version-4-6/), a text domain had to be defined to make the strings translatable. While it’s no longer a requirement to have a text domain, it does no harm to still include it. If the text domain is available, it has to match the slug of the plugin and is defined in the header of the main plugin file `woocommerce-gutenberg-products-block.php`:

```php
<?php
/**
 * [...]
 * Text Domain:  woo-gutenberg-products-block
 * [...]
 */
```

See also <https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains>.

## Loading Text Domain

Prior to [WordPress 4.6 “Pepper Adams”](https://wordpress.org/support/wordpress-version/version-4-6/), loading the text domain was required. As translations now take place on <https://translate.wordpress.org/>, loading the text domain using `load_plugin_textdomain()` is no longer required. In case the plugin does not load the text domain, the header of the main plugin file must include the definition `Requires at least:`. This definition must be set to 4.6 or higher.

See also <https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain>.

## Domain Path

Only plugins that are not hosted in the official WordPress Plugin Directory need to define a `Domain Path`. As the WooCommerce Blocks plugin is hosted in the official WordPress Plugin Directory, it does not need a `Domain Path`.

See also <https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#domain-path>.
