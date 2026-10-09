---
post_title: Customer review request email
sidebar_label: Customer review request
---

# Customer Review Request

## Overview

The Customer Review Request feature emails customers a few days after their order is marked completed, asking them to review the products they bought. The email links to a dedicated tokenized landing page where the customer rates each eligible product item, optionally writes a review, and submits everything in a single form.

The feature lives in the `Automattic\WooCommerce\Internal\OrderReviews` namespace and registers its WordPress hooks only when the feature flag is on.

## Feature flag

The feature ships **off by default** behind the `customer_review_request` feature flag (beta). Merchants enable it under **WooCommerce → Settings → Advanced → Features**. While the flag is off, none of the feature's hooks run, the review-request email is not registered with `WC_Emails`, and no host page is created. Enabling it registers the hooks and seeds the host page (see "Host page" below).

## How it works

When the feature is enabled, these pieces register their hooks:

- **Scheduler** — on order completion, schedules the delayed email via Action Scheduler, and cancels it if the order later leaves the eligible set (cancelled, refunded, processing, on-hold, pending, failed, trashed, deleted). It also skips scheduling when nothing on the order is reviewable (see "Skip when nothing is reviewable" below).
- **Endpoint** — routes `/review-order/{id}/?key={order_key}` to the WC-managed Review Order page and renders it through the `[woocommerce_review_order]` shortcode. Access requires a matching order key and an eligible status; for an order that has a customer id, a logged-in visitor must be that customer (guests, and anyone holding the key for a customer-less guest order, still pass).
- **SubmissionHandler** — the AJAX endpoint that stores submitted reviews, one per `(product, variation)` slot, honoring the `comment_moderation` option and updating a review in place when the customer resubmits the same row.
- **ItemEligibility** — decides which line items are reviewable, excludes fully-refunded items, and keeps each variation's row independent.

`StarRating` and `Meta` are stateless view helpers the templates call directly.

## Host page

When the feature is enabled, WooCommerce creates and maintains a dedicated Review Order page (slug `review-order`, embedding the `[woocommerce_review_order]` shortcode). It is seeded by any "create default WooCommerce pages" run — fresh install, activation, database upgrade, and **Status → Tools → Create default WooCommerce pages** — and is self-healing: if the page is unpublished or the stored page option is re-pointed, it is restored on the next load. The page is hidden from nav menus and `wp_list_pages()` / `core/page-list` output, and is labeled "— Review Order Page" in the admin Pages list (matching how Cart / Checkout / Shop / My account are labeled). Its theme-rendered page title is suppressed so it does not duplicate the body `<h1>`.

## Skip when nothing is reviewable

If an order would land on the empty "nothing to review" page anyway, no email is scheduled or sent. At schedule time the Scheduler skips the order and logs the reason `no reviewable items`; at send time the email re-checks and is silently dropped if every item has since been reviewed or had reviews disabled (for example, the admin turns off reviews during the delay window).

A customer can still reach the empty-state page directly — by finishing or losing their reviewable items after the email went out, or by opening a bookmarked or admin-shared link — so `customer-review-order-empty.php` keeps rendering that fallback.

## Per-variation reviews

The Review Order page renders one row per reviewable slot — a distinct `(product, variation)` pair — rather than one per raw line item. `customer-review-order.php` passes the items through `ItemEligibility::unique_slot_items()` first, so two line items of the same variation collapse into a single row. Two *different* variations of one variable product (e.g. a T-shirt purchased in Small + Medium) still get two distinct rows, each with its own image and attribute summary, and each can carry an independent rating + text review.

Storage is on the parent product post — WC reviews don't live on variations — but the line item context is captured via two pieces of comment meta written by `SubmissionHandler` alongside `_review_order_id`:

- `_review_variation_id` — the line item's `variation_id` (`0` for simple products, kept for symmetry).
- `_review_variation_summary` — the attribute summary at the moment the review was written (e.g. `"Size: Small, Colour: Red"`). Captured as a snapshot so historical reviews stay readable even if the variation is later retired or its attribute terms are renamed.

`ItemEligibility::find_existing_review()` keys by `(order_id, product_id, variation_id, email)`, so each variation row pre-fills against its own review on revisit. The Review Order row template renders the variation's attribute summary via `ItemEligibility::format_variation_summary()`, which restricts the attributes to the live variation's whitelisted slugs before passing them to `wc_get_formatted_variation()` — third-party item meta (engraving, gift-wrap, custom add-ons) is intentionally excluded so private personalisation data can't leak into the public review snapshot.

On the parent product's single-product Reviews tab, the variation summary is prepended above each comment body through the existing `woocommerce_review_before_comment_text` action; reviews without the summary meta render unchanged. The theming class is `.woocommerce-review__variation-summary`.

## Tokenized URL helper

```php
$url = wc_get_review_order_url( $order );
```

Returns the tokenized review-order URL for a given `WC_Order`. Use this rather than constructing the URL manually so the helper's filter and pretty/plain-permalink fallback both apply.

## Filter and action reference

### Filters

- `woocommerce_should_send_review_request` (`bool`, `WC_Order`)  
  Return `false` to skip scheduling the email for a specific order.

- `woocommerce_review_request_delay_seconds` (`int`)  
  Override the delay (in seconds) before the email fires. Defaults to the value configured on the email settings screen.

- `woocommerce_review_order_url` (`string`, `WC_Order`)  
  Replace the URL emitted by `wc_get_review_order_url()`.

- `woocommerce_review_order_eligible_statuses` (`string[]`, `WC_Order|null`)  
  The order statuses treated as eligible. Defaults to `[ 'completed' ]`. The same list is consulted at four points: the route-level page gate, the AJAX submission handler, the send-time re-check, and the scheduler's cancellation (a queued email is unscheduled only when the order leaves this set). Initial scheduling stays tied to the `completed` transition, so widening this filter does not queue a request when an order enters the added status; it only keeps a queued request alive through transitions inside the expanded set and lets the page and send pass. The second argument is the order, or `null` when the scheduler evaluates a status change without a resolved order.

- `woocommerce_review_order_eligible_items` (`WC_Order_Item[]`, `WC_Order`)  
  The order items considered reviewable. The default callback excludes fully-refunded items. Beyond choosing which rows the page renders, it feeds the actionable-items check at both schedule time and send time, so emptying it for an order suppresses scheduling and sending too.

- `woocommerce_review_order_rating_labels` (`array<int,string>`)  
  Customize the 1-5 star labels surfaced beside the control. Defaults to `Very poor / Not that bad / Average / Good / Perfect`.

### Actions

#### Send pipeline

- `woocommerce_send_review_request` (`int $order_id`)  
  Action Scheduler hook. `Scheduler` enqueues this for each eligible order when the order moves to `completed`, with the delay configured on the email settings screen. When the delay elapses, Action Scheduler fires the hook and WC's transactional-email pipeline re-dispatches it as `woocommerce_send_review_request_notification` (the hook is registered with the pipeline in `WC_Emails::init_transactional_emails()`). Useful for plugins that need to observe the scheduled send without owning the email itself. It is a plain `do_action`, so a callback's return value cannot cancel the send — to prevent a send, use the `woocommerce_should_send_review_request` filter (evaluated at schedule time) instead.

- `woocommerce_send_review_request_notification` (`int $order_id`)  
  Transactional email pipeline action, fired by `WC_Emails` after `woocommerce_send_review_request`. `WC_Email_Customer_Review_Request::trigger()` listens here and builds + sends the email. Useful when you need to add a second listener (e.g. analytics) alongside the built-in mailer.

#### Form

- `woocommerce_review_order_form_fields` (`WC_Order_Item_Product`, `WC_Product`, `WC_Order`, `int $row_index`)  
  Fires after each row's image / rating / review columns, as a sibling of `.woocommerce-review-order__item-row` and immediately before `</li>`. Echo extra fields directly; they render below the row's columns so injected UI doesn't disturb the three-column layout.

- `woocommerce_review_order_submitted` (`WC_Order $order`, `array $results`)  
  Fires after the form has been processed, even when some rows ended in `error`. `$results` is the per-row outcome map keyed by row index, with `product_id`, `variation_id`, `status` (`ok | pending_moderation | error`), and (on success) `comment_id`.

## Post-submit thank-you flow

After a successful AJAX submission the thank-you view is shown in place of the form, re-using the empty-state copy. If any row failed, the form stays visible so the per-row error notes remain readable.

Refreshing the page or re-clicking the email link re-renders the form for any remaining items — server-side routing is unchanged, so there is no URL hop and no server-side flag.

## Theme overrides

The page renders through `wc_get_template()`, so themes can override any of:

- `templates/order/customer-review-order.php` — the page wrapper. Branches between the form view and the empty-state thank-you view, and pre-computes the per-row decisions consumed by the row template.
- `templates/order/customer-review-order-row.php` — one form row per item. Receives `existing_rating` and `existing_text` so the row pre-fills when the customer already submitted a review for this order.
- `templates/order/customer-review-order-empty.php` — thank-you view rendered when no actionable rows remain (every item already reviewed for this order, or every item skipped because reviews are disabled).
- `templates/order/star-rating.php` — the accessible star-rating control partial.

To override, copy a template into your theme while preserving the relative path:

- `templates/order/customer-review-order.php` → `yourtheme/woocommerce/order/customer-review-order.php`
- `templates/order/customer-review-order-row.php` → `yourtheme/woocommerce/order/customer-review-order-row.php`
- `templates/order/customer-review-order-empty.php` → `yourtheme/woocommerce/order/customer-review-order-empty.php`
- `templates/order/star-rating.php` → `yourtheme/woocommerce/order/star-rating.php`

### Theme-aware classes used by the page

The templates lean on built-in WC/WP classes so the active theme drives the styling instead of opinionated SCSS:

- The disabled-products notice composes with `.woocommerce-info` — themes that already restyle classic WC notices automatically restyle this one too.
- The customer/order meta line uses `.woocommerce-breadcrumb` so it adopts the theme's small-grey-secondary-text treatment.
- The Submit button picks up `wc_wp_theme_get_element_class_name( 'button' )` (i.e. `wp-element-button` on block themes), so block themes paint it with their button styling.

If you do override `customer-review-order.php`, preserve those class names so theme integrators don't have to re-style the page from scratch.

## Email template overrides

The HTML and plain-text bodies of the email itself follow standard WC conventions:

- `templates/emails/customer-review-request.php`
- `templates/emails/plain/customer-review-request.php`
- `templates/emails/block/customer-review-request.php` (block-based email editor)

## Tests

PHPUnit tests live under `plugins/woocommerce/tests/php/src/Internal/OrderReviews/` (plus the email-class test under `plugins/woocommerce/tests/php/includes/emails/`); the Playwright specs are in `plugins/woocommerce/tests/e2e/tests/order/review-order-page.spec.ts`.

```bash
pnpm --filter=@woocommerce/plugin-woocommerce test:php:env -- --filter "OrderReviews|Customer_Review_Request"
pnpm --filter=@woocommerce/plugin-woocommerce test:e2e:default --grep "Customer Review Request — Review Order page"
```

## Accessibility notes

- The star-rating control is a group of five native `<input type="radio">` elements inside a wrapper that carries the ARIA `role="radiogroup"` and `aria-labelledby` pointing at the rating label. The visual stars are SVG; the inputs themselves are visually hidden but remain in the accessibility tree.
- Keyboard navigation is supported via Arrow keys and Home/End.
- A live caption (`aria-live="polite"`) announces the selected rating's label.
- The "Required" indicator on the rating label is exposed to screen readers via a `.screen-reader-text` span alongside the visual asterisk.
- Inline mandatory-rating errors render with `role="alert"` so assistive tech announces them on submission.
- The host-page heading suppression keeps the chrome `<h1>` from duplicating the body `<h1>`, avoiding the "page announced twice" issue with screen readers.
