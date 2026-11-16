---
post_title: Order fulfillments hooks
sidebar_label: Hooks
---

# Order fulfillments hooks

WooCommerce exposes lifecycle hooks, status filters, provider filters, notification actions, email template hooks, and CSV importer hooks for order fulfillments. These hooks let extensions validate fulfillment data, synchronize external systems, customize tracking behavior, adjust merchant or customer-facing output, accept header names from warehouse exports, and react when a staged import file is cleaned up.

## Data lifecycle filters

These filters run before WooCommerce persists fulfillment changes. Each filter receives the `Fulfillment` object and must return it (optionally modified). To **cancel the operation** — for example, to enforce a validation rule — throw a `FulfillmentException` inside the callback. The exception message is surfaced to the caller as an error response.

| Hook | Purpose |
| --- | --- |
| `woocommerce_fulfillment_before_create` | Modify a `Fulfillment` object before it is inserted into the database. Throw a `FulfillmentException` to block creation. |
| `woocommerce_fulfillment_before_fulfill` | Modify a `Fulfillment` object immediately before it transitions into a fulfilled state during create or update operations. |
| `woocommerce_fulfillment_before_update` | Modify a `Fulfillment` object before changes are written to the database. Throw a `FulfillmentException` to block the update. |
| `woocommerce_fulfillment_before_delete` | Modify a `Fulfillment` object before WooCommerce soft-deletes it. Throw a `FulfillmentException` to block deletion. |

```php
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentException;

// Require a tracking number before a fulfillment can be created in fulfilled status.
add_filter( 'woocommerce_fulfillment_before_create', function( $fulfillment ) {
    if ( $fulfillment->get_is_fulfilled() && ! $fulfillment->get_tracking_number() ) {
        throw new FulfillmentException(
            __( 'A tracking number is required to create a fulfilled fulfillment.', 'my-plugin' )
        );
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
| `woocommerce_fulfillments_auto_fulfill_products` | Return an array of product or variation IDs to fulfill automatically. Receives the IDs array and the `WC_Order` object. |
| `woocommerce_fulfillment_order_fulfillment_status_text` | Customize the display label for an order-level fulfillment state. |
| `woocommerce_fulfillment_shipping_providers` | Add or modify shipping providers available to the feature. |
| `woocommerce_fulfillment_shipment_provider_label` | Customize the provider label used in fulfillment emails, order notes, and the orders list column, after WooCommerce resolves it from the provider list or from fulfillment metadata. Receives the label and the `Fulfillment` object. A value that cannot be rendered as text is ignored. |
| `woocommerce_rest_prepare_fulfillments_providers` | Add or modify the provider list returned by the fulfillments providers REST endpoint. Receives the providers array and the `WP_REST_Request`. Entries missing `label`, `icon`, `value`, or `url` are dropped. |
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
| `woocommerce_get_email_fulfillment_items_table` | `string $html`, `WC_Order $order`, `Fulfillment $fulfillment` | Filter and return the rendered fulfillment items table HTML. |

## Privacy filters

When an order is anonymized, through the WordPress personal-data eraser or the automatic retention cleanup, WooCommerce clears the personal data stored on its fulfillments. This also runs on a store where the fulfillments feature has since been disabled, as long as its tables were created while it was on, since any tracking data they hold is still stored.

| Hook | Parameters | Purpose |
| --- | --- | --- |
| `woocommerce_privacy_remove_fulfillment_personal_data_meta` | `string[] $meta_keys`, `WC_Order $order` | Add or remove the fulfillment meta keys cleared during erasure. Defaults to `_tracking_number` and `_tracking_url`. |

```php
add_filter( 'woocommerce_privacy_remove_fulfillment_personal_data_meta', function( $meta_keys, $order ) {
    // Redact a shipping label URL stored by this extension.
    $meta_keys[] = '_shipping_label_url';

    return $meta_keys;
}, 10, 2 );
```

## CSV importer filters

The fulfillments CSV importer recognizes five canonical columns. This filter lets a store accept the header names used by its warehouse or 3PL exports when the importer auto-detects the column mapping.

Aliases are normalized before matching (lowercase, with runs of non-alphanumeric characters replaced by `_`), so `PO Number` and `po_number` are the same alias. Keys that are not one of the five canonical columns are ignored, non-string aliases are dropped, and if nothing usable is left the default aliases are used.

| Hook | Purpose |
| --- | --- |
| `woocommerce_fulfillments_csv_importer_column_aliases` | Change the accepted header names for `order_number`, `tracking_number`, `shipment_provider`, `tracking_url`, and `items`. Receives the alias map and returns the modified map. |

```php
add_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', function( $aliases ) {
    $aliases['order_number'][]      = 'PO Number';
    $aliases['shipment_provider'][] = 'Ship Via';

    return $aliases;
} );
```

## CSV importer actions

The import wizard stages the uploaded CSV and schedules its cleanup through Action Scheduler. The action fires after the import session's lifetime (one hour plus a five-minute grace period) so the staged file is removed when the wizard never finished. It also fires while the session is still live, because a later write to the session extends its lifetime after the action was scheduled. The default handler, `ImportSession::handle_cleanup_hook()`, then leaves the file alone and schedules the action again; once the session has expired it deletes the file and its attachment post. Listeners that treat the session as abandoned must check it first: `ImportSession::load( $user_id, $token )` returns `null` once the session is gone. The action is only scheduled while the `fulfillments` feature is enabled, but the handler is attached regardless of the flag so a pending cleanup still runs after the feature is turned off.

| Hook | Parameters | Purpose |
| --- | --- | --- |
| `woocommerce_fulfillments_import_session_cleanup` | `int $user_id`, `string $token`, `string $file`, `int $attachment_id` | React when an abandoned import upload is cleaned up, for example to log it or to remove data stored alongside the session. Skip live sessions, or a running import loses that data. `$file` is the absolute path of the staged CSV and `$attachment_id` is the attachment post created for it, or `0`. |

```php
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\ImportSession;

add_action( 'woocommerce_fulfillments_import_session_cleanup', 'log_abandoned_import', 10, 4 );

function log_abandoned_import( $user_id, $token, $file, $attachment_id ) {
    // Action Scheduler stores the arguments, so cast them before calling a typed method.
    if ( ImportSession::load( (int) $user_id, (string) $token ) ) {
        return; // Still live: the default handler reschedules the cleanup.
    }
    error_log( sprintf( 'Import session %s of user %d was abandoned', $token, $user_id ) );
}
```
