---
post_title: Navigation
sidebar_label: Navigation
---

# Navigation

Place your product navigation elements within the existing WooCommerce menu structure.

## Extension with its own menu item

The two options to extend the WooCommerce menu structure are within the category menu or within the relevant settings areas.

![Category Subnavigation Image](/img/doc_images/Category-Subnavigation.png)

## Category sub-navigation

If your plugin is extending an area of WooCommerce, it should live directly within that category’s section.

For example, TikTok lives in Marketing and Product Add-Ons lives in Products.

![Category Setting Image](/img/doc_images/Category-Settings.png)

## Settings

If your plugin adds a settings screen to set up the plugin, settings should be under an appropriate tab on the WooCommerce > Settings screen.

For example, shipping and payments extensions will appear in their relevant Settings area. Only if necessary, create a top-level settings tab if your extension has settings that don’t fit under existing tabs and creating a sub-tab isn’t appropriate.

### Don’t: Add top-level navigation

If your product is extending WooCommerce, there’s a 99.9% chance your product navigation and settings should live within the WooCommerce nav structure—see the menu structure examples above.

### Don’t: No iframes, only APIs

To create a cohesive experience, application data should be loaded via API instead of an iframe.

### Do: Keep menu structure simple

Keep menu structure simple. Use existing WooCommerce menu structures as much as possible to reduce redundancies.

If your plugin must introduce multiple pages or areas, consider grouping them in tabs using existing components to remain consistent with WooCommerce structure.

## Extension with no menu item

Some extensions don’t require a menu item because they extend specific features within an existing product area.

Integrated features include extensions that don't live in the navigation and simply add functionality to an existing system. For example, the Product Bundles plugin is limited to the product form.

In this case, there’s no navigation item or extension home screen. The Plugins page can be used to share updates with your users.

## Plugin name

The plugin name represents a way for merchants to identify your plugin across multiple touchpoints in the WooCommerce admin.

### Don’t: Use an existing feature or extension in the plugin title

The plugin name should appear at all times in the UI as a functional and original name. e.g “Appointments” instead of “VendorXYZ Bookings Plugin for WooCommerce.”

### Do: One-line navigation label

Keep extension names short, ideally within 20 characters, to make it easier for merchants to read and understand.

Keep all navigation labels on one line. Do not introduce a second line in any of the menu items.
