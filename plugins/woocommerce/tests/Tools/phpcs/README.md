# Local PHPCS sniffs

Custom PHP_CodeSniffer sniffs for WooCommerce Core that are not part of the shared
[`woocommerce/woocommerce-sniffs`](https://github.com/woocommerce/woocommerce-sniffs)
package. They live here (rather than in `WooCommerce-Core`) so they can ship and evolve
with the codebase they guard.

The sniffs are registered by relative path in `plugins/woocommerce/phpcs.xml`, and this
directory is excluded from being linted as product source (sniff classes must be named
`XxxSniff.php`, which conflicts with the WooCommerce filename convention).

## `WooCommerceInternal.DB.IdentifierPlaceholder`

Flags the `%i` SQL identifier placeholder inside a `wpdb::prepare()` call when it is **not**
guarded by `wpdb::has_cap( 'identifier_placeholders' )` in the same function.

WordPress 6.2 added `%i` to `wpdb::prepare()` for quoting table/column names, but a `$wpdb`
drop-in can run on a supported WordPress version without implementing it (its
`has_cap( 'identifier_placeholders' )` returns `false`). On such a layer `prepare()` treats
`%i` as a literal and shifts the remaining positional arguments, silently producing malformed
queries.

**How to satisfy the sniff:**

- Preferred — interpolate the trusted identifier directly into the query string:

  ```php
  $table = OrdersTableDataStore::get_orders_table_name();
  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted table name.
  $wpdb->prepare( "SELECT id FROM {$table} WHERE customer_id = %d", $customer_id );
  ```

- When `%i` is genuinely required, guard it and provide a fallback:

  ```php
  if ( $wpdb->has_cap( 'identifier_placeholders' ) ) {
      $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id );
  }
  ```

The identifier MUST be a trusted, developer-provided value (a table or column name), never raw
user input.

### Verifying the sniff

The fixture `WooCommerceInternal/Tests/DB/IdentifierPlaceholderUnitTest.inc` documents the
flagged and allowed cases. Run the sniff over it directly:

```sh
cd plugins/woocommerce
bin/composer/phpcs/vendor/bin/phpcs -s \
  --standard=tests/Tools/phpcs/WooCommerceInternal/ruleset.xml \
  tests/Tools/phpcs/WooCommerceInternal/Tests/DB/IdentifierPlaceholderUnitTest.inc
```

Only the two unguarded `%i` cases (cases 1 and 2) should be reported.

## `WooCommerceInternal.I18n.DiagnosticMessage`

Flags a WordPress translation call (`__()`, `esc_html__()`, `_n()` and the rest) inside the message passed to a developer diagnostic: `wc_doing_it_wrong()`, `_doing_it_wrong()`, `trigger_error()`, `wp_trigger_error()`, `user_error()`, `error_log()`, and the replacement or message of the deprecation notices (`wc_deprecated_*()`, `_deprecated_*()`, `apply_filters_deprecated()`, `do_action_deprecated()`, `wc_do_deprecated_action()`). Calls made through `LegacyProxy`, such as `$this->proxy->call_function( 'wc_doing_it_wrong', ... )`, are checked too.

These messages are read in logs, filtered by monitoring tools, and searched by developers and support. A translated message reads differently on every site language, so a log filter or a search for the English text misses it. PHP's own errors are English for the same reason.

**How to satisfy the sniff:** pass the English string directly. Keep any escaping the message had, without the translation:

```php
wc_doing_it_wrong( __FUNCTION__, 'This function should not be called before woocommerce_init.', '2.3' );

wc_doing_it_wrong(
	__METHOD__,
	sprintf( esc_html( 'A settings section is already registered for "%s".' ), esc_html( $section_id ) ),
	'10.9.0'
);
```

WordPress still translates its own wrapper around these messages (for example "Function %1$s was called incorrectly."), so only the WooCommerce part of a PHP notice is guaranteed to be English. Strings shown to merchants or shoppers stay translated, even when an error causes them.

The sniff only sees translation calls written inside the diagnostic call. It does not follow a translated string built elsewhere and passed in through a variable, a property or a wrapper method, so keep those English by hand.

### Verifying the sniff

The fixture `WooCommerceInternal/Tests/I18n/DiagnosticMessageUnitTest.inc` documents the flagged and allowed cases:

```sh
cd plugins/woocommerce
vendor/bin/phpcs -s \
  --standard=tests/Tools/phpcs/WooCommerceInternal/ruleset.xml \
  --sniffs=WooCommerceInternal.I18n.DiagnosticMessage \
  tests/Tools/phpcs/WooCommerceInternal/Tests/I18n/DiagnosticMessageUnitTest.inc
```

Only cases 1 to 6, 12 and 13 should be reported, one error each.
