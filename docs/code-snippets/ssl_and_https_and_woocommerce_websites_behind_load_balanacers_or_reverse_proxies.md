---
post_title: SSL and HTTPS and WooCommerce
menu_title: SSL and HTTPS and WooCommerce
tags: code-snippet
current wccom url: https://woocommerce.com/document/ssl-and-https/#websites-behind-load-balancers-or-reverse-proxies
---

## Websites behind load balancers or reverse proxies

WooCommerce uses the `is_ssl()` WordPress function to verify if your website using SSL or not.

`is_ssl()` checks if the connection is via HTTPS or on Port 443. However, this won’t work for websites behind load balancers, especially websites hosted at Network Solutions. For details, read [WordPress is_ssl() function reference notes](https://codex.wordpress.org/Function_Reference/is_ssl#Notes).

Websites behind load balancers or reverse proxies that support `HTTP_X_FORWARDED_PROTO` can be fixed by adding the following code to the `wp-config.php` file, above the require_once call:

```php
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
    $_SERVER['HTTPS'] = 'on';
}
```

**Note:** If you use CloudFlare, you need to configure it. Check their documentation.
