---
sidebar_label: Version support policy
category_slug: contributing
post_title: WordPress and WooCommerce version support policy
---

# WordPress and WooCommerce version support policy

Woo uses a latest-minus-one (L-1) policy for WordPress and WooCommerce version
compatibility. This policy defines the versions that we officially test and
support as dependencies; it is separate from entitlement to customer support services.

L-1 includes:

- The current stable release series (latest).
- The stable release series immediately before it (latest minus one).

Within each release series, only the latest patch release is officially
supported. For example, if `6.8` and `6.7` are the two supported release
series, testing should use the latest available `6.8.x` and `6.7.x` releases.

## How the policy applies

| Project | Supported versions |
| --- | --- |
| WooCommerce Core | The latest and immediately previous WordPress release series. |
| Woo-owned extensions | The latest and immediately previous release series of both WordPress and WooCommerce. |

The policy for WooCommerce Core refers to the versions of WordPress on which
Core runs. Woo-owned extensions also depend on WooCommerce, so they apply L-1
to both WordPress and WooCommerce.

Third-party extension authors control their own version requirements. They can
adopt a wider compatibility window, but should test every version they claim
to support and publish accurate requirements.

## When the support window changes

When a new stable WordPress or WooCommerce release series becomes available:

1. The new release series becomes the latest supported version.
2. The former latest release series becomes the L-1 version.
3. The older release series moves outside the official support window.

Beta and release candidate versions do not move the support window. Older
versions might continue to work, but they are not part of the official test
matrix unless a project documents an exception.

## Plugin version metadata

Keep plugin headers and `readme.txt` metadata aligned with the support window:

- `Requires at least` identifies the earliest supported WordPress release
  series.
- `Tested up to` identifies the latest tested WordPress release series.
- `WC requires at least` identifies an extension's earliest supported
  WooCommerce release series.
- `WC tested up to` identifies an extension's latest tested WooCommerce
  release series.

For an extension following L-1, the two "requires at least" fields should
identify the immediately previous release series, while the two "tested up to"
fields should identify the current release series. See the
[example WordPress plugin header comment](/docs/extensions/core-concepts/example-header-plugin-comment/)
for header formatting.
