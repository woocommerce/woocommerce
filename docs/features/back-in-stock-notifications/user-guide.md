---
post_title: Back in stock notifications
sidebar_label: User guide
sidebar_position: 1
---

# Back in stock notifications

Back in stock notifications let customers ask to be emailed when an out-of-stock product becomes available again. Sign-ups are stored alongside your product data and are processed automatically when a product returns to stock.

## Status

Back in stock notifications are currently released as an alpha feature. The feature is disabled by default and is only loaded when the `WOOCOMMERCE_BIS_ALPHA_ENABLED` constant is set to `true`. You can enable it by adding the following to `wp-config.php`:

```php
define( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );
```

Once the feature reaches general availability, the constant will be replaced with a standard feature flag and the setting will be available through the Features screen.

## How to enable sign-ups

1. Make sure the alpha constant above is defined.
2. Go to **WooCommerce > Settings > Products > Customer stock notifications**.
3. Check **Allow sign-ups** and save.

After sign-ups are enabled, a sign-up form appears on the product page of any supported, eligible product that is currently out of stock.

### Available settings

The settings section exposes the following options:

- **Allow sign-ups.** Turns the sign-up form on or off for the whole store.
- **Require double opt-in to sign up.** Customers receive a verification email and must click a link before their sign-up becomes active. Use this to reduce spam sign-ups and keep your list clean.
- **Delete unverified notification sign-ups after (in days).** Controls how long pending (unverified) sign-ups are stored. Enter `0` or leave empty to keep them indefinitely.
- **Guest sign-up.** Require customers to be logged in before signing up. When enabled, guests are redirected to the log-in page.
- **Create an account when guests sign up for stock notifications.** Automatically create a customer account the first time a guest signs up. Only available when guest sign-up is allowed.

If you enable **Create an account when guests sign up for stock notifications** while WooCommerce is configured to skip automatic password generation, a warning appears on the settings page. Customers will then need to reset their password before they can log in.

Hiding out-of-stock products from the catalog also prevents sign-ups — a warning is shown on the settings page if both options are active at the same time.

## Per-product control

Each product has a **Stock notifications** checkbox under the Inventory panel. Use it to opt an individual product out of sign-ups even when the global **Allow sign-ups** option is enabled. The checkbox only appears for supported product types (simple, variable, and variation) and only when the store-wide option is on.

## How customers sign up

On the front end, when a supported product is out of stock, customers see a **Back in stock** form directly below the product summary. The form behaviour depends on your settings.

- **Logged-in customer.** The form shows a single **Notify me** button; the customer's account email is used.
- **Guest, email collection allowed.** The form shows an email field, an optional **Create an account** checkbox (if enabled), and a **Notify me** button.
- **Guest, login required.** The form is replaced with a message inviting the customer to log in.
- **Already signed up.** The form is replaced with a message linking the customer to the **Stock notifications** tab in their account, so they can manage existing sign-ups.

If double opt-in is enabled, the customer receives a **Verify your notification** email and must click the verification link before their sign-up becomes active. Unverified sign-ups never trigger a back-in-stock email.

Customers can unsubscribe at any time from a one-click link in any notification email or from the **Stock notifications** section of their account.

## Emails sent by the feature

Back in stock notifications add three customer-facing emails. All of them appear under **WooCommerce > Settings > Emails** and can be customised there.

- **Customer back in stock notification.** Sent when a signed-up product is back in stock. Includes a product image, the current price, and a **Shop now** link.
- **Customer back in stock notification (verification).** Sent when the customer signs up and double opt-in is required. Contains the verification link.
- **Customer back in stock notification (verified).** Confirmation email sent after a customer successfully verifies their sign-up.

Sending is throttled per customer so the same person does not receive duplicate emails for the same product in rapid succession.

## Managing sign-ups in the admin

A new **WooCommerce > Notifications** submenu hosts the notifications list. You can:

- Filter sign-ups by product, customer, or status.
- Create a new sign-up on behalf of a customer.
- Edit a sign-up to update the product, customer, or status.
- Cancel a sign-up so the customer no longer receives emails.
- Resend a verification email to a pending sign-up.

When a product comes back in stock, the admin notices panel on the **Notifications** screen lets you know that the queue is being processed. Processing happens in the background through Action Scheduler, so the admin screen remains responsive on large stores.

### Sign-up statuses

Each sign-up has one of four statuses:

- **Pending** — Awaiting email verification when double opt-in is enabled.
- **Active** — Verified and waiting for the product to return to stock.
- **Sent** — The back-in-stock email has been delivered.
- **Cancelled** — The customer or admin cancelled the sign-up and no email will be sent.

## Privacy

Sign-up data is covered by the WordPress personal-data export and erase tools. Requests run through WooCommerce's existing privacy screens under **Tools > Export Personal Data** and **Tools > Erase Personal Data**. Cancelled sign-ups are preserved in the database for reporting purposes until the customer, admin, or privacy eraser removes them explicitly.

## Further reading

- Developers looking to add hooks, query sign-ups from code, or replace templates should read [Extending back in stock notifications](./extending.md).
