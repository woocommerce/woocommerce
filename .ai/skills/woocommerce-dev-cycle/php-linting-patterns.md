# PHP Linting Patterns and Common Issues

## Table of Contents

- [Critical Rule: Lint Only Specific Files](#critical-rule-lint-only-specific-files)
- [Common PHP Linting Issues & Fixes](#common-php-linting-issues--fixes)
- [Translators Comment Placement](#translators-comment-placement)
- [PSR-12 File Header Order](#psr-12-file-header-order)
- [Mock Classes with Intentional Violations](#mock-classes-with-intentional-violations)
- [Multi-line Condition Alignment](#multi-line-condition-alignment)
- [Unused Closure Parameters](#unused-closure-parameters)
- [Array and Operator Alignment](#array-and-operator-alignment)
- [Indentation Rules](#indentation-rules)
- [Workflow for Fixing PHP Linting Issues](#workflow-for-fixing-php-linting-issues)
- [Quick Command Reference](#quick-command-reference)

## Critical Rule: Lint Only Specific Files

**NEVER run linting on the entire codebase.** Always lint specific files, changed files or staged files only.

```bash
# ✅ CORRECT: Check only changed files at the branch level
pnpm lint:php:changes

# ✅ CORRECT: Check only changed files that are staged
pnpm lint:php:changes:staged

# ✅ CORRECT: Lint specific file
pnpm lint:php -- path/to/file.php
pnpm lint:php:fix -- path/to/file.php

# ❌ WRONG: Lints entire codebase
pnpm lint:php
pnpm lint:php:fix
```

## Common PHP Linting Issues & Fixes

### Quick Reference Table

| Issue | Wrong | Correct |
|-------|-------|---------|
| **Translators comment** | Before return | Before function call |
| **File docblock (PSR-12)** | After `declare()` | Before `declare()` |
| **Indentation** | Spaces | Tabs only |
| **Array alignment** | Inconsistent | Align `=>` with context |
| **Equals alignment** | Inconsistent | Match surrounding style |

## Translators Comment Placement

Translators comments must be placed **immediately before the translation function call**, not before the return statement.

### Wrong - Comment Before Return

```php
/* translators: %s: Gateway name. */
return sprintf(
    esc_html__( '%s is not supported.', 'woocommerce' ),
    'Gateway'
);
```

### Correct - Comment Before Translation Function

```php
return sprintf(
    /* translators: %s: Gateway name. */
    esc_html__( '%s is not supported.', 'woocommerce' ),
    'Gateway'
);
```

### Multiple Parameters

```php
return sprintf(
    /* translators: 1: Gateway name, 2: Country code. */
    esc_html__( '%1$s is not available in %2$s.', 'woocommerce' ),
    $gateway_name,
    $country_code
);
```

## PSR-12 File Header Order

File docblocks must come **before** the `declare()` statement, not after.

### Wrong - Docblock After declare()

```php
<?php
declare( strict_types=1 );

/**
 * File docblock
 *
 * @package WooCommerce
 */
```

### Correct - Docblock Before declare()

```php
<?php
/**
 * File docblock
 *
 * @package WooCommerce
 */

declare( strict_types=1 );
```

## Mock Classes with Intentional Violations

When creating mock classes that must match external class names, use phpcs:disable comments:

```php
if ( ! class_exists( 'WC_Payments_Utils' ) ) {
    /**
     * Mock class for testing.
     *
     * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
     * phpcs:disable Suin.Classes.PSR4.IncorrectClassName
     * phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
     */
    class WC_Payments_Utils {
        /**
         * Mock implementation.
         */
        public static function supported_countries() {
            return array( 'US', 'GB' );
        }
    }
}
```

## Multi-line Condition Alignment

Use tabs for continuation lines in multi-line conditions:

```php
// Correct - tabs for continuation
if ( class_exists( '\WC_Payments_Utils' ) &&
    is_callable( '\WC_Payments_Utils::supported_countries' ) ) {
    // code
}

// Also correct - align with opening parenthesis
if ( class_exists( '\WC_Payments_Utils' ) &&
     is_callable( '\WC_Payments_Utils::supported_countries' ) ) {
    // code
}
```

## Unused Closure Parameters

When creating closures with parameters required by signature but unused, use `unset()` to avoid PHPCS errors:

### The Problem

```php
// ❌ WRONG - PHPCS error: Generic.CodeAnalysis.UnusedFunctionParameter
'callback' => function ( string $return_url ) {
    return array( 'success' => true );
},
```

### The Solution

```php
// ✅ CORRECT - unset unused parameters
'callback' => function ( string $return_url ) {
    unset( $return_url ); // Avoid parameter not used PHPCS errors.
    return array( 'success' => true );
},
```

### Multiple Unused Parameters

```php
'callback' => function ( $arg1, $arg2, $arg3 ) {
    unset( $arg1, $arg2 ); // Avoid parameter not used PHPCS errors.
    return $arg3;
},
```

### Common Scenarios

- Mock method callbacks in PHPUnit tests
- Array/filter callbacks where signature is fixed
- Interface implementations with unused parameters

**Reference:** `tests/php/src/Internal/Admin/Settings/PaymentsRestControllerIntegrationTest.php:1647-1655`

## Array and Operator Alignment

### Array Arrow Alignment

Align `=>` arrows consistently within each array context:

```php
// Correct - aligned arrows
$options = array(
    'gateway_id'   => 'stripe',
    'enabled'      => true,
    'country_code' => 'US',
);

// Also correct - no alignment for short arrays
$small = array(
    'id' => 123,
    'name' => 'Test',
);
```

### Assignment Operator Alignment

Match the surrounding code style:

```php
// When surrounding code aligns, align:
$gateway_id     = 'stripe';
$enabled        = true;
$country_code   = 'US';

// When surrounding code doesn't align, don't align:
$gateway_id = 'stripe';
$enabled = true;
$country_code = 'US';
```

## Indentation Rules

**Always use tabs, never spaces, for indentation.**

```php
// ✅ Correct - tabs for indentation
public function process_payment( $order_id ) {
→   $order = wc_get_order( $order_id );
→
→   if ( ! $order ) {
→   →   return false;
→   }
→
→   return true;
}

// ❌ Wrong - spaces for indentation
public function process_payment( $order_id ) {
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return false;
    }

    return true;
}
```

## Workflow for Fixing PHP Linting Issues

1. **Run linting on changed files:**

   ```bash
   pnpm lint:php:changes
   ```

2. **Auto-fix what you can:**

   ```bash
   pnpm lint:php:fix -- path/to/file.php
   ```

3. **Review remaining errors** - Common issues that require manual fixing:
   - Translators comment placement
   - File docblock order (PSR-12)
   - Unused closure parameters (add `unset()`)

4. **Address remaining issues manually**

5. **Verify the output is clean:**

   ```bash
   pnpm lint:php -- path/to/file.php
   ```

6. **Commit**

## Quick Command Reference

```bash
# Check changed files
pnpm lint:php:changes

# Check specific file
pnpm lint:php -- src/Internal/Admin/ClassName.php

# Fix specific file
pnpm lint:php:fix -- src/Internal/Admin/ClassName.php

# Check with error details
vendor/bin/phpcs -s path/to/file.php
```
