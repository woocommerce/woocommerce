---
post_title: Extending back in stock notifications
sidebar_label: Developer reference
sidebar_position: 2
---

# Extending back in stock notifications

This document is the developer reference for the Back in stock notifications feature. It covers the alpha gate, the public PHP surface, the hooks and filters the feature exposes, and the templates you can override from a theme or extension.

For a merchant-oriented overview of the feature, see the [user guide](./user-guide.md).

## Alpha gate

The feature is loaded from a single entry point — `Automattic\WooCommerce\Internal\StockNotifications\StockNotifications` — which is only pulled out of the container when the `WOOCOMMERCE_BIS_ALPHA_ENABLED` constant is truthy.

Defined in `plugins/woocommerce/includes/class-woocommerce.php`:

```php
if ( Constants::is_true( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' ) ) {
    $container->get( StockNotifications::class );
}
```

Set the constant in `wp-config.php` (or via a `mu-plugin` if you need to gate it by environment):

```php
define( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );
```

When the constant is not set, none of the classes, templates, hooks, admin screens, or emails are registered. The constant is temporary and will be replaced with a proper feature flag before general availability.

## Namespace and classes

All feature code lives under `Automattic\WooCommerce\Internal\StockNotifications`. The classes in that namespace are marked `Internal` and are not part of WooCommerce's long-term public API contract; they are, however, the supported surface while the feature is in alpha.

The following classes are the primary integration points you can rely on while the feature ships.

### `Notification`

A `WC_Data` subclass that represents a single customer sign-up. Construct, read, update, save, and delete notifications the same way you would a `WC_Order` or `WC_Product`.

```php
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

$notification = new Notification();
$notification->set_product_id( $product_id );
$notification->set_user_email( 'customer@example.com' );
$notification->set_status( NotificationStatus::ACTIVE );
$result = $notification->save(); // int ID on success, WP_Error on failure.
```

Relevant getters and setters:

- `get_product_id()` / `set_product_id( int $product_id )`
- `get_user_id()` / `set_user_id( int $user_id )`
- `get_user_email()` / `set_user_email( string $user_email )`
- `get_status()` / `set_status( string $status )`
- `get_cancellation_source()` / `set_cancellation_source( ?string $source )`
- `get_date_created()`, `get_date_confirmed()`, `get_date_notified()`, `get_date_last_attempt()`, `get_date_cancelled()` — all return `WC_DateTime|null`.
- `get_product()` — returns the related `WC_Product` or `false`.

`save()` runs `validate_props()`, which requires a product ID plus either a user ID or a valid user email. Validation failures surface as a `WP_Error`, not an exception.

### `NotificationQuery`

Static helpers that wrap the `stock_notification` data store. Use these instead of hitting the database directly.

```php
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

$active = NotificationQuery::get_notifications(
    array(
        'product_id' => $product_id,
        'status'     => NotificationStatus::ACTIVE,
    )
);

$has_signups     = NotificationQuery::product_has_active_notifications( array( $product_id ) );
$email_signed_up = NotificationQuery::notification_exists_by_email( $product_id, $email );
$user_signed_up  = NotificationQuery::notification_exists_by_user_id( $product_id, $user_id );
```

`get_notifications( array $args )` accepts the same query arguments as the underlying data store: `product_id`, `user_id`, `user_email`, `status`, `limit`, `offset`, `orderby`, `order`, plus per-field date filters. It returns an array of `Notification` objects.

### `Enums\NotificationStatus`

String constants for the four sign-up states. Use these instead of hard-coding strings.

- `NotificationStatus::PENDING`
- `NotificationStatus::ACTIVE`
- `NotificationStatus::SENT`
- `NotificationStatus::CANCELLED`
- `NotificationStatus::get_valid_statuses()` returns them all.

### `Enums\NotificationCancellationSource`

String constants for the actor that cancelled a sign-up. Written to `cancellation_source` when `set_status( CANCELLED )` is used.

- `NotificationCancellationSource::ADMIN`
- `NotificationCancellationSource::USER`
- `NotificationCancellationSource::SYSTEM`

### `Config`

Static accessor for merchant-controlled settings and the handful of runtime thresholds that the feature exposes.

```php
use Automattic\WooCommerce\Internal\StockNotifications\Config;

if ( Config::allows_signups() && Config::requires_double_opt_in() ) {
    // ...
}

$supported_types = Config::get_supported_product_types();
```

Notable methods:

- `allows_signups()`, `requires_double_opt_in()`, `requires_account()`, `creates_account_on_signup()` — boolean reads of the merchant settings.
- `get_supported_product_types()`, `get_supported_product_statuses()`, `get_eligible_stock_statuses()` — arrays of type/status strings used when deciding whether the sign-up form should be shown.
- `get_verification_expiration_time_threshold()` — verification-link lifetime, in seconds.
- `get_unverified_deletion_days_threshold()` — number of days before unverified sign-ups are deleted (`0` means keep forever).
- `get_product_signups_meta_key()` — name of the per-product meta key used to opt a product out of sign-ups.

## Hooks and filters

All hooks are introduced in version 10.2.0 unless otherwise noted.

### Sign-up and eligibility

#### `woocommerce_customer_stock_notifications_product_is_valid`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(bool $is_valid, WC_Product $product)`
- **Returns:** `bool`

Extend or override the eligibility check. Return `false` to stop a product from accepting sign-ups even when type, status, and stock rules would otherwise allow it.

#### `woocommerce_customer_stock_notifications_supported_product_types`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(array $product_types)`
- **Returns:** `array`

Product types that the feature considers supported. Defaults to `simple`, `variable`, and `variation`.

#### `woocommerce_customer_stock_notifications_supported_product_statuses`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(array $product_statuses)`
- **Returns:** `array`

Product statuses eligible for sign-ups. Defaults to `publish`.

#### `woocommerce_customer_stock_notifications_supported_stock_statuses`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(array $stock_statuses)`
- **Returns:** `array`

Stock statuses that trigger notification dispatch. Defaults to `instock` and `onbackorder`.

#### `woocommerce_customer_stock_notifications_verification_expiration_time_threshold`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(int $threshold)`
- **Returns:** `int`

Lifetime of a verification link, in seconds. Default: `HOUR_IN_SECONDS`.

#### `woocommerce_customer_stock_notifications_signup`

- **Type:** Action
- **Since:** 10.2.0
- **Parameters:** `(Notification $notification)`

Fires every time a customer successfully signs up or re-activates a pending sign-up. Useful for analytics or external CRM mirroring.

### Front-end rendering

#### `woocommerce_customer_stock_notifications_personalization_enabled`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(bool $enabled)`
- **Returns:** `bool`

Enables per-customer personalisation of the sign-up form (for example, showing an "already signed up" message). Off by default because it introduces a per-request lookup and can interact with page caching.

#### `woocommerce_customer_stock_notifications_account_required_message_html`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string|null $pre_html, WC_Product $product)`
- **Returns:** `string|null`

Replace the HTML shown when guests need to log in before signing up. Return a non-null string to short-circuit the default notice.

#### `woocommerce_customer_stock_notifications_already_signed_up_message_html`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string|null $pre_html, WC_Product $product, Notification $notification)`
- **Returns:** `string|null`

Replace the HTML shown when the current customer already has an active sign-up for this product.

#### `woocommerce_customer_stock_notifications_requires_nonce_check`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(bool $requires_nonce_check)`
- **Returns:** `bool`

Forces nonce validation on front-end form submission. Defaults to `true` when the form is personalised and an account is required. Override with care — disabling nonce checks on anonymous forms is generally fine, enabling them on cached pages can break sign-ups.

### Dispatch and background processing

#### `woocommerce_customer_stock_notifications_product_sync`

- **Type:** Action
- **Since:** 10.2.0
- **Parameters:** `(array $product_ids)`

Fires after back-in-stock products have been queued for processing. Hook in to trigger secondary systems (search index refreshes, external inventory mirrors, etc.).

#### `woocommerce_customer_stock_notifications_first_batch_delay`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(int $delay_seconds, int $product_id)`
- **Returns:** `int`

Delay (seconds) between queuing and running the first batch of emails for a product. Default: `MINUTE_IN_SECONDS`. Keep a small delay so that multiple rapid stock changes collapse into a single run.

#### `woocommerce_customer_stock_notifications_next_batch_delay`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(int $delay_seconds, int $product_id)`
- **Returns:** `int`

Delay between consecutive batches. Default: `0` (schedule immediately).

#### `woocommerce_customer_stock_notifications_batch_size`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(int $batch_size)`
- **Returns:** `int`

Number of notifications processed per Action Scheduler run. Raise on fast hosting; lower if you hit time-outs on shared hosts.

#### `woocommerce_customer_stock_notification_throttle_threshold`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(int $threshold_seconds)`
- **Returns:** `int`

Minimum seconds between repeat sends for the same customer/product. Set to `0` to disable throttling.

#### `woocommerce_customer_stock_notification_should_skip_sending`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(bool $should_skip, int $notification_id)`
- **Returns:** `bool`

Per-notification veto applied just before dispatch. Return `true` to skip sending a specific notification while leaving its status untouched.

### Email-link redirects

#### `woocommerce_customer_stock_notification_verified_redirect_url`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string $url)`
- **Returns:** `string`

URL the customer lands on after clicking the verification link. Defaults to the shop page.

#### `woocommerce_customer_stock_notification_unsubscribe_redirect_url`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string $url)`
- **Returns:** `string`

URL the customer lands on after clicking the unsubscribe link. Defaults to the shop page.

### Email rendering

The three customer emails (`customer_stock_notification`, `customer_stock_notification_verify`, `customer_stock_notification_verified`) are standard `WC_Email` classes and respect WooCommerce's existing email hooks and settings. The filters below let you adjust copy and styling without replacing the template.

#### `woocommerce_email_stock_notification_intro_content`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string $content, Notification $notification, WC_Email $email)`
- **Returns:** `string`

Intro paragraph rendered at the top of every back-in-stock email. The same filter name is fired from all three email classes — inspect the `$email->id` argument if you only want to target one of them.

#### `woocommerce_email_stock_notification_button_text`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string $button_text, Notification $notification, WC_Product $product)`
- **Returns:** `string`

Call-to-action label on the main notification email. Default: `Shop Now`.

#### `woocommerce_email_stock_notification_button_link`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string $url, Notification $notification, WC_Product $product)`
- **Returns:** `string`

Destination of the call-to-action button. Default: the product permalink decorated with UTM parameters.

#### `woocommerce_email_stock_notification_verify_button_text`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string $button_text, Notification $notification, WC_Product $product)`
- **Returns:** `string`

Label on the verification button in the double opt-in email. Default: `Confirm`.

#### `woocommerce_email_stock_notification_emails_to_style`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(array $email_ids)`
- **Returns:** `array`

IDs of emails that get the dedicated back-in-stock stylesheet. Extend this array if you ship a sibling email that should share the same visuals.

#### `woocommerce_email_stock_notification_base_text_color`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(string $color, WC_Email|null $email)`
- **Returns:** `string`

Text colour used when rendering on top of the email base colour. Defaults to a contrast-aware choice via `wc_light_or_dark()`.

### Settings

#### `woocommerce_customer_stock_notifications_settings`

- **Type:** Filter
- **Since:** 10.2.0
- **Parameters:** `(array $settings)`
- **Returns:** `array`

Add, remove, or reorder fields on the **WooCommerce > Settings > Products > Customer stock notifications** settings screen. The array uses the standard WooCommerce settings-array format.

## Template overrides

The feature ships three customer email templates and one front-end form template. All use the standard `wc_get_template()` lookup, so themes can override them by copying the file into `wp-content/themes/your-theme/woocommerce/`.

- `templates/single-product/back-in-stock-form.php` — sign-up form shown beneath an out-of-stock product.
- `templates/emails/customer-stock-notification.php` (plus `emails/plain/…`) — the back-in-stock notification email.
- `templates/emails/customer-stock-notification-verify.php` (plus `emails/plain/…`) — the verification email sent when double opt-in is enabled.
- `templates/emails/customer-stock-notification-verified.php` (plus `emails/plain/…`) — confirmation email after the customer verifies.

When the form HTML is not enough, prefer one of the `*_message_html` filters above, or replace the template entirely — do not modify the shipped templates in place.

## Options

The feature stores its configuration in these `wp_options` rows. Read them through `Config` rather than directly — `Config` centralises the defaults and caches lookups where it matters.

- `woocommerce_customer_stock_notifications_allow_signups` (`yes|no`)
- `woocommerce_customer_stock_notifications_require_double_opt_in` (`yes|no`)
- `woocommerce_customer_stock_notifications_require_account` (`yes|no`)
- `woocommerce_customer_stock_notifications_create_account_on_signup` (`yes|no`)
- `woocommerce_customer_stock_notifications_unverified_deletions_days_threshold` (`int`, days; `0` means keep indefinitely)

Per-product opt-out is stored on the product under the meta key returned by `Config::get_product_signups_meta_key()` (currently `customer_stock_notifications_enable_signups`).

## Storage

Sign-ups are stored in two custom tables:

- `wp_wc_stock_notifications` — one row per sign-up.
- `wp_wc_stock_notificationmeta` — attached meta rows (for example, the verification and unsubscribe hashes).

Do not read or write to these tables directly. Use `Notification` and `NotificationQuery` so schema changes remain invisible to your code.

## Testing your integration

When developing against the feature, enable the alpha gate in the test environment and make sure the test bootstrap installs the feature's tables by calling `WC_Install::install()`. The feature's own PHPUnit tests live under `plugins/woocommerce/tests/php/src/Internal/StockNotifications/` and are a good reference for wiring hooks into unit tests.
