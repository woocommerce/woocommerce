# Options Cache Priming

Covers correct usage of `wp_prime_option_caches()` to reduce SQL query counts when reading multiple options in a method or loop.

## Patterns

### 1. Missing options priming before reading a known set of keys

**Apply when:** A method reads multiple known `get_option()` keys in sequence.

**Correct pattern:**

```php
// Prime caches to reduce future queries.
wp_prime_option_caches(
    array(
        'woocommerce_enable_checkout_login_reminder',
        'woocommerce_tax_display_cart',
        // ...
    )
);
$login_reminder = get_option( 'woocommerce_enable_checkout_login_reminder' );
$tax_display    = get_option( 'woocommerce_tax_display_cart' );
```

No `! empty()` guard is needed for statically declared, always-non-empty arrays. Place the comment directly above the call.

**Common locations to check:**

- `register_routes()` methods that read options immediately after registration
- Block type `render()` or `get_data()` methods that read several settings
- Any method that reads more than one non-autoloaded option in sequence

---

### 2. Missing options priming before a loop with a derivable key pattern

**Apply when:** A loop iterates a collection and each iteration calls `get_option()` using a key derived from the item — for example `woocommerce_{class}_settings`.

**Correct pattern:**

```php
// Prime caches to reduce future queries.
wp_prime_option_caches(
    array_map( fn( string $class ) => sprintf( 'woocommerce_%s_settings', $class ), $classes )
);
foreach ( $classes as $class ) {
    $settings = get_option( sprintf( 'woocommerce_%s_settings', $class ) );
}
```

**Common locations to check:**

- Email class initialization: key pattern `woocommerce_{email_class_suffix}_settings`
- Shipping method loops: key pattern `woocommerce_{method}_settings`

---

### 3. Missing options priming when keys are extracted from a settings structure

**Apply when:** A settings array carries an `option_key` field; the array is iterated and each item's option is read via `get_option()`.

**Correct pattern:**

```php
$prefetch = array_column( $settings, 'option_key' ); // or equivalent extraction
if ( ! empty( $prefetch ) ) {
    // Prime caches to reduce future queries.
    wp_prime_option_caches( $prefetch );
}
foreach ( $settings as $setting ) {
    $value = get_option( $setting['option_key'] );
}
```

Guard with `! empty()` when the list is dynamically built and may be empty. When guarded, the comment sits inside the `if` block directly above the call — consistent with `_prime_post_caches` placement rules.

---

## Notes

`wp_prime_option_caches()` is a stable public WordPress function (no underscore prefix), available since WP 6.4. WooCommerce's minimum supported WordPress version guarantees its presence — no `is_callable()` guard is needed.

Always use the comment `// Prime caches to reduce future queries.` directly above the call. When the call is guarded by `! empty()`, the comment sits inside the `if` block — not before it.

The benefit of `wp_prime_option_caches` operates along two complementary dimensions — not binary logic:

- **Existence**: options not yet written to the database are absent from `wp_load_alloptions()` even when flagged autoloaded. Each `get_option()` call for a missing key issues an individual SQL query. Priming batches those misses into one query upfront.
- **Autoload state**: non-autoloaded options are never loaded at bootstrap regardless of whether they exist. Priming is the primary mechanism to avoid per-request queries for them.

An autoloaded option that has already been saved gains nothing from priming (already in cache). The same option before it is first saved benefits from the existence check. Both dimensions apply independently — consider both when deciding whether to prime.

For multisite contexts, use `wp_prime_network_option_caches( $network_id, $keys )` (available since WP 6.4) for network-scoped options.

---

## Autoload Architecture (WooCommerce-specific)

**WooCommerce settings API autoloads by default.** Any option registered and saved through `WC_Admin_Settings::save_fields()` is stored with `autoload = 'yes'` unless the field definition explicitly sets `'autoload' => false`. The relevant code is in `includes/admin/class-wc-admin-settings.php`:

```php
// Line ~1035
$autoload_options[ $option_name ] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;
// Line ~1047
update_option( $name, $value, $autoload_options[ $name ] ? 'yes' : 'no' );
```

WordPress loads all autoloaded options into the object cache at bootstrap via `wp_load_alloptions()`. This means that **any `get_option()` call reading a WooCommerce settings-API-registered option is already served from cache** — adding `wp_prime_option_caches` there is a no-op.

### False-positive patterns — do NOT add priming

High `get_option()` concentration alone is **not** a signal. These are common false positives:

- **Endpoint options** — `woocommerce_checkout_pay_endpoint`, `woocommerce_myaccount_*_endpoint`, etc. All autoloaded via settings API.
- **Feature flags and toggles** — `woocommerce_enable_ajax_add_to_cart`, `woocommerce_enable_checkout_login_reminder`, `woocommerce_tax_display_cart`, etc. All autoloaded.
- **General store settings** — currency, weight unit, address fields, etc. All autoloaded.

### The `*_settings` per-entity pattern

All three entity types extend `WC_Settings_API`, which saves settings with `autoload='yes'`. Once saved, these options are already in cache. However, on a fresh install or before settings are first saved, they are absent from `wp_load_alloptions()` — each `get_option()` issues an individual query. Priming is justified here specifically for the existence dimension (batching those misses), particularly when looping over a large number of entities such as email classes.

The four built-in payment gateways are a negligible count and are skipped.

| Location | Pattern | Status |
| --- | --- | --- |
| `includes/class-wc-emails.php` — `init()` | array_map over email class list | ✅ covered — batches miss queries on fresh/unconfigured installs |
| `includes/class-wc-shipping.php` — `get_shipping_method_class_names()` | array_map over method ID list | ✅ covered — same rationale |
| `includes/class-wc-payment-gateways.php` — `init()` | 4 built-in gateways — negligible count | ✅ verified, skipped |

### Workflow for gap analysis

When asked to find missing `wp_prime_option_caches` opportunities:

1. Search for multi-`get_option()` methods.
2. Consider both dimensions: autoload state (non-autoloaded options benefit on every request) and existence (options not yet saved benefit on first use regardless of autoload flag).
3. Flag loops or sequences reading multiple options where either dimension applies and no priming is present.
