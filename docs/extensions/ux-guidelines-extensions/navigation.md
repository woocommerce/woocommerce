---
post_title: Navigation
sidebar_label: Navigation
---

# Navigation

Place your extension where merchants already do the related work. Use WooCommerce's existing navigation before adding a top-level WordPress menu.

## Choose a location

| Merchant task | Preferred location and integration |
| --- | --- |
| Work on a product, order or other WooCommerce object | Extend that object's supported editing screen. |
| Configure payments or shipping | Use the relevant [payment gateway](../../features/payments/payment-gateway-api.md) or [shipping method](../../features/shipping/shipping-method-api.md) settings integration. |
| Configure options that fit an existing settings area | Add a [section under the relevant WooCommerce settings tab](../settings-and-config/adding-a-section-to-a-settings-tab.md). |
| Configure a service integration | Use [integration settings](../settings-and-config/implementing-settings.md) when the task belongs in the Integrations area. |
| Configure options with no suitable existing settings area | Add a [WooCommerce settings tab](../settings-and-config/extend-wc-settings-page.md) only when no existing tab or section fits. |
| Carry out a distinct workflow that needs its own screen | Use [admin page registration](../settings-and-config/working-with-woocommerce-admin-pages.md) and place the screen within the relevant WooCommerce category. |

## Keep WooCommerce's structure

Inside **WooCommerce > Settings**, keep the existing navigation, settings tabs, header and [saving behavior](../settings-and-config/settings-ui.md#page-header). Do not add a second page shell or a generic "General" heading that adds no context.

Use [`Page` from `@wordpress/admin-ui`](https://wordpress.github.io/gutenberg/?path=/docs/admin-ui-page--docs) only for a standalone page your extension owns, when available in the versions you support. If the existing page already uses `Page`, add content within it. This layout does not replace WooCommerce page registration. Choose controls using the [admin component guidance](./README.md#admin-components).

Group related fields on one screen; a small group such as "Booking form" does not need its own tab. Add separate destinations only for distinct merchant tasks. See the [settings composition example](./README.md#settings-composition-example) for field grouping.

Use WooCommerce's current WPDS-aligned navigation without restyling it. Keep the selected destination visible when navigation overflows, and preserve existing entry points and deep links during visual updates.

The [React Settings UI](../settings-and-config/settings-ui.md) is experimental and opt-in; it is not required for these placements.

## Labels and wayfinding

Use short, descriptive labels in sentence case, such as "Shared space booking". Preserve official capitalization for product names and acronyms. Check long translations and narrow layouts.

Keep labels consistent across menus, headings, settings and setup links. Link setup actions directly to the relevant task or settings section, with a clear way back.
