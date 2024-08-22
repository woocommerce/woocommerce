---
post_title: Using NGINX server to protect your upload directory
menu_title: NGINX server to protect upload directory
tags: code-snippet
current wccom url: https://woocommerce.com/document/digital-downloadable-product-handling/#protecting-your-uploads-directory
---

If you using NGINX server for your site along with **X-Accel-Redirect/X-Sendfile** or **Force Downloads** download method, it is necessary that you add this configuration for better security:

```php
# Protect WooCommerce upload folder from being accessed directly.
# You may want to change this config if you are using "X-Accel-Redirect/X-Sendfile" or "Force Downloads" method for downloadable products.
# Place this config towards the end of "server" block in NGINX configuration.
location ~* /wp-content/uploads/woocommerce_uploads/ {
    if ( $upstream_http_x_accel_redirect = "" ) {
        return 403;
    }
    internal;
}
```

And this the configuration in case you are using **Redirect only** download method:

```php
# Protect WooCommerce upload folder from being accessed directly.
# You may want to change this config if you are using "Redirect Only" method for downloadable products.
# Place this config towards the end of "server" block in NGINX configuration.
location ~* /wp-content/uploads/woocommerce_uploads/ {
    autoindex off;
}
```

If you do not know which web server you are using, please reach out to your host along with a link to this support page.
