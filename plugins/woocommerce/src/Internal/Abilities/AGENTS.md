# WooCommerce abilities

The Core abilities live in `Domain/`. `AbilitiesLoader` registers them with `Ability` or a subclass of `ActionableAbility`. Two PHPStan rules keep it that way.

## Trust the input schema

`WP_Ability::execute()` validates the input against the input schema before it calls the permission or execute callback. `Ability` also converts the input to the schema types. So a callback gets `true`, not `"true"`, and `5`, not `"5"`.

Do not repeat in a callback what the schema already checks:

- No casts or type checks on input fields: `(int)`, `(string)`, `(bool)`, `is_array()`, `is_string()`.
- No string-boolean helpers on boolean fields: `wc_string_to_bool()`, `wp_validate_boolean()`. Read a boolean with `$input['field'] ?? false`.
- No checks for required fields, `minimum`, `maximum`, `enum` or `pattern`.
- No sanitizers on `enum` fields, or on the `email`, `date-time` and `uri` formats. The conversion already sanitizes those formats.

Put a new rule in the schema when the schema can express it. Check in the callback only what the schema cannot express, such as "send `id` or `filters`, not both".

Keep:

- Sanitizers on free text, such as `wp_kses_post()` on a note or `wc_clean()` on a SKU.
- Calls that normalize a valid value, such as `sanitize_title()` on a slug or `wc_format_decimal()` on a price.
- A cast that a WooCommerce function needs, such as `(int)` for `add_order_note()`.

## Omit unavailable output fields

Core ability output types are never nullable. Omit an unavailable field instead of returning `null`. Declare date fields as `string` with the `date-time` format.
