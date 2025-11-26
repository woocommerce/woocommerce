# Claude Code Documentation for Settings Payments backend

**Location**: `src/Internal/Admin/Settings/`
**Purpose**: Payment settings REST API controllers and business logic

## Quick Reference: JSON Schema Validation

**Common errors and fixes:**

| Error | Wrong | Correct |
|-------|-------|---------|
| Object with dynamic keys | `'items' => array(...)` | `'additionalProperties' => array(...)` |
| Enum as type | `'type' => 'enum'` | `'type' => 'string', 'enum' => [...]` |

**Valid types only:** `string`, `number`, `integer`, `boolean`, `array`, `object`, `null`

**Schema keywords:**

| Keyword | For Type | Purpose |
|---------|----------|---------|
| `properties` | `object` | Define named fields |
| `additionalProperties` | `object` | Define dynamic keys |
| `items` | `array` | Define element schema |
| `enum` | any | Constraint (not a type) |

## File Structure

```text
Settings/
|-- PaymentsRestController.php       # Main REST endpoint
|-- WooPaymentsRestController.php    # WooPayments endpoints
|-- Payments.php                     # Business logic
|-- PaymentsProviders.php            # Provider aggregation
`-- Utils.php                        # Utilities
```

## Critical Patterns

### Messages Field (Object with Dynamic Keys)

```php
// CORRECT - Use additionalProperties for objects with dynamic keys
'messages' => array(
    'type'                 => 'object',
    'additionalProperties' => array('type' => 'string'),
),

// WRONG - items only for arrays
'messages' => array(
    'type'  => 'object',
    'items' => array('type' => 'string'),  // ERROR
),
```

### Enum Constraints

```php
// CORRECT - enum is a constraint, not a type
'status' => array(
    'type' => 'string',
    'enum' => array('active', 'inactive'),
),

// WRONG - enum is not a valid type
'status' => array(
    'type' => 'enum',  // ERROR
    'enum' => array('active', 'inactive'),
),
```

### Schema Declaration

```php
$schema = array(
    '$schema' => 'https://json-schema.org/draft-04/schema#',
    'type'    => 'object',
);
```

## Known Issues Fixed

| File | Line | Issue | Fix |
|------|------|-------|-----|
| PaymentsRestController.php | 885-895 | `messages` uses `items` | Changed to `additionalProperties` |
| WooPaymentsRestController.php | 1066-1076 | `messages` uses `items` | Changed to `additionalProperties` |
| WooPaymentsRestController.php | 1107 | `type: enum` | Changed to `type: string` |
| WooPaymentsRestController.php | 1246 | `type: enum` | Changed to `type: string` |

## Known Issues (Incomplete Schemas)

### onboarding.state Schema (PaymentsRestController.php:880-884)

The `onboarding.state` field has an incomplete schema definition:

**Current schema:**

```php
'state' => array(
    'type'        => 'object',
    'description' => esc_html__( 'The state of the onboarding process.', 'woocommerce' ),
    'context'     => array( 'view', 'edit' ),
    // Missing: properties, readonly
),
```

**Actual implementation** (PaymentGateway.php:76-81):

```php
'state' => array(
    'supported' => $onboarding_supported,  // boolean
    'started'   => $this->is_onboarding_started( $gateway ),  // boolean
    'completed' => $this->is_onboarding_completed( $gateway ),  // boolean
    'test_mode' => $this->is_in_test_mode_onboarding( $gateway ),  // boolean
),
```

**Issues:**

1. Schema lacks `properties` definition constraining object structure
2. Missing `readonly` flag (inconsistent with other onboarding fields: `type`, `messages`, `steps`, `_links`)
3. No `required` array to indicate which fields are optional
4. No type validation for the boolean fields

**Impact:**

- API clients cannot rely on schema for validation
- Object structure depends on provider implementation
- No schema-level guarantees about field presence or types

**Note:** The `onboarding.messages.not_supported` field contains the reason when `onboarding.state.supported` is `false`.

## Validation Checklist

**Before committing schema changes:**

1. All `object` types use `properties` OR `additionalProperties` (never `items`)
2. All `array` types use `items` (never `additionalProperties`)
3. No `'type' => 'enum'` - use `'type' => 'string'` with `'enum' => [...]`
4. Schema version declared: `'$schema' => 'https://json-schema.org/draft-04/schema#'`

**Quick validation commands:**

```bash
# Find invalid enum types
grep -n "'type'.*=>.*'enum'" *.php

# Find objects incorrectly using items
grep -B2 "'type'.*=>.*'object'" *.php | grep -A2 "'items'"
```

## Response Structure Reference

**GET /wc-admin/settings/payments/providers:**

```json
{
  providers: [],               # Main list (gateways, suggestions, offline PM group)
  offline_payment_methods: [], # Individual offline PMs
  suggestions: [],             # Extensions not in main list
  suggestion_categories: []    # Category metadata
}
```

**Provider object:**

- Core: `id`, `_order`, `_type`, `title`, `description`
- Plugin: `plugin.{slug, status, file, _type}`
- State: `state.{enabled, account_connected, needs_setup, test_mode, dev_mode}`
- Onboarding: `onboarding.{type, state, messages, steps, _links}`
    - `state` fields (no schema validation): `{supported, started, completed, test_mode}`
    - `messages.not_supported`: Populated when `state.supported` is `false`
- Management: `management._links.settings.href`
- Metadata: `_suggestion_id`, `_incentive`

**Messages field structure:**

```php
array(
    'messages' => array(
        'error'   => 'Configuration required',
        'warning' => 'Test mode active',
        'info'    => 'Account connected',
    ),
)
```

## Key Classes

| File | Endpoint | Key Methods |
|------|----------|-------------|
| PaymentsRestController.php | `/wc-admin/settings/payments/*` | `get_providers()`, `set_country()`, `update_providers_order()` |
| WooPaymentsRestController.php | `/wc-admin/settings/payments/providers/woopayments/*` | `get_onboarding_details()` |
| Payments.php | N/A (business logic) | `get_payment_providers()`, `get_payment_extension_suggestions()` |

## Linting

**Array alignment auto-fix:**

```bash
pnpm run lint:php:fix -- src/Internal/Admin/Settings/PaymentsRestController.php
```

**Common fix:**

```php
'onboarding' => array(      # Before
'onboarding'     => array(  # After (aligned arrows)
```

## Related Documentation

- JSON Schema Draft 04: <https://json-schema.org/draft-04/schema#>
- WordPress REST API: <https://developer.wordpress.org/rest-api/>
- Main plugin docs: `../../CLAUDE.md`
