---
post_title: Navigation
sidebar_label: Navigation
---

# Navigation

Place your extension where merchants already do the related work. Reuse WooCommerce's navigation and supported integration points before adding another destination.

## Choose a location

| Merchant task | Preferred location and integration |
| --- | --- |
| Work on an existing product, order or other WooCommerce object | Extend that object's supported editing surface. A feature does not need its own menu item just because it comes from an extension. |
| Configure payments or shipping | Use the relevant [payment gateway](../../features/payments/payment-gateway-api.md) or [shipping method](../../features/shipping/shipping-method-api.md) settings integration. |
| Configure options that fit an existing settings area | Add a [section under the relevant WooCommerce settings tab](../settings-and-config/adding-a-section-to-a-settings-tab.md). |
| Configure a service integration | Use [integration settings](../settings-and-config/implementing-settings.md) when the Integrations area is the right home for the task. |
| Configure options with no suitable existing settings area | Consider a [WooCommerce settings page](../settings-and-config/extend-wc-settings-page.md), which adds a tab under **WooCommerce > Settings**. Add this navigation weight only when an existing tab or section is unsuitable. |
| Carry out a distinct workflow that needs its own screen | Place it within the relevant WooCommerce category and use the supported [admin page registration](../settings-and-config/working-with-woocommerce-admin-pages.md). |

For example, a product customization feature belongs with product editing; a shipping method's configuration belongs with shipping settings. Distinguish configuration from everyday work rather than making merchants choose between duplicate settings destinations.

## Preserve the host's structure

Inside **WooCommerce > Settings**, retain the host's top-level navigation, header and [save contract](../settings-and-config/settings-ui.md#page-header). Do not wrap an embedded settings section in a second page shell or repeat a generic "General" heading and introduction that add no context.

Use [`Page` from `@wordpress/admin-ui`](https://wordpress.github.io/gutenberg/?path=/docs/admin-ui-page--docs) only when your extension owns a supported standalone page boundary and needs that layout. If the host already renders `Page`, add your content within that frame rather than nesting another instance. Verify API availability in the versions you support. Choosing a component does not change the screen's location or replace WooCommerce page registration. Follow the [admin component guidance](./README.md#admin-components) for controls and the host integration for navigation, permissions and saving. The React [Settings UI](../settings-and-config/settings-ui.md) is experimental and opt-in; it is not required to choose an appropriate settings location.

Keep a single settings screen simple. Group related fields with clear headings. Do not add extension-level tabs by default: a small group such as "Booking form" can remain a section on the same screen. Separate destinations only when distinct merchant tasks justify them, using a supported host pattern. Preserve the host's existing settings tabs.

Reuse the host's current WPDS-aligned navigation rather than copying legacy tabs or restyling global WooCommerce navigation. Keep the selected destination visible when navigation overflows. Preserve established entry points and deep links during an unrelated visual update.

The [settings composition example](./README.md#settings-composition-example) shows field grouping within one save scope. It depicts a standalone page; use its content structure within WooCommerce's existing frame when your extension is embedded in Settings.

Avoid a new top-level WordPress menu when the task fits WooCommerce's existing structure. Load application data through APIs instead of embedding another application's admin interface in an iframe.

## Labels and wayfinding

Use short, descriptive labels in sentence case, such as "Shared space booking". Keep product names and acronyms in their official capitalization. Aim for a label that fits on one line without forcing line breaks, and check translations and narrow layouts.

Use a functional, original extension name, such as "Appointments", rather than a vendor prefix or the name of an existing WooCommerce feature. Keep labels consistent across menus, page headings, settings and setup links. A setup action should open the relevant task or settings section directly, with a clear way back.
