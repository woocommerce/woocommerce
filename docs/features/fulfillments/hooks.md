---
post_title: Order fulfillments hooks
sidebar_label: Hooks
---

# Order fulfillments hooks

WooCommerce exposes lifecycle hooks, status filters, provider filters, notification actions, and email template hooks for order fulfillments. These hooks let extensions validate fulfillment data, synchronize external systems, customize tracking behavior, and adjust merchant or customer-facing output.

## Data lifecycle filters

These filters run before WooCommerce persists fulfillment changes.

| Hook | Purpose |
| --- | --- |
| `woocommerce_fulfillment_before_create` | Modify a `Fulfillment` object before it is inserted into the database. |
| `woocommerce_fulfillment_before_fulfill` | Modify a `Fulfillment` object immediately before it transitions into a fulfilled state during create or update operations. |
| `woocommerce_fulfillment_before_update` | Modify a `Fulfillment` object before changes are written to the database. |
| `woocommerce_fulfillment_before_delete` | Modify a `Fulfillment` object before WooCommerce soft-deletes it. |

```php
add_filter( 'woocommerce_fulfillment_before_create', function( $fulfillment ) {
    if ( ! $fulfillment->get_tracking_number() ) {
        $fulfillment->set_status( 'unfulfilled' );
    }

    return $fulfillment;
} );
```

## Data lifecycle actions

These actions fire after WooCommerce saves or deletes the fulfillment.

| Hook | Parameters | Purpose |
| --- | --- | --- |
| `woocommerce_fulfillment_after_create` | `Fulfillment $fulfillment` | React after a fulfillment is created. |
| `woocommerce_fulfillment_after_fulfill` | `Fulfillment $fulfillment` | React when a fulfillment becomes fulfilled. |
| `woocommerce_fulfillment_after_update` | `Fulfillment $fulfillment`, `array $changes`, `string $previous_status` | React after an update and inspect the changed core fields or metadata. |
| `woocommerce_fulfillment_after_delete` | `Fulfillment $fulfillment` | React after a fulfillment is soft-deleted. |

```php
add_action( 'woocommerce_fulfillment_after_update', function( $fulfillment, $changes, $previous_status ) {
    $meta_changes = $changes['meta_data'] ?? array();

    if ( array_key_exists( '_tracking_number', $meta_changes ) ) {
        // Push the new tracking number to an external system.
    }

    if ( $previous_status !== $fulfillment->get_status() ) {
        // React to a status transition.
    }
}, 10, 3 );
```

## Notification actions

These actions back the built-in customer fulfillment emails and any additional notification channel an extension wants to add.

| Hook | Parameters | Purpose |
| --- | --- | --- |
| `woocommerce_fulfillment_created_notification` | `int $order_id`, `Fulfillment $fulfillment`, `WC_Order $order` | Notify the customer that a fulfilled fulfillment was created. |
| `woocommerce_fulfillment_updated_notification` | `int $order_id`, `Fulfillment $fulfillment`, `WC_Order $order` | Notify the customer that a fulfilled fulfillment was updated. |
| `woocommerce_fulfillment_deleted_notification` | `int $order_id`, `Fulfillment $fulfillment`, `WC_Order $order` | Notify the customer that a fulfilled fulfillment was deleted. |

```php
add_action( 'woocommerce_fulfillment_created_notification', function( $order_id, $fulfillment, $order ) {
    $phone = $order->get_billing_phone();

    if ( $phone && $fulfillment->get_tracking_number() ) {
        // Send an SMS in parallel with the built-in email.
    }
}, 10, 3 );
```

## Status, provider, and tracking filters

These filters change how WooCommerce calculates fulfillment state and resolves shipping providers.

| Hook | Purpose |
| --- | --- |
| `woocommerce_fulfillment_order_fulfillment_statuses` | Register or modify order-level fulfillment statuses. |
| `woocommerce_fulfillment_fulfillment_statuses` | Register or modify fulfillment record statuses. |
| `woocommerce_fulfillment_calculate_order_fulfillment_status` | Override how WooCommerce derives an order's fulfillment state from its fulfillment records. |
| `woocommerce_fulfillment_order_fulfillment_status_text` | Customize the display label for an order-level fulfillment state. |
| `woocommerce_fulfillment_shipping_providers` | Add or modify shipping providers available to the feature. |
| `woocommerce_fulfillment_parse_tracking_number` | Parse or normalize tracking numbers based on origin and destination countries. |
| `woocommerce_fulfillment_meta_key_translations` | Customize the translation map for fulfillment metadata labels. |
| `woocommerce_fulfillment_translate_meta_key` | Customize the label for one metadata key before it is rendered in emails or other UI. |

```php
add_filter( 'woocommerce_fulfillment_shipping_providers', function( $providers ) {
    // Option 1: Register a provider class name
    $providers['my-carrier'] = 'My_Carrier_Provider';

    // Option 2: Register a provider instance
    $providers['my-carrier-alt'] = new My_Carrier_Provider();

    return $providers;
} );
```

## Order note filters

WooCommerce writes order notes when fulfillments are created, updated, deleted, or when fulfillment-related status changes occur. These filters let extensions rewrite those note messages.

| Hook | Purpose |
| --- | --- |
| `woocommerce_fulfillment_created_order_note` | Customize the order note written after a fulfillment is created. |
| `woocommerce_fulfillment_updated_order_note` | Customize the order note written after a fulfillment is updated. |
| `woocommerce_fulfillment_deleted_order_note` | Customize the order note written after a fulfillment is deleted. |
| `woocommerce_fulfillment_order_status_changed_order_note` | Customize the note written when the order-level fulfillment status changes. |
| `woocommerce_fulfillment_status_changed_order_note` | Customize the note written when an individual fulfillment status changes. |

## Email template hooks

The fulfillment email templates expose the standard extension points below.

| Hook | Parameters | Purpose |
| --- | --- | --- |
| `woocommerce_email_fulfillment_details` | `WC_Order $order`, `Fulfillment $fulfillment`, `bool $sent_to_admin`, `bool $plain_text`, `WC_Email $email` | Render the fulfillment details block in the email. |
| `woocommerce_email_fulfillment_meta` | `WC_Order $order`, `Fulfillment $fulfillment`, `bool $sent_to_admin`, `bool $plain_text`, `WC_Email $email` | Render additional metadata inside the email. |
| `woocommerce_email_before_fulfillment_table` | `WC_Order $order`, `Fulfillment $fulfillment`, `bool $sent_to_admin`, `bool $plain_text`, `WC_Email $email` | Inject content before the fulfillment items table. |
| `woocommerce_email_after_fulfillment_table` | `WC_Order $order`, `Fulfillment $fulfillment`, `bool $sent_to_admin`, `bool $plain_text`, `WC_Email $email` | Inject content after the fulfillment items table. |
| `woocommerce_email_fulfillment_items_args` | Template arguments array | Customize how fulfillment line items are rendered in the email templates. |

## REST-specific filter

The v4 providers endpoint exposes one additional filter.

| Hook | Purpose |
| --- | --- |
| `woocommerce_rest_prepare_fulfillments_providers` | Filter the provider payload returned by `/wp-json/wc/v4/fulfillments/providers`. Each provider must return `label`, `icon`, `value`, and `url`. |