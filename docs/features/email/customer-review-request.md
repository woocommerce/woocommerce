---
post_title: Customer review request email
sidebar_label: Customer review request
---

# Customer Review Request

## Overview

The Customer Review Request feature emails customers a few days after their order is marked completed, asking them to review the products they bought. The email links to a dedicated tokenized landing page where the customer rates each line item, optionally writes a review, and submits everything in a single form.

The feature is implemented in the `Automattic\WooCommerce\Internal\OrderReviews` namespace and is wired into the WC dependency container. The container instantiates each sub-service and auto-calls its `init()` method, where the sub-service registers its WordPress hooks.

## Feature flag

The feature ships **off by default** behind the `customer_review_request` feature flag (beta). Merchants enable it under **WooCommerce → Settings → Advanced → Features**. While the flag is off:

- `WooCommerce::maybe_init_order_reviews()` skips resolving the OrderReviews services, so none of their hooks fire and no DB work happens on each request.
- The review-request email class is not registered with `WC_Emails`.
- No host page is created.

Toggling the flag on resolves the services. The host page is seeded on the first `init` after the flag flips on (see "Host page lifecycle" below) and the rewrite rule is flushed on the next `wp_loaded`.

## Sub-services

| Class | Responsibility |
| ----- | -------------- |
| `Scheduler` | Schedules the delayed review-request email via Action Scheduler when an order moves to `completed`, and unschedules it on any transition out of the eligible set (cancellation, refund, processing, on-hold, pending, failed, trash, delete). |
| `Endpoint` | Routes `/review-order/{id}/?key={order_key}` to the WC-managed Review Order page, seeds and self-heals the host page, runs the gating checks (key match, eligible status, customer ownership), and renders the landing-page template through the `[woocommerce_review_order]` shortcode inside the page's content. Also registers the host page's admin affordances (Pages-list label, nav-list exclusion, host-page title suppression). |
| `SubmissionHandler` | AJAX handler that consumes the form post. Per-row outcome reporting (`ok`, `pending_moderation`, `error`); honors WordPress's `comment_moderation` option; tags every inserted comment with `verified=1` and `_review_order_id` commentmeta so the page can scope existing reviews to the order being viewed. When the customer resubmits a row that already has a review for this order, the existing comment is updated in place rather than duplicated. |
| `ItemEligibility` | Decides per line item whether the row should render the form (`STATUS_FORM`) or be skipped (`STATUS_SKIP`, used when reviews are disabled on the product). Lookups are order-scoped via the `_review_order_id` commentmeta, so a customer who reviewed the same product on a previous order can still review it again here, and the form pre-fills with the existing rating/text when the customer already submitted a review for this order. Also provides the default callback that excludes fully-refunded line items. |
| `StarRating` | Server-side renderer for the accessible 5-star rating control (radio inputs + SVG visuals + caption). |
| `Meta` | Builds the shared customer/order meta-line (`Name · email · Order #N (date)`) consumed by both the form template and the empty-state thank-you template, so the wording stays in sync between views. |

## Host page lifecycle

`Endpoint::maybe_create_host_page()` is the single point of truth for the host page state. It runs on `init` priority 4 (when the feature flag is on) and is also invoked from `WC_Install::create_pages()` callers via the permanent `woocommerce_create_pages` filter that `Endpoint::init()` registers. The same code path therefore covers:

- **First-time enable.** Creates the page (via `WC_Install::create_pages()`, with our filter injecting the entry) and queues a rewrite-rule flush for the next `wp_loaded`.
- **Fresh install / activation / database upgrade.** Any call to `WC_Install::create_pages()` sees our filter and creates the page if missing — no separate db update callback needed.
- **Status → Tools → Create default WooCommerce pages.** The same filter chain ensures the repair tool seeds the page alongside Cart, Checkout, Shop, etc.
- **Stale state.** Idempotent and self-healing: short-circuits on the fast path when the stored option already points at a published page that embeds our shortcode. Falls through to a slug-canonical reconciliation when the option dangles, and only creates a new page when no host page exists anywhere.

The host page is hidden from `wp_list_pages()` / `core/page-list` output via `Endpoint::exclude_self_from_page_list()`, kept out of nav menus' "Auto add new top-level pages" toggle via `Endpoint::skip_auto_menu_for_self()`, and labeled in the admin Pages list as "— Review Order Page" via `Endpoint::add_post_state_label()` (mirroring how `WC_Admin_Post_Types` labels Shop / Cart / Checkout / My account).

## Page-title suppression

The page body already prints its own `<h1>` ("Review your order" or "Thank you for your reviews"), so the theme-rendered page title would duplicate the wording both visually and for screen readers. `Endpoint::gate_request()` registers two suppression filters AFTER the auth check passes, scoped strictly to the host page id and the main-query loop:

- `the_title` → `Endpoint::maybe_hide_page_title()` — empties the title for classic themes.
- `render_block_core/post-title` → `Endpoint::maybe_hide_post_title_block()` — empties the `core/post-title` block on block themes when its resolved `context['postId']` matches the host page.

Both filters are no-ops on any other render context.

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

- `woocommerce_review_order_eligible_statuses` (`string[]`, `WC_Order`)  
  Widen or narrow the order statuses that pass the route-level gate. Defaults to `[ 'completed' ]`.

- `woocommerce_review_order_eligible_items` (`WC_Order_Item[]`, `WC_Order`)  
  Filter the line items rendered on the page. The default callback excludes fully-refunded items.

- `woocommerce_review_order_rating_labels` (`array<int,string>`)  
  Customize the 1-5 star labels surfaced beside the control. Defaults to `Very poor / Not that bad / Average / Good / Perfect`.

### Actions

#### Send pipeline

- `woocommerce_send_review_request` (`int $order_id`)  
  Action Scheduler hook. `Scheduler` enqueues this for each eligible order when the order moves to `completed`, with the delay configured on the email settings screen. When the delay elapses, Action Scheduler fires the hook and WC's transactional-email pipeline (`WC_Emails::email_actions()`) re-dispatches it as `woocommerce_send_review_request_notification`. Useful for plugins that need to observe or short-circuit the scheduled send without owning the email itself.

- `woocommerce_send_review_request_notification` (`int $order_id`)  
  Transactional email pipeline action, fired by `WC_Emails` after `woocommerce_send_review_request`. `WC_Email_Customer_Review_Request::trigger()` listens here and builds + sends the email. Useful when you need to add a second listener (e.g. analytics) alongside the built-in mailer.

#### Form

- `woocommerce_review_order_form_fields` (`WC_Order_Item_Product`, `WC_Product`, `WC_Order`, `int $row_index`)  
  Fires after each row's image / rating / review columns, as a sibling of `.woocommerce-review-order__item-row` and immediately before `</li>`. Echo extra fields directly; they render below the row's columns so injected UI doesn't disturb the three-column layout.

- `woocommerce_review_order_submitted` (`WC_Order $order`, `array $results`)  
  Fires after the form has been processed, even when some rows ended in `error`. `$results` is the per-row outcome map keyed by row index, with `product_id`, `status` (`ok | pending_moderation | error`), and (on success) `comment_id`.

## Post-submit thank-you flow

After a successful AJAX submission the client renders the thank-you view in place:

- The frontend script gates on `anySaved && !anyFailed` (i.e. every processed row returned `ok` or `pending_moderation`).
- When the gate passes, JS adds `.is-success` to `.woocommerce-review-order`. The SCSS rule hides the form chrome (`__title`, `__intro`, `__legend`, `__notice`, `__form`) and reveals a hidden `.woocommerce-review-order__success` block that re-uses the empty-state copy.
- If any row returned `error`, the form stays visible so the per-row status notes (`__item-status--error`) remain readable.

Refreshing the page or re-clicking the email link re-renders the form for any remaining items — server-side routing is unchanged, so no URL hop and no server-side flag are involved.

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

## Accessibility notes

- The star-rating control is a group of five native `<input type="radio">` elements inside a wrapper that carries the ARIA `role="radiogroup"` and `aria-labelledby` pointing at the rating label. The visual stars are SVG; the inputs themselves are visually hidden but remain in the accessibility tree.
- Keyboard navigation is supported via Arrow keys and Home/End.
- A live caption (`aria-live="polite"`) announces the selected rating's label.
- The "Required" indicator on the rating label is exposed to screen readers via a `.screen-reader-text` span alongside the visual asterisk.
- Inline mandatory-rating errors render with `role="alert"` so assistive tech announces them on submission.
- The host-page heading suppression keeps the chrome `<h1>` from duplicating the body `<h1>`, avoiding the "page announced twice" issue with screen readers.
