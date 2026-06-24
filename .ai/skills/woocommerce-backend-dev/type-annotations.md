# Type Annotations for Static Analysis

## Table of Contents

- [Overview](#overview)
- [When to Use PHPStan Annotations](#when-to-use-phpstan-annotations)
- [Generic Types with @template](#generic-types-with-template)
- [PHPStan-Specific Annotations](#phpstan-specific-annotations)
- [Common Patterns](#common-patterns)
- [Suppressing False Positives](#suppressing-false-positives)

## Overview

WooCommerce uses PHPStan for static analysis. Beyond standard PHPDoc annotations (`@param`, `@return`, `@var`), use PHPStan-specific annotations to provide richer type information that enables better type inference.

## When to Use PHPStan Annotations

Use PHPStan annotations when:

- A method returns a type based on its input (generic/template types)
- Standard PHPDoc cannot express the type relationship
- You need to provide type information that PHP's type system cannot express

## Generic Types with @template

Use `@template` to declare generic type parameters. This enables PHPStan to infer return types based on input types.

### Basic Pattern

```php
/**
 * Get an instance of a class from the container.
 *
 * @template T of object
 * @param string $class_name Class name.
 * @phpstan-param class-string<T> $class_name
 *
 * @return T The instance of the requested class.
 */
public function get( string $class_name ) {
    // ...
}
```

**How it works:**

1. `@template T of object` - Declares a type variable `T` constrained to objects
2. `@phpstan-param class-string<T> $class_name` - The input is a class name string for type `T`
3. `@return T` - The return type is the same `T` that was passed in

**Result:** PHPStan knows that `$container->get( MyService::class )` returns `MyService`.

### Constraint Options

```php
// Any object type
@template T of object

// Specific base class or interface
@template T of WC_Product

// No constraint (can be any type including scalars)
@template T
```

## PHPStan-Specific Annotations

### @phpstan-param vs @param

Use both when you need PHPStan-specific type info while keeping standard documentation:

```php
/**
 * @param string $class_name Class name to instantiate.
 * @phpstan-param class-string<T> $class_name
 */
```

- `@param string` - Standard PHPDoc (for IDEs and documentation generators)
- `@phpstan-param class-string<T>` - PHPStan-specific (richer type info)

### @phpstan-return

Use when the return type is more specific than the declared type:

```php
/**
 * @return object
 * @phpstan-return T
 */
```

### @phpstan-var

Use for inline type assertions:

```php
/** @phpstan-var array<string, WC_Product> $products */
$products = get_transient( 'cached_products' );
```

## Common Patterns

### Factory Methods

```php
/**
 * Create a new instance of a data store.
 *
 * @template T of WC_Data_Store
 * @param string $object_type Object type (e.g., 'product', 'order').
 * @phpstan-param class-string<T> $object_type
 *
 * @return T The data store instance.
 */
public static function load( string $object_type ) {
    // ...
}
```

### Container/Service Locator

```php
/**
 * @template T of object
 * @param string $id Service identifier.
 * @phpstan-param class-string<T> $id
 *
 * @return T Service instance.
 */
public function get( string $id );
```

### Collections with Known Types

```php
/**
 * @param array<int, WC_Order_Item> $items Order items.
 * @return array<string, float> Item totals keyed by item type.
 */
public function calculate_totals( array $items ): array {
    // ...
}
```

## Suppressing False Positives

When PHPStan reports an error that is a false positive (the code is correct but PHPStan cannot verify it), use inline ignores with explanations:

```php
// @phpstan-ignore return.type (method uses reflection to return correct type at runtime)
return $this->create_instance( $class_name );
```

Common ignore identifiers:

- `return.type` - Return type mismatch
- `argument.type` - Argument type mismatch
- `method.nonObject` - Method call on potentially non-object
- `nullCoalesce.property` - `??` on a property typed as non-nullable

**Important:** Only use ignores when the code is genuinely correct. Prefer fixing the type annotations or code when possible.

## NEVER Delete Code to Silence a Warning

When a PHPStan warning points at a specific expression, the fix is **never** to delete the expression as a shortcut. Always:

1. **Understand the warning.** What is PHPStan claiming about the code?
2. **Verify the claim.** Is the type annotation misleading PHPStan, or is the code genuinely unsafe?
3. **Fix the root cause:**
   - Wrong `@var` / `@param` / `@return`? Fix the annotation.
   - Unsafe code (e.g. method call on possibly-null)? Add a guard, narrow with `instanceof`, or assert.
   - PHPStan is genuinely mistaken? Use `@phpstan-ignore-next-line <identifier>` with a comment explaining *why* PHPStan is wrong.
4. **Verify the warning is gone and tests still pass.**

**Forbidden shortcut: removing the offending line.** This often silently breaks runtime behaviour that tests don't cover — especially with null-coalescing fallbacks (`??`), defensive guards, and "redundant"-looking session/cache reads.

### Real example (PR #64155 → issue #64792)

A PHPStan `nullCoalesce.property` warning was reported on:

```php
$this->order = $this->order ?? $this->get_draft_order();
```

The PR "fixed" the warning by deleting the line. Tests passed. But the line was the **load-bearing failed-payment retry fallback** — without it, every retry POST created a duplicate order. The actual root cause was that `$this->order` was declared `@var \WC_Order` (non-null) but initialised to `null`, so PHPStan thought the `??` was unnecessary.

**Correct fix: make the type honest, then handle null at the points that read the property.**

```php
// 1. Fix the property docblock to match reality.
/**
 * @var \WC_Order|null
 */
private $order = null;

// 2. Restore the load-bearing fallback. With the docblock correct, no warning.
$this->order = $this->order ?? $this->get_draft_order();

// 3. Declare the post-condition on the method that materialises the value, so
//    PHPStan can narrow `$this->order` in the *direct caller's* scope.
/**
 * @phpstan-assert \WC_Order $this->order
 */
private function create_or_update_draft_order( \WP_REST_Request $request ) {
    // ... method already throws if `$this->order` ends up null ...
}

// 4. For helper methods that read `$this->order` but live in their own method
//    scope (so they don't benefit from a caller-side assertion), extract a
//    small helper that throws if null and returns the narrowed order. Callers
//    use the returned local variable rather than `$this->order` directly. Do
//    not change method signatures to thread the order through.
private function get_order_or_throw(): \WC_Order {
    if ( ! $this->order instanceof \WC_Order ) {
        throw new RouteException(
            'woocommerce_rest_checkout_missing_order',
            esc_html__( 'Unable to create order', 'woocommerce' ),
            500
        );
    }
    return $this->order;
}

private function process_payment( \WP_REST_Request $request, PaymentResult $payment_result ) {
    $order = $this->get_order_or_throw();
    // ... rest of method uses $order, which PHPStan knows is non-null ...
}
```

The return-value pattern (rather than `@phpstan-assert \WC_Order $this->order` on the helper) is the safer choice when the helper lives in a trait used by multiple classes whose `$this->order` property may be typed differently. `@phpstan-assert` can fail with `assert.alreadyNarrowedType` in a consuming class whose property is already non-null per its (possibly inaccurate) docblock.

Fixing the type honestly often surfaces other latent type-unsafety. Resolve it inside each affected method (null-check + throw, then narrowing), not by changing method signatures to thread the value through. Do not add the new errors to `phpstan-baseline.neon` — the baseline must only shrink.

A single-line `@phpstan-ignore-next-line` with an explanatory comment is acceptable only when PHPStan is genuinely mistaken about a correct piece of code (e.g. it can't see through a runtime invariant). It is *not* a substitute for fixing a wrong type annotation.

### Sanity-check questions before deleting any line to fix a warning

Answer all three out loud:

1. What does this line do at runtime?
2. In what scenario does it matter (success path, failure path, retry path, edge case)?
3. Which tests cover that scenario?

If any answer is "I don't know," **do not delete the line.** Add an inline suppression with a comment, or fix the underlying annotation.
