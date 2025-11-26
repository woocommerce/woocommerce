# JavaScript/TypeScript i18n Patterns

## Table of Contents

- [Overview](#overview)
- [Translation Functions](#translation-functions)
- [Placeholder Patterns](#placeholder-patterns)
- [Translator Comments](#translator-comments)
- [Complex String Patterns](#complex-string-patterns)
- [Common Pitfalls](#common-pitfalls)
- [Quick Command Reference](#quick-command-reference)

## Overview

WooCommerce uses WordPress i18n functions from `@wordpress/i18n` for
translatable strings. Brand names like "WooPayments" should use placeholders
to improve translation flexibility.

```typescript
import { __, sprintf } from '@wordpress/i18n';
```

## Translation Functions

### Basic Translation

```typescript
// Simple string
__( 'Save changes', 'woocommerce' )

// String with placeholder
sprintf(
    /* translators: %s: Payment provider name (e.g., WooPayments) */
    __( 'Set up %s', 'woocommerce' ),
    'WooPayments'
)
```

### Plural Forms with `_n`

```typescript
import { _n, sprintf } from '@wordpress/i18n';

sprintf(
    /* translators: %d: Number of items */
    _n(
        '%d item selected',
        '%d items selected',
        count,
        'woocommerce'
    ),
    count
)
```

### Interpolated Elements with `createInterpolateElement`

```typescript
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

createInterpolateElement(
    sprintf(
        /* translators: 1: Payment provider name */
        __( 'Enable <strong>%1$s</strong> for your store.', 'woocommerce' ),
        'WooPayments'
    ),
    {
        strong: <strong />,
    }
)
```

## Placeholder Patterns

### Single Placeholder

Use `%s` for a single placeholder:

```typescript
sprintf(
    /* translators: %s: Payment provider name (e.g., WooPayments) */
    __( 'Get paid with %s', 'woocommerce' ),
    'WooPayments'
)
```

### Multiple Same Placeholders

Use numbered placeholders `%1$s` when the same value appears multiple times:

```typescript
sprintf(
    /* translators: 1: Payment provider name (e.g., WooPayments) */
    __(
        'By using %1$s you agree to our Terms. Payments via %1$s are secure.',
        'woocommerce'
    ),
    'WooPayments'
)
```

### Multiple Different Placeholders

Use numbered placeholders `%1$s`, `%2$s` for different values:

```typescript
sprintf(
    /* translators: 1: Payment provider name, 2: Extension names */
    _n(
        'Installing %1$s will activate %2$s extension.',
        'Installing %1$s will activate %2$s extensions.',
        extensionCount,
        'woocommerce'
    ),
    'WooPayments',
    extensionNames
)
```

## Translator Comments

### Comment Placement

The translator comment must be placed **immediately before the `__()` or
`_n()` function**, not before `sprintf()`:

```typescript
// ❌ WRONG - Comment before sprintf
/* translators: %s: Payment provider name */
sprintf(
    __( 'Set up %s', 'woocommerce' ),
    'WooPayments'
)

// ✅ CORRECT - Comment inside sprintf, before __()
sprintf(
    /* translators: %s: Payment provider name */
    __( 'Set up %s', 'woocommerce' ),
    'WooPayments'
)
```

### Comment Format for Numbered Placeholders

When using numbered placeholders like `%1$s`, use just the number in the comment:

```typescript
// ❌ WRONG - Using %1$s in comment
/* translators: %1$s: Provider name, %2$s: Country */

// ✅ CORRECT - Using just numbers
/* translators: 1: Provider name, 2: Country */
```

### Descriptive Comments

Always provide context for translators:

```typescript
// ❌ WRONG - No context
/* translators: %s: name */

// ✅ CORRECT - Clear context
/* translators: %s: Payment provider name (e.g., WooPayments) */
```

## Complex String Patterns

### Combining `sprintf`, `_n`, and `createInterpolateElement`

```typescript
installText: ( extensionsString: string ) => {
    const count = extensionsString.split( ', ' ).length;
    return createInterpolateElement(
        sprintf(
            /* translators: 1: Provider name, 2: Extension names */
            _n(
                'Installing <strong>%1$s</strong> activates <strong>%2$s</strong>.',
                'Installing <strong>%1$s</strong> activates <strong>%2$s</strong>.',
                count,
                'woocommerce'
            ),
            'WooPayments',
            extensionsString
        ),
        { strong: <strong /> }
    );
}
```

### Strings with Links

```typescript
createInterpolateElement(
    sprintf(
        /* translators: 1: Payment provider name */
        __(
            'Learn more about <a>%1$s</a> features.',
            'woocommerce'
        ),
        'WooPayments'
    ),
    {
        a: (
            <a
                href="https://example.com"
                target="_blank"
                rel="noopener noreferrer"
            />
        ),
    }
)
```

## Common Pitfalls

### Curly Apostrophes

TypeScript files may use curly apostrophes (`'` U+2019) instead of straight
apostrophes (`'` U+0027). When editing, preserve the original character:

```typescript
// Original uses curly apostrophe - preserve it
__( 'I don't want to install another plugin', 'woocommerce' )
//       ^ This is U+2019, not U+0027
```

### ESLint i18n Rules

The `@wordpress/i18n-translator-comments` rule requires comments directly
before the translation function:

```typescript
// ❌ ESLint error - comment not adjacent to __()
const title = sprintf(
    /* translators: %s: Provider name */

    __( 'Set up %s', 'woocommerce' ),
    'WooPayments'
);

// ✅ Correct - comment directly before __()
const title = sprintf(
    /* translators: %s: Provider name */
    __( 'Set up %s', 'woocommerce' ),
    'WooPayments'
);
```

### Brand Names

Always use placeholders for brand names to improve translation flexibility:

```typescript
// ✅ CORRECT - Use placeholder for brand name
title: sprintf(
    /* translators: %s: Payment provider name */
    __( 'Get paid with %s', 'woocommerce' ),
    'WooPayments'
)

// ✅ CORRECT - Use placeholder in descriptions
description: sprintf(
    /* translators: %s: Payment provider name */
    __( 'Enable PayPal alongside %s', 'woocommerce' ),
    'WooPayments'
)

// ❌ WRONG - Hardcoded brand name
title: __( 'Get paid with WooPayments', 'woocommerce' )
```

## Quick Command Reference

```bash
# Lint specific file
npx eslint client/path/to/file.tsx

# Fix specific file
npx eslint --fix client/path/to/file.tsx

# Type check
pnpm run ts:check

# ❌ NEVER lint entire codebase
pnpm run lint  # NO - lints everything
```

## Summary

| Pattern                       | Example                                      |
|-------------------------------|----------------------------------------------|
| **Single placeholder**        | `sprintf( __( 'Set up %s' ), 'Name' )`       |
| **Repeated placeholder**      | `sprintf( __( '%1$s via %1$s' ), 'Name' )`   |
| **Multiple placeholders**     | `sprintf( __( '%1$s in %2$s' ), 'A', 'B' )`  |
| **Comment format (simple)**   | `/* translators: %s: Provider name */`       |
| **Comment format (numbered)** | `/* translators: 1: Provider, 2: Country */` |
