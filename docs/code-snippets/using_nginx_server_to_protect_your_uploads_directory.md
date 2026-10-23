---
post_title: Using NGINX server to protect your upload directory
sidebar_label: NGINX server to protect upload directory

current wccom url: https://woocommerce.com/document/digital-downloadable-product-handling/#protecting-your-uploads-directory
---

# Using NGINX server to protect your upload directory

WooCommerce adds an `.htaccess` file to the `woocommerce_uploads` directory to stop downloadable product files from being fetched directly by URL. NGINX ignores `.htaccess` files, so stores running on NGINX need to add their own rule.

The rule depends on your store's download method. To check it, go to **WooCommerce > Settings > Products > Downloadable products** and look at **File download method**. **Force downloads** is the default.

## X-Accel-Redirect/X-Sendfile or Force Downloads

If your store uses the **X-Accel-Redirect/X-Sendfile** or **Force Downloads** download method, add this to your NGINX `server` block:

```nginx
# Block direct access to WooCommerce downloadable product files.
# Customers still get their files through WooCommerce download links.
location ~* /woocommerce_uploads/ {
    internal;
}
```

Place it **before** any other `location` block that uses a regular expression, such as a rule for static files like `location ~* \.(pdf|zip|jpg)$`. NGINX uses the first regular expression location that matches a request, so a static file rule placed earlier would serve downloadable files directly.

The rule matches the `woocommerce_uploads` directory wherever your uploads are stored, including multisite subsites (for example `/wp-content/uploads/sites/2/woocommerce_uploads/`) and sites that move the `wp-content` or uploads directory.

If **Allow using redirect mode (insecure) as a last resort** is turned on under the same settings, WooCommerce sends customers straight to the file when the selected method fails. This rule blocks those direct requests, so the fallback download fails instead.

## Redirect only

With the **Redirect only** download method, customers are sent straight to the file, so files can't be blocked. To stop the directory from listing its files, add:

```nginx
location ~* /woocommerce_uploads/ {
    autoindex off;
}
```

## Check your configuration

After reloading NGINX, go to **Tools > Site Health** in your WordPress dashboard. The WooCommerce uploads directory check reports whether downloadable product files can be accessed directly from the web.

If you do not know which web server you are using, please reach out to your host along with a link to this page.
