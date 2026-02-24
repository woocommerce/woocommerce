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

**Important:** Only use ignores when the code is genuinely correct. Prefer fixing the type annotations or code when possible.
