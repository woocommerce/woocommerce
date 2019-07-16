---
name: "\U0001F41E Bug report"
about: Report a bug if something isn't working as expected in the core WooCommerce
  plugin.
title: ''
labels: ''
assignees: ''

---

**Describe the bug**
After woocommerce update to ver 3.6.5 the product pages do not appear. Just a blank screen. When you open products from the dashboard, all the product is there. You can do a quick edit but cannot view the product.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to 'www.smarthobbyllc.com'
2. Click on 'Buy Now'
3. Scroll down to '....'
4. See error

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Expected behavior**
When you clicked on buy now the product was display and quantities were requested.

**Isolating the problem (mark completed items with an [x]):**
- [X] I have deactivated other plugins and confirmed this bug occurs when only WooCommerce plugin is active.
- [X] This bug happens with a default WordPress theme active, or [Storefront](https://woocommerce.com/storefront/).
- [X] I can reproduce this bug consistently using the steps above.

**WordPress Environment**
<details>
```
Copy and paste the system status report from **WooCommerce > System Status** in WordPress admin.
```
`
### WordPress Environment ###

WordPress address (URL): https://www.smarthobbyllc.com
Site address (URL): https://www.smarthobbyllc.com
WC Version: 3.6.5
Log Directory Writable: ✔
WP Version: 5.2.2
WP Multisite: –
WP Memory Limit: 256 MB
WP Debug Mode: –
WP Cron: ✔
Language: en_US
External object cache: –

### Server Environment ###

Server Info: Apache
PHP Version: 7.2.15
PHP Post Max Size: 100 MB
PHP Time Limit: 300
PHP Max Input Vars: 1000
cURL Version: 7.45.0
OpenSSL/1.0.1e

SUHOSIN Installed: –
MySQL Version: 5.6.32-78.0-log
Max Upload Size: 100 MB
Default Timezone is UTC: ✔
fsockopen/cURL: ✔
SoapClient: ✔
DOMDocument: ✔
GZip: ✔
Multibyte String: ✔
Remote Post: ✔
Remote Get: ✔

### Database ###

WC Database Version: 3.6.5
WC Database Prefix: asb_
MaxMind GeoIP Database: ✔
Total Database Size: 6.45MB
Database Data Size: 5.16MB
Database Index Size: 1.29MB
asb_woocommerce_sessions: Data: 2.02MB + Index: 0.05MB
asb_woocommerce_api_keys: Data: 0.02MB + Index: 0.03MB
asb_woocommerce_attribute_taxonomies: Data: 0.02MB + Index: 0.02MB
asb_woocommerce_downloadable_product_permissions: Data: 0.02MB + Index: 0.06MB
asb_woocommerce_order_items: Data: 0.02MB + Index: 0.02MB
asb_woocommerce_order_itemmeta: Data: 0.02MB + Index: 0.03MB
asb_woocommerce_tax_rates: Data: 0.02MB + Index: 0.06MB
asb_woocommerce_tax_rate_locations: Data: 0.02MB + Index: 0.03MB
asb_woocommerce_shipping_zones: Data: 0.02MB + Index: 0.00MB
asb_woocommerce_shipping_zone_locations: Data: 0.02MB + Index: 0.03MB
asb_woocommerce_shipping_zone_methods: Data: 0.02MB + Index: 0.00MB
asb_woocommerce_payment_tokens: Data: 0.02MB + Index: 0.02MB
asb_woocommerce_payment_tokenmeta: Data: 0.02MB + Index: 0.03MB
asb_woocommerce_log: Data: 0.02MB + Index: 0.02MB
asb_aiowps_events: Data: 0.00MB + Index: 0.00MB
asb_aiowps_failed_logins: Data: 0.01MB + Index: 0.00MB
asb_aiowps_global_meta: Data: 0.00MB + Index: 0.00MB
asb_aiowps_login_activity: Data: 0.00MB + Index: 0.00MB
asb_aiowps_login_lockdown: Data: 0.00MB + Index: 0.00MB
asb_aiowps_permanent_block: Data: 0.00MB + Index: 0.00MB
asb_commentmeta: Data: 0.00MB + Index: 0.01MB
asb_comments: Data: 0.31MB + Index: 0.22MB
asb_failed_jobs: Data: 0.02MB + Index: 0.00MB
asb_links: Data: 0.00MB + Index: 0.00MB
asb_mailchimp_carts: Data: 0.02MB + Index: 0.00MB
asb_options: Data: 0.72MB + Index: 0.05MB
asb_postmeta: Data: 0.51MB + Index: 0.08MB
asb_posts: Data: 1.04MB + Index: 0.23MB
asb_queue: Data: 0.02MB + Index: 0.00MB
asb_smush_dir_images: Data: 0.00MB + Index: 0.00MB
asb_termmeta: Data: 0.00MB + Index: 0.01MB
asb_terms: Data: 0.00MB + Index: 0.01MB
asb_term_relationships: Data: 0.01MB + Index: 0.03MB
asb_term_taxonomy: Data: 0.00MB + Index: 0.00MB
asb_usermeta: Data: 0.01MB + Index: 0.01MB
asb_users: Data: 0.00MB + Index: 0.01MB
asb_wc_admin_notes: Data: 0.00MB + Index: 0.00MB
asb_wc_admin_note_actions: Data: 0.00MB + Index: 0.00MB
asb_wc_customer_lookup: Data: 0.00MB + Index: 0.00MB
asb_wc_download_log: Data: 0.02MB + Index: 0.03MB
asb_wc_order_coupon_lookup: Data: 0.00MB + Index: 0.00MB
asb_wc_order_product_lookup: Data: 0.00MB + Index: 0.00MB
asb_wc_order_stats: Data: 0.00MB + Index: 0.00MB
asb_wc_order_tax_lookup: Data: 0.00MB + Index: 0.00MB
asb_wc_product_meta_lookup: Data: 0.02MB + Index: 0.09MB
asb_wc_webhooks: Data: 0.02MB + Index: 0.02MB
asb_wpfm_backup: Data: 0.02MB + Index: 0.00MB
wp_mrd7fxman4_commentmeta: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_comments: Data: 0.00MB + Index: 0.01MB
wp_mrd7fxman4_gf_draft_submissions: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_gf_entry: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_gf_entry_meta: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_gf_entry_notes: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_gf_form: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_gf_form_meta: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_gf_form_revisions: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_gf_form_view: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_links: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_options: Data: 0.08MB + Index: 0.02MB
wp_mrd7fxman4_postmeta: Data: 0.02MB + Index: 0.02MB
wp_mrd7fxman4_posts: Data: 0.03MB + Index: 0.01MB
wp_mrd7fxman4_termmeta: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_terms: Data: 0.00MB + Index: 0.01MB
wp_mrd7fxman4_term_relationships: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_term_taxonomy: Data: 0.00MB + Index: 0.00MB
wp_mrd7fxman4_usermeta: Data: 0.00MB + Index: 0.01MB
wp_mrd7fxman4_users: Data: 0.00MB + Index: 0.01MB

### Post Type Counts ###

attachment: 32
customize_changeset: 18
custom_css: 1
nav_menu_item: 21
page: 18
post: 3
product: 12
revision: 140
scheduled-action: 1127
shop_coupon: 1

### Security ###

Secure connection (HTTPS): ✔
Hide errors from visitors: ✔

### Active Plugins (13) ###

Autoptimize: by Frank Goossens (futtta) – 2.5.1
WooCommerce Continue Shopping: by HappyKite – 1.3.1 – Not tested with the active version of WooCommerce
Facebook for WooCommerce: by Facebook – 1.9.15 – Not tested with the active version of WooCommerce
Image Size Selection for Divi: by Aaron Bolton – 1.0.3
Mailchimp for WooCommerce: by Mailchimp – 2.1.17
PHP Compatibility Checker: by WP Engine – 1.4.7
Compress JPEG & PNG images: by TinyPNG – 3.2.0
PayPal PLUS for WooCommerce: by Inpsyde GmbH – 2.0.4
WooCommerce Admin: by WooCommerce – 0.15.0
WooCommerce PayPal Checkout Gateway: by WooCommerce – 1.6.15
WooCommerce Services: by Automattic – 1.21.0
WooCommerce: by Automattic – 3.6.5
WP File Manager: by mndpsingh287 – 5.2

### Inactive Plugins (0) ###


### Dropin Plugins (1) ###

object-cache.php: APCu Object Cache

### Must Use Plugins (1) ###

System Plugin: by  – 3.9.5

### Settings ###

API Enabled: –
Force SSL: –
Currency: USD ($)
Currency Position: left
Thousand Separator: ,
Decimal Separator: .
Number of Decimals: 2
Taxonomies: Product Types: external (external)
grouped (grouped)
simple (simple)
variable (variable)

Taxonomies: Product Visibility: exclude-from-catalog (exclude-from-catalog)
exclude-from-search (exclude-from-search)
featured (featured)
outofstock (outofstock)
rated-1 (rated-1)
rated-2 (rated-2)
rated-3 (rated-3)
rated-4 (rated-4)
rated-5 (rated-5)

Connected to WooCommerce.com: ✔

### WC Pages ###

Shop base: #272 - /new-hobby-shop/
Cart: #106 - /cart/
Checkout: #123 - /checkout/
My account: #110 - /my-account/
Terms and conditions: ❌ Page not set

### Theme ###

Name: Divi
Version: 3.26.1
Author URL: http://www.elegantthemes.com
Child Theme: ❌ – If you are modifying WooCommerce on a parent theme that you did not build personally we recommend using a child theme. See: How to create a child theme
WooCommerce Support: ✔

### Templates ###

Overrides: –

### Action Scheduler ###

Complete: 1,124
Oldest: 2019-06-15 16:44:35 +0000
Newest: 2019-07-15 20:42:20 +0000

Pending: 0
Oldest: –
Newest: –

Canceled: 3
Oldest: 2019-07-15 13:01:47 +0000
Newest: 2019-07-15 21:42:20 +0000

In-progress: 0
Oldest: –
Newest: –

Failed: 0
Oldest: –
Newest: –

`</details>
