# Mobile app login

Standalone wc-admin page that lets a logged-in merchant sign in to the Woo
mobile app on their phone by scanning a QR code.

## Audience

Merchants who already have the Woo mobile app installed on their phone and
want a direct, one-shot way to sign in — without going through the onboarding
modal on the wc-admin home screen.

## Route

`/wp-admin/admin.php?page=wc-admin&path=/mobile-app-login`

Registered in `client/admin/client/layout/controller.js` with capability
`manage_woocommerce`, so admins and shop managers can reach it.

## Scope

Application Password flow only. The WordPress.com multi-store flow is deferred
to a future task. The page's single-column layout leaves room below the QR for
a future secondary CTA, so adding that flow later does not require a
structural rewrite.

## Reused components

- `<QRDirectLoginCode />` from
  `client/admin/client/homescreen/mobile-app-modal/components/QRDirectLoginCode.tsx`
  renders the QR, countdown, and in-QR FAQ link.
- `useQRLoginToken` from
  `client/admin/client/homescreen/mobile-app-modal/components/useQRLoginToken.tsx`
  owns the token lifecycle — it is consumed indirectly by
  `<QRDirectLoginCode />`.

Neither file is modified by this page. The shared QR component only allows a
manual retry after it reaches an error or expired state, so the page does not
mint parallel valid login tokens while a QR code is still live.

## What this page does not do

- No Jetpack / WordPress.com detection branching.
- No `<SendMagicLinkButton />` — the email magic-link CTA stays in the
  onboarding modal only.
- No URL input field — the page is only reachable inside the merchant's own
  wp-admin, so the site URL is already known.
- No "install the app" wizard step — the audience is "app already installed."

## Tests

See `test/index.test.tsx`.
