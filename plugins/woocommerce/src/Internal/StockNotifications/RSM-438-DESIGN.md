# RSM-438 — Wire verification + confirmation emails

Design doc for [RSM-438](https://linear.app/a8c/issue/RSM-438/wire-in-verification-and-confirmation-emails). Sprint scratch — delete before GA (see `SPRINT.md` pre-merge checklist).

## Problem

Three BIS email flows are half-wired:

1. **Verify email** (`CustomerStockNotificationVerifyEmail`) — class exists with `trigger()`, never dispatched.
2. **Verified/confirmation email** (`CustomerStockNotificationVerifiedEmail`) — same.
3. **Back-in-stock email** — dispatches correctly, but links miss the agreed UTM params for order attribution.

Plus two broken user-facing paths:

- Frontend "Resend verification" URL (`wc_bis_resend_notification` query param) — URL generator exists, no handler reads the param.
- Admin **Resend verification email** button (`NotificationEditPage.php:107`) — shows success notice, dispatches nothing. (CodeRabbit Must-fix #9, explicitly deferred in PR #64329.)

## Scope

In:

- A. Frontend resend URL handler.
- B. Reconcile the hook-name mismatch between `EmailManager::add_transactional_emails()` registrations and the actual `trigger()` hook names on the email classes (the registered actions are never fired; dispatch is done through direct `trigger()` calls instead).
- Verify + Verified email dispatch wiring.
- Admin Resend fix.
- UTM params on notification links.

Out (follow-ups):

- C. Guest-user account creation flow around `success_account_created_double_opt_in` — verify only, defer fixes.
- `is_email()` guard + batching in `PrivacyEraser::erase_notification_data()` — separate CodeRabbit Should-fix.

## Approach

**Dispatch pattern:** wrapper methods on `EmailManager` (matches existing `send_stock_notification_email()`). Drop the unused `add_transactional_emails()` registration rather than repair it — nothing in the codebase relies on it, and the wrapper pattern is more grep-able.

**Dispatch location:** inline in callers (`SignupService`, `EmailActionController`, `NotificationEditPage`, `NotificationManagementService`). Matches how `NotificationEditPage` already dispatches the stock notification email. Listener indirection would be YAGNI at alpha.

**DI:** `EmailManager` injected via `init()` method (matches the container pattern already in use by `NotificationsProcessor`, `SignupService`). `new EmailManager()` in `NotificationEditPage` replaced with the injected instance.

## Components

### `EmailManager`

```php
public function send_verify_email( Notification $notification ): void;
public function send_verified_email( Notification $notification ): void;
```

Each looks up the registered WC email class via `WC()->mailer()->get_emails()` and calls `->trigger( $notification )`. Remove `add_transactional_emails()` and the `woocommerce_email_actions` hook.

### `SignupService::init()`

Extend to receive `EmailManager`. After each `do_action( 'woocommerce_customer_stock_notifications_signup', $notification )` call (two sites), dispatch the verify email iff `Config::requires_double_opt_in()` **and** `$notification->get_status() === NotificationStatus::PENDING`.

### `EmailActionController`

Add `init( EmailManager $email_manager )`. In `process_verification_action()`, after `$notification->save()` sets status to `ACTIVE`, call `$this->email_manager->send_verified_email( $notification )` before the redirect.

### `NotificationEditPage`

Convert to DI (`init( EmailManager $email_manager )`), drop the `new EmailManager()` in `send_notification` case, and replace the no-op `send_verification_email` case with:

```php
case 'send_verification_email':
    if ( $notification->get_status() !== NotificationStatus::PENDING ) {
        NotificationsPage::add_notice( __( 'Cannot resend verification: this notification is already verified or cancelled.', 'woocommerce' ), 'error' );
        break;
    }
    $this->email_manager->send_verify_email( $notification );
    $notice_message = sprintf( /* translators: %s user email. */ __( 'Verification email sent to "%s".', 'woocommerce' ), $notification->get_user_email() );
    NotificationsPage::add_notice( $notice_message, 'success' );
    break;
```

### `NotificationManagementService`

Becomes an `init( EmailManager $email_manager )` service. On `init()`, register `template_redirect` listener `maybe_process_resend_request()`:

1. Return if `$_GET['wc_bis_resend_notification']` missing.
2. `wp_verify_nonce( $_GET['_wpnonce'], 'wc_bis_resend_verification_email_nonce' )`.
3. Load notification via `Factory::get_notification()`; bail with notice if not found or status !== `PENDING`.
4. Rate-limit: if `$notification->get_meta( '_last_verify_email_sent_at' )` is within 60s of `time()`, show notice ("Please wait before requesting another verification email.") and redirect.
5. `$this->email_manager->send_verify_email( $notification )`.
6. `$notification->update_meta_data( '_last_verify_email_sent_at', time() )` + `save()`.
7. `wc_add_notice( 'Verification email sent to …', 'success' )`; `wp_safe_redirect( $notification->get_product_permalink() )`.

Register the class in `StockNotifications::init_hooks()` via `$container->get( NotificationManagementService::class )` so its `template_redirect` handler is wired.

### UTM params

Small helper — prefer static utility so it's callable from email classes without a container round-trip:

```php
namespace Automattic\WooCommerce\Internal\StockNotifications\Utilities;

class UtmHelper {
    public static function add_email_utm_params( string $url, string $medium = 'email' ): string {
        return add_query_arg(
            array(
                'utm_source' => 'back-in-stock-notifications',
                'utm_medium' => $medium,
            ),
            $url
        );
    }
}
```

Applied in:

- `CustomerStockNotificationVerifyEmail::get_additional_template_args()` — `verification_link`.
- `CustomerStockNotificationVerifiedEmail::get_additional_template_args()` — unsubscribe link.
- `CustomerStockNotificationEmail::get_additional_template_args()` — product CTA + unsubscribe link.

No UTM on admin links.

## Error handling

- Verify email send failure (WC_Emails returns no status): best-effort. If the mailer is mis-configured, signup still succeeds; user can use the Resend button. No retry logic — matches the rest of WC.
- Notification not found / wrong status in resend paths: show error notice, redirect to the product permalink if resolvable, else `wc_get_page_permalink( 'shop' )`.
- Rate-limit exceeded: show informational notice, don't regenerate key, don't send email.
- Nonce failure on resend: silently drop the request (same pattern as `EmailActionController`).

## Testing

See `RSM-438-TESTING.md` for manual steps. Automated coverage:

- `EmailManagerTests::test_send_verify_email_triggers_verify_email`
- `EmailManagerTests::test_send_verified_email_triggers_verified_email`
- `SignupServiceTests::test_verify_email_sent_when_double_opt_in_required`
- `SignupServiceTests::test_verify_email_not_sent_when_double_opt_in_disabled`
- `EmailActionControllerTests::test_verified_email_sent_after_successful_verification`
- `NotificationManagementServiceTests::test_resend_request_sends_verify_email`
- `NotificationManagementServiceTests::test_resend_request_rate_limited`
- `NotificationManagementServiceTests::test_resend_request_rejected_if_already_verified`
- `NotificationManagementServiceTests::test_resend_request_rejects_invalid_nonce`
- `NotificationEditPageTests::test_admin_resend_button_sends_verify_email`
- `UtmHelperTests::test_add_email_utm_params_preserves_existing_query`
