---
post_title: How to configure caching plugins for WooCommerce
sidebar_label: Configure caching plugins
description: A guide to configuring caching plugins for WooCommerce to ensure proper functionality and performance.
---

# How to configure caching plugins for WooCommerce

## Excluding pages from the cache

Oftentimes if using caching plugins they'll already exclude these pages. Otherwise make sure you exclude the following pages from the cache through your caching systems respective settings.

- Cart
- My Account
- Checkout

These pages need to stay dynamic since they display information specific to the current customer and their cart.

## Excluding WooCommerce session from the cache

If the caching system you're using offers database caching, it might be helpful to exclude `_wc_session_` from being cached. This will be dependent on the plugin or host caching so refer to the specific instructions or docs for that system.

## Excluding WooCommerce cookies from the cache

Cookies in WooCommerce help track the products in your customers cart, can keep their cart in the database if they leave the site, and powers the recently viewed widget. Below is a list of the cookies WooCommerce uses for this, which you can exclude from caching.

| COOKIE NAME | DURATION | PURPOSE |
| --- | --- | --- |
| woocommerce_cart_hash | session | Helps WooCommerce determine when cart contents/data changes. |
| woocommerce_items_in_cart | session | Helps WooCommerce determine when cart contents/data changes. |
| wp_woocommerce_session_ | 2 days | Contains a unique code for each customer so that it knows where to find the cart data in the database for each customer. |
| woocommerce_recently_viewed | session | Powers the Recent Viewed Products widget. |
| store_notice[notice id] | session | Allows customers to dismiss the Store Notice. |


We're unable to cover all options, but we have added some tips for the popular caching plugins. For more specific support, please reach out to the support team responsible for your caching integration.

### W3 total cache minify settings

Ensure you add 'mfunc' to the 'Ignored comment stems' option in the Minify settings.

### WP-Rocket

WooCommerce is fully compatible with WP-Rocket. Please ensure that the following pages (Cart, Checkout, My Account) are not to be cached in the plugin's settings.

We recommend avoiding JavaScript file minification.

### WP Super Cache

WooCommerce is natively compatible with WP Super Cache. WooCommerce sends information to WP Super Cache so that it doesn't cache the Cart, Checkout, or My Account pages by default.

### Varnish

```varnish
if (req.url ~ "^/(cart|my-account|checkout|addons)") {
  return (pass);
}
if ( req.url ~ "\\?add-to-cart=" ) {
  return (pass);
}
```

## Serving cacheable HTML with the `woocommerce_should_hydrate` filter (experimental)

Several WooCommerce blocks bake per-customer data into their server-rendered HTML (the Mini-Cart badge and totals, the Product Button cart count, and the Cart and Checkout block pages). Out of the box this is safe because either the page carries no-cache headers (Cart, Checkout, My Account) or the cache layer is expected to bypass on the WooCommerce cart/session cookies, as described above.

For cache setups that want to go further, WooCommerce exposes the `woocommerce_should_hydrate` filter. Blocks that know how to recover their data on the client (via the Store API) consult it before rendering; when it returns `false` they emit neutral, anonymous markup instead of per-customer data, and load the real data client-side after the cached HTML paints.

```php
/**
 * @param bool   $default   Whether the output should be hydrated with per-user data.
 *                          True when the request is personalized (logged-in user or non-empty cart).
 * @param string $namespace Block or IAPI store namespace making the decision (e.g. `woocommerce/mini-cart`).
 * @return bool             Return false to emit neutral output that is safe to store in a shared cache.
 */
apply_filters( 'woocommerce_should_hydrate', $default, $namespace );
```

There is no setting for this; the filter is the only opt-in surface, and it is intended for cache integrations (CDNs, page caches, hosts) that know the caching policy applied to the request. The default is request-aware and matches existing behavior: hydrate when the request is personalized anyway (logged-in user, or a session with a non-empty cart — requests a policy-following cache layer bypasses on the cart/session cookies), emit neutral output otherwise. With no filter registered, nothing changes.

Two things the filter deliberately does **not** do:

- **It is a per-block hint, not a whole-page cacheability promise.** Only blocks that can recover client-side honor it. Third-party code that reads `WC()->cart` or the session and personalizes its output is unaffected and remains unsafe to cache.
- **It does not change the no-cache headers on the Cart, Checkout, and My Account pages.** `WC_Cache_Helper::prevent_caching()` keeps marking those pages uncacheable so extensions that personalize them stay protected. An integration that has audited its pages and wants to cache them anyway can strip the headers via the `wp_headers` filter (registered at priority 10 or later, after WooCommerce's priority 5), on its own responsibility. Note that `prevent_caching()` also defines the `DONOTCACHEPAGE` constant, which cannot be undone at runtime, so plugin-based page caches honoring that constant will still skip those pages.

### Serving shared anonymous HTML

Cache setups that serve the same cached HTML to all visitors regardless of cookies (aggressive edge caching, cache warming) should force neutral output for every request they intend to store:

```php
add_filter(
	'woocommerce_should_hydrate',
	fn( $default ) => is_user_logged_in() ? $default : false
);
```

Shoppers with a cart then receive the shared neutral page, and the Mini-Cart, Product Button, Cart, and Checkout blocks fetch their real data from the Store API on load. Logged-in traffic keeps full hydration because those requests are expected to bypass the cache.

### Header-gating recipe

A CDN that can add a request header on origin fetches can align the decision with its per-request policy — hydrate exactly when the response will not be stored, neutral exactly when it will:

```php
add_filter(
	'woocommerce_should_hydrate',
	function ( $default ) {
		// Only let the CDN gate anonymous traffic; logged-in users keep full hydration.
		if ( is_user_logged_in() ) {
			return $default;
		}

		// CDN sets this header on the (cacheable) variant of the request.
		if ( isset( $_SERVER['HTTP_X_WC_CACHE'] ) && 'cache' === $_SERVER['HTTP_X_WC_CACHE'] ) {
			return false; // Emit anonymous, cacheable output.
		}

		return $default;
	}
);
```

### Known limitation: server-only blocks that depend on the cart

Blocks whose markup can only be generated on the server *and* depends on the visitor's cart — currently the cart-referencing Product Collection used for cross-sells/upsells inside the Mini-Cart — cannot recover client-side and do not honor this filter. Under a shared-anonymous cache policy such blocks should not be used: cached pages would show them empty, and origin renders for cart-holding visitors are not cacheable. The Cart block's own Cross-Sells inner block is unaffected, as it renders client-side from the Store API. A platform-level refresh mechanism for these regions is being explored in [WordPress/gutenberg#80521](https://github.com/WordPress/gutenberg/issues/80521).

### Detecting hydration on the client

Blocks and extensions that render per-user UI can avoid flashing stale/empty state by checking whether the checkout payload was server-hydrated before rendering. The checkout data store exposes an `isCheckoutDataHydrated` selector that returns `false` on cached pages (where the data is fetched on the client) and `true` on hydrated pages. Cart readiness is available through the cart store's resolution state (`hasFinishedResolution( 'getCartData' )`).

## Troubleshooting

### Why is my Varnish configuration not working in WooCommerce?

Check out the following WordPress.org Support forum post on[ how cookies may be affecting your varnish coding](https://wordpress.org/support/topic/varnish-configuration-not-working-in-woocommerce).

```text
Add this to vcl_recv above "if (req.http.cookie) {":

# Unset Cookies except for WordPress admin and WooCommerce pages 
if (!(req.url ~ "(wp-login|wp-admin|cart|my-account/*|wc-api*|checkout|addons|logout|lost-password|product/*)")) { 
unset req.http.cookie; 
} 
# Pass through the WooCommerce dynamic pages 
if (req.url ~ "^/(cart|my-account/*|checkout|wc-api/*|addons|logout|lost-password|product/*)") { 
return (pass); 
} 
# Pass through the WooCommerce add to cart 
if (req.url ~ "\?add-to-cart=" ) { 
return (pass); 
} 
# Pass through the WooCommerce API
if (req.url ~ "\?wc-api=" ) { 
return (pass); 
} 
# Block access to php admin pages via website 
if (req.url ~ "^/phpmyadmin/.*$" || req.url ~ "^/phppgadmin/.*$" || req.url ~ "^/server-status.*$") { 
error 403 "For security reasons, this URL is only accessible using localhost (127.0.0.1) as the hostname"; 
} 

Add this to vcl_fetch:

# Unset Cookies except for WordPress admin and WooCommerce pages 
if ( (!(req.url ~ "(wp-(login|admin)|login|cart|my-account/*|wc-api*|checkout|addons|logout|lost-password|product/*)")) || (req.request == "GET") ) { 
unset beresp.http.set-cookie; 
} 
```

### Why is my Password Reset stuck in a loop?

This is due to the My Account page being cached, Some hosts with server-side caching don't prevent my-account.php from being cached.

If you're unable to reset your password and keep being returned to the login screen, please speak to your host to make sure this page is being excluded from their caching.
