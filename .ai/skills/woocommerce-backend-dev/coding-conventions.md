# General Coding Conventions

## Table of Contents

- [Code Clarity and Comments](#code-clarity-and-comments)
- [WordPress Coding Standards](#wordpress-coding-standards)
- [Null Coalescing Operator](#null-coalescing-operator)
- [Ternary Operator](#ternary-operator)
- [call_user_func_array() Usage](#call_user_func_array-usage)
- [Linting](#linting)

## Code Clarity and Comments

Write self-explanatory code. Use comments sparingly - only for non-obvious insights.

**Good - Code explains itself:**

```php
if ( $order->is_draft() && $user->can_delete_drafts() ) {
    $order->delete();
}
```

**Avoid - Over-commented:**

```php
// Check if order is draft
if ( $order->is_draft() ) {
    // Check if user has permission
    if ( $user->can_delete_drafts() ) {
        // Delete the order
        $order->delete();
    }
}
```

**When to add comments:**

- Unusual decisions, workarounds, or non-obvious business logic
- Performance considerations

**When NOT to add comments:**

- Explaining what code does (code should be self-explanatory)
- Restating the obvious

## WordPress Coding Standards

Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

- **Yoda conditions**: `'true' === $value` not `$value === 'true'`
- **Spacing**: Spaces around operators, inside parentheses
- **Braces**: Opening on same line, closing on new line
- **Naming**: snake_case for functions and variables

## Null Coalescing Operator

Use `??` instead of `isset` checks for array access.

**Good:**

```php
if ( 34 === ( $foo['bar'] ?? null ) ) {
    // ...
}

$value = $options['setting'] ?? 'default';
```

**Avoid:**

```php
if ( isset( $foo['bar'] ) && 34 === $foo['bar'] ) {
    // ...
}

if ( isset( $options['setting'] ) ) {
    $value = $options['setting'];
} else {
    $value = 'default';
}
```

## Ternary Operator

Prefer the ternary operator over if-else statements for simple conditional returns or assignments, except for very complex cases.

**Good:**

```php
return $condition ? $true_value : $false_value;

$result = $is_enabled ? 'enabled' : 'disabled';

$price = $has_discount ? $product->get_sale_price() : $product->get_regular_price();
```

**Avoid:**

```php
if ( $condition ) {
    return $true_value;
}
return $false_value;

if ( $is_enabled ) {
    $result = 'enabled';
} else {
    $result = 'disabled';
}
```

**Exception:** Use traditional if-else for complex conditions or when multiple statements are needed in each branch.

```php
// OK to use if-else when complex logic is involved
if ( $order->needs_shipping() && ! $order->has_valid_address() ) {
    $this->logger->log( 'Invalid shipping address' );
    $this->notify_customer( $order );
    return false;
} else {
    return true;
}
```

## call_user_func_array() Usage

When using `call_user_func_array()`, always use **positional arguments** (numerically indexed array) instead of named/associative arrays for the arguments parameter.

The function uses array values in order and ignores keys, so named keys are misleading.

**Correct:**

```php
call_user_func_array( array( $obj, 'method' ), array( $code ) );
call_user_func_array( array( $obj, 'process' ), array( $id, $status, $data ) );
```

**Wrong:**

```php
call_user_func_array( array( $obj, 'method' ), array( 'country_code' => $code ) );
call_user_func_array( array( $obj, 'process' ), array( 'order_id' => $id, 'status' => $status ) );
```

## Linting

Only fix linting errors for code that has been added or modified in the branch you are working on.

Do not fix linting errors in unrelated code unless specifically asked to do so.
