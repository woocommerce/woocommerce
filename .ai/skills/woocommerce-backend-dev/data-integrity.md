# Data Integrity Guidelines

## Table of Contents

- [Preventing Accidental Data Loss](#preventing-accidental-data-loss)
    - [Validation Before Deletion](#validation-before-deletion)
    - [Consider Race Conditions](#consider-race-conditions)
    - [Session-Based Operations](#session-based-operations)
- [When in Doubt, Ask](#when-in-doubt-ask)
- [Security Checklist for Data Operations](#security-checklist-for-data-operations)
- [Common Pitfalls to Avoid](#common-pitfalls-to-avoid)

## Preventing Accidental Data Loss

Always verify entity state before deletion/modification to prevent accidental data loss.

### Validation Before Deletion

Always verify the state of the entity before deleting or modifying it.

#### Example: Deleting a draft order

```php
// GOOD: Verify order status before deletion
public function delete_draft_order( int $order_id ) {
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return false;
    }

    // Verify it's actually a draft or checkout-draft
    if ( ! in_array( $order->get_status(), array( 'draft', 'checkout-draft' ), true ) ) {
        throw new \Exception( 'Cannot delete non-draft order' );
    }

    return $order->delete( true );
}

// BAD: No verification
public function delete_draft_order( int $order_id ) {
    $order = wc_get_order( $order_id );
    return $order->delete( true );  // Could delete any order!
}
```

### Consider Race Conditions

Think about whether race conditions could occur that might affect the wrong data.

#### Example: User-specific data deletion

```php
// GOOD: Verify ownership before deletion
public function delete_user_cart_item( int $item_id, int $user_id ) {
    $item = $this->get_cart_item( $item_id );

    if ( ! $item ) {
        return false;
    }

    // Prevent race condition: verify item belongs to this user
    if ( (int) $item->get_user_id() !== $user_id ) {
        throw new \Exception( 'Cannot delete item belonging to another user' );
    }

    return $this->data_store->delete( $item_id );
}

// BAD: Race condition possible
public function delete_user_cart_item( int $item_id, int $user_id ) {
    // Another user could have taken this item in the meantime
    return $this->data_store->delete( $item_id );
}
```

### Session-Based Operations

For session-based operations (like cart or checkout), verify session ownership.

#### Example: Clearing checkout data

```php
// GOOD: Verify session ownership
public function clear_checkout_data( int $session_id ) {
    $current_session_id = WC()->session->get_customer_id();

    if ( $session_id !== $current_session_id ) {
        throw new \Exception( 'Cannot clear checkout data for another session' );
    }

    WC()->session->set( 'checkout_data', array() );
}
```

## When in Doubt, Ask

If unsure about data operations, ask for clarification about:

- Required state/ownership verifications
- Soft delete vs hard delete requirements
- Protected states that prevent deletion

## Security Checklist for Data Operations

Before implementing code that modifies or deletes data:

- [ ] Verify entity exists
- [ ] Verify entity state (status, type, etc.)
- [ ] Verify ownership (user_id, session_id, etc.)
- [ ] Check for race conditions
- [ ] Consider using soft delete (trash) instead of hard delete
- [ ] Add appropriate error handling
- [ ] Log sensitive operations for audit trail
- [ ] Add capability checks (`current_user_can()`)

## Common Pitfalls to Avoid

### 1. Trusting User Input

```php
// BAD
$order_id = $_POST['order_id'];
$order->delete( true );

// GOOD
$order_id = absint( $_POST['order_id'] );
if ( ! current_user_can( 'delete_order', $order_id ) ) {
    wp_die( 'Unauthorized' );
}
// ... additional validation ...
```

### 2. Batch Operations Without Verification

```php
// BAD: Deletes all without verification
foreach ( $order_ids as $order_id ) {
    wc_delete_order( $order_id );
}

// GOOD: Verify each item
foreach ( $order_ids as $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order && $order->get_status() === 'draft' ) {
        wc_delete_order( $order_id );
    }
}
```

### 3. Ignoring Return Values

```php
// BAD: Doesn't check if operation succeeded
$order->delete( true );
wp_send_json_success();

// GOOD: Check result
if ( $order->delete( true ) ) {
    wp_send_json_success();
} else {
    wp_send_json_error( 'Failed to delete order' );
}
```
