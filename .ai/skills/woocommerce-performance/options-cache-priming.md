# Options Cache Priming

Covers correct usage of `wp_prime_option_caches()` to batch cold option reads in methods, registries, and loops. Distinguish existing autoloaded options, missing options, cached negative results, and the active transient backend before adding work.

## Patterns

### 1. Missing options priming before reading a known set of keys

**Apply when:** a method reads several known `get_option()` keys, and those reads produce separate cold queries for non-autoloaded or not-yet-existing options.

**Correct pattern:**

```php
wp_prime_option_caches(
    array(
        'woocommerce_enable_checkout_login_reminder',
        'woocommerce_tax_display_cart',
    )
);
$login_reminder = get_option( 'woocommerce_enable_checkout_login_reminder' );
$tax_display    = get_option( 'woocommerce_tax_display_cart' );
```

The example shows placement, not proof that these particular options need priming on every store. Existing autoloaded settings normally need no explicit prime; missing or non-autoloaded settings may benefit when their cache entries are cold. A large number of `get_option()` calls alone does not establish an N+1 query problem.

No empty guard is needed for a statically declared, non-empty array. Place the prime before the first consumer and keep using `get_option()` for actual values so option filters and defaults retain their behavior. Add a comment only when a non-obvious placement or cache constraint needs explanation.

**Common locations to check:**

- `register_routes()` methods that actually read several settings during registration; do not move endpoint-only reads into every registration request.
- Block `render()` or `get_data()` methods that read a bounded set of settings.
- Methods that read several non-autoloaded or not-yet-saved options in sequence.

---

### 2. Missing options priming before a loop with a derivable key pattern

**Apply when:** each loop iteration reads an option whose name follows a known pattern, such as `woocommerce_{id}_settings`, and the relevant keys have cold misses.

**Correct pattern:**

```php
$keys = array_map(
    static fn( string $id ) => sprintf( 'woocommerce_%s_settings', $id ),
    $setting_ids
);
if ( ! empty( $keys ) ) {
    wp_prime_option_caches( $keys );
}
foreach ( $setting_ids as $id ) {
    $settings = get_option( sprintf( 'woocommerce_%s_settings', $id ) );
}
```

Derive keys using the consumer's actual naming rule. A class name is not necessarily an option ID: email class names need the same prefix removal and lowercasing as the email initialization path, and extension classes may define their own IDs.

**Place the prime before constructors that read settings.** If a loop instantiates a class or includes a file that returns an initialized object, priming afterward is too late. Use the final known class/definition list before that first read; do not instantiate every class just to discover keys if its constructor is what causes the queries.

**Common locations to check:**

- Email initialization in `WC_Emails::init()`, where built-in email settings keys can be derived before including their class files.
- Shipping method loops using `woocommerce_{method}_settings`, including legacy-method discovery.
- Gateway initialization, where built-in keys are known before gateway constructors run.

Do not assume a fixed built-in list covers keys introduced by filters or extensions. Inspect the final consumed collection and batch only keys whose derivation is known. [#62853](https://github.com/woocommerce/woocommerce/pull/62853) batches email/settings reads.

---

### 3. Missing options priming when keys are extracted from a settings structure

**Apply when:** a settings array contains an `option_key` field and the consumer reads each key with `get_option()`.

**Correct pattern:**

This example assumes every entry's `option_key` is a validated, non-empty option name and the consumer reads those entries directly.

```php
$prefetch = array_unique( array_column( $settings, 'option_key' ) );
if ( ! empty( $prefetch ) ) {
    wp_prime_option_caches( $prefetch );
}
foreach ( $settings as $setting ) {
    $value = get_option( $setting['option_key'] );
}
```

An empty guard makes sense for a dynamically built list that may contain no entries. Extract from the final settings structure, after relevant filtering, and skip headings or other definition entries that do not correspond to stored options.

**Normalize settings references to actual option names.** A field ID such as `woocommerce_example_settings[enabled]` can refer to the `enabled` element of the single `woocommerce_example_settings` option. Priming the full bracketed field ID would query a different name and could add a useless negative-cache entry. Follow the same parsing rule the settings reader uses, deduplicate the resulting top-level names, and retain the consumer's nested-value/default behavior.

Prime the bounded group that this consumer will read, rather than every settings page's keys on every request.

---

### 4. Registry / definition-array based priming at init time

**Apply when:** a class owns a bounded registry whose entries identify options explicitly or through a known naming convention, and initialization is followed by reads of those options.

**Correct pattern:**

```php
$option_names = array_map(
    static fn( $id, $definition ) => $definition['option_key'] ?? "woocommerce_feature_{$id}_enabled",
    array_keys( $this->registry ),
    $this->registry
);
if ( ! empty( $option_names ) ) {
    wp_prime_option_caches( array_unique( $option_names ) );
}
```

Place the call after the relevant definitions are registered and before the first consumer reads them. This may be near the end of registry initialization, but the first read determines placement: if constructing registry entries already reads settings, prime the known keys before those constructors instead.

Do not broaden a request's work to every possible registry entry just because initialization is convenient. A large registry used sparsely may need a prime scoped to the selected entries or endpoint instead.

`wp_prime_option_caches()` checks autoload and cache state itself. The query monitor may show only a subset of the supplied names because cached, negatively cached, or autoloaded keys can be skipped. That subset is expected; it is not evidence that the registry extraction failed.

**Real-world example:** `FeaturesController::init_feature_definitions()` derives `woocommerce_feature_{id}_enabled` names and honors explicit `option_key` overrides before subsequent feature checks. Cold missing/non-autoloaded keys can be batched there; without that batch they may produce separate reads. It does not follow that every `feature_is_enabled()` call always causes SQL. [#65883](https://github.com/woocommerce/woocommerce/pull/65883) covers registry-derived feature/gateway keys.

---

### 5. Transient names passed to `wp_prime_option_caches()` — unsafe under persistent object cache

**Anti-pattern:** passing transient backing-option names to an options-table priming API when the active transient storage is an external object cache.

With an external object cache, `get_transient()` and `set_transient()` use the `transient` cache group instead of the options table. Priming `_transient_{name}` and `_transient_timeout_{name}` rows on that path does not warm the cache entries the consumer uses.

For names absent from the options table, the prime can add entries to `notoptions`, WordPress's negative cache. Its size can grow as **new unique names** accumulate. Repeating the same names does not add new entries each time, and eviction or legacy transient rows in the database can affect the result. If the backend stores `notoptions` as a single value, a larger value can increase transfer and processing cost.

**Correct pattern for database-backed, site-local transients:**

This assumes `$transient_option_names` contains the actual backing-option names needed by a bounded set of upcoming transient reads, including timeout names where applicable.

```php
if ( ! wp_using_ext_object_cache() ) {
    wp_prime_option_caches( $transient_option_names );
}
```

`wp_using_ext_object_cache()` follows the same storage distinction used by the transient API. Skipping the options-table prime on the external-cache path avoids warming the wrong namespace. It does not imply that all external-cache reads are free or that no backend-specific batching can help. See [#65440](https://github.com/woocommerce/woocommerce/pull/65440) and its [skill correction #65441](https://github.com/woocommerce/woocommerce/pull/65441).

**Mixed key list:** keep regular options outside the guard. Guarding the entire list would also remove useful ordinary-option priming on sites with an external cache.

```php
wp_prime_option_caches( $regular_option_names );
if ( ! wp_using_ext_object_cache() ) {
    wp_prime_option_caches( $transient_option_names );
}
```

**Site transients need a separate scope check.** Their external-cache group is `site-transient`. On multisite, their database fallback uses network options rather than the current site's options table. For database-backed site transients, use the appropriate network-option cache API and network ID where available; do not pass `_site_transient_*` names to `wp_prime_option_caches()` merely because they resemble ordinary transient names. On single-site, check the site-option fallback path.

**Audit rule:** inspect any priming list containing `_transient_`, `_transient_timeout_`, `_site_transient_`, or `_site_transient_timeout_` prefixes. Confirm the cache-backend guard, the site's single-site/multisite scope, and the API/cache namespace that the consumer actually uses. A prefix check alone is insufficient.

---

## Notes

`wp_prime_option_caches()` is a public WordPress function available in WooCommerce's supported WordPress versions, so a redundant availability guard is unnecessary here. For network options, use `wp_prime_network_option_caches( $network_id, $keys )` when it is available in the supported versions; check that API's availability separately instead of assuming it shares the ordinary option helper's introduction version.

Consider both existence and autoload state, together with the current cache state:

| Option state | Consequence for priming |
| --- | --- |
| Existing and autoloaded | Normally already supplied by `alloptions`; explicit priming usually adds no SQL benefit. |
| Existing and non-autoloaded | Cold individual entries can benefit from a batch; warm entries may already avoid SQL. |
| Missing and not negatively cached | Priming can batch absence checks for several distinct names. |
| Missing and present in `notoptions` | The cached absence can already avoid SQL; repeated reads do not imply repeated database queries. |

Before an option is first saved, its definition's default autoload setting does not create a row in `alloptions`. Conversely, a missing option does not cause SQL on every call once its absence is cached. Filters and historic stored state can also affect the read path, so inspect the consumer and measure its actual queries.

Priming caches absence; it should not replace the option reader or invent a stored default. Keep dynamic defaults and option filters intact.

---

## Autoload Architecture (WooCommerce-specific)

**WooCommerce settings APIs commonly save options as autoloaded.** `WC_Admin_Settings::save_fields()` defaults a field's autoload setting to true unless its definition explicitly overrides it. In `includes/admin/class-wc-admin-settings.php`, the relevant expressions are:

```php
$autoload_options[ $option_name ] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;
update_option( $name, $value, $autoload_options[ $name ] ? 'yes' : 'no' );
```

These lines occur in separate loops; the definition determines the autoload argument used by the later save. WordPress's `alloptions` cache normally supplies existing autoloaded options without individual queries.

Do not infer live storage from a field definition alone. The option may not have been saved yet, may have an explicit override, or may retain historic state. Other writers and filters also matter. Settings-API registration is not a universal guarantee that every read is already a cache hit.

### False-positive patterns — verify before adding priming

High `get_option()` concentration alone is not a signal. Common settings that are often already autoloaded include:

- **Endpoint options** — `woocommerce_checkout_pay_endpoint`, `woocommerce_myaccount_*_endpoint`, and related URL settings.
- **Feature flags and toggles** — `woocommerce_enable_ajax_add_to_cart`, `woocommerce_enable_checkout_login_reminder`, and `woocommerce_tax_display_cart`.
- **General store settings** — currency, weight units, and address fields.

Check the actual options and cache state before adding a batch or dismissing one. A saved store and a fresh installation can take different paths for the same definitions.

### The `*_settings` per-entity pattern

Email, shipping, and payment gateway classes use `WC_Settings_API` settings, which commonly become autoloaded when saved. Before that first save, a group of distinct missing keys can produce cold absence checks. Priming can batch those checks, especially when initialization loads many entities together. Already negatively cached misses and warm saved settings will not show the same SQL benefit.

**Existing coverage to inspect before adding another prime:**

| Location | Pattern | Placement to inspect |
| --- | --- | --- |
| `includes/class-wc-emails.php` — `init()` | Keys derived from the built-in email class list | Before class files initialize email objects. |
| `includes/class-wc-shipping.php` — `get_shipping_method_class_names()` | Keys derived from legacy method IDs | Before reading which legacy methods are enabled. |
| `includes/class-wc-payment-gateways.php` — `init()` | Known built-in gateway option keys | Before constructing the gateways. |

These are discovery pointers, not a guarantee that every extension-provided key or later-added entity is covered. Compare the primed set with the final set consumed by the path under review.

### Workflow for gap analysis

When asked to find missing `wp_prime_option_caches()` opportunities:

1. Find methods or loops reading several options, including settings reads inside constructors.
2. Derive the actual top-level option names from the final settings definitions or registry. Account for custom keys and bracketed references.
3. Check existence, autoload, and positive/negative cache state on fresh/unconfigured and previously saved stores.
4. Trace existing priming and determine whether it runs before the first read and covers the final consumed keys.
5. Batch the bounded, relevant group of proven cold misses. For transients, confirm storage backend and multisite scope first.
6. Measure query-count changes and end-to-end cost separately, with external cache on and off. Retain the change only when the consuming path benefits without disproportionate payload or initialization work.

### Keep autoload changes consistent across time

Autoload moves cost into bootstrap payload. Use it for suitably bounded, frequently read values with evidence that this tradeoff helps the consuming requests.

Check installation defaults, existing stored values, the migration, and every later writer. In [#69693](https://github.com/woocommerce/woocommerce/pull/69693), settings saves undid an earlier autoload migration because the definitions still disabled autoload. A migration alone does not establish a lasting policy. Prefer WordPress option APIs for cache coherence and target the options that still exist.

Do not materialize a missing option merely to avoid a read until its absence semantics are understood. Absence can select a dynamic default, unlike a stored false or zero. The [#70409 review](https://github.com/woocommerce/woocommerce/pull/70409#discussion_r4423848691) identified that distinction for HPOS background synchronization; check absence behavior before adopting the proposed stored default.

For invalidation, background warming, and persistent counters, also read [database operations](database-operations.md#make-caching-and-scheduling-correct-across-their-lifecycle).
