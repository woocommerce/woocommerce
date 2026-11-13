---
sidebar_label: Extension guidelines
category_slug: user-experience-extensions
post_title: Extension guidelines
---

# Extension Guidelines

This section covers general guidelines, and best practices to follow in order to ensure your product experience aligns with WooCommerce for ease of use, seamless integration, and strong adoption.

We strongly recommend you review the current [WooCommerce setup experience](https://woocommerce.com/documentation/plugins/woocommerce/getting-started/) to get familiar with the user experience and taxonomy.

We also recommend you review the [WordPress core guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) to ensure your product isn't breaking any rules, and review [this helpful resource](https://woocommerce.com/document/grammar-punctuation-style-guide/) on content style.

## General

Use existing WordPress/WooCommerce UI, built-in components (text fields, checkboxes, etc) and existing menu structures.

Plugins which draw on WordPress' core design aesthetic will benefit from future updates to this design as WordPress continues to evolve. If you need to make an exception for your product, be prepared to provide a valid use case.

### Admin components

For new WooCommerce extension admin interfaces and modernization work, prefer [`@wordpress/ui`](https://wordpress.github.io/gutenberg/?path=/docs/design-system-components-introduction--docs). When it does not cover a required component or capability in the supported version, use a suitable [`@wordpress/components`](https://wordpress.github.io/gutenberg/?path=/docs/components-introduction--docs) component as a fallback and document the reason.

Check the documentation and exports for the package versions your extension supports. A coverage gap can be a missing control or a missing behavior needed for your task. Before adopting a fallback, verify its API, accessibility, styling and compatibility, and record the missing capability and why the fallback is appropriate.

Reuse WooCommerce's supported page, settings and integration patterns. For settings, preserve the host's [header and save contract](../settings-and-config/settings-ui.md#page-header). When using the experimental, opt-in Settings UI, prefer [DataForm's built-in controls](../settings-and-config/registering-settings-ui-components.md) before registering custom components. Use [semantic design tokens](https://wordpress.github.io/gutenberg/?path=/docs/design-system-tokens-introduction--docs) for custom styling, and plan changes to existing PHP and React screens around their compatibility requirements.

### Settings composition example

![Standalone settings page with Store details and Checkout field groups, labels and help text, and one page-level Save action](/img/doc_images/wpds-settings-page.png)

This example from the WordPress [Settings Page patterns proposal](https://github.com/WordPress/gutenberg/pull/86451) shows related fields grouped into sections, help beside the relevant control and one save scope for the page. It is a preview of an upstream pattern, not a shipped WooCommerce settings screen. Check the proposal and component documentation for current API availability and recommendation status.

Reuse the composition within your supported host. Inside WooCommerce Settings, keep the host's navigation, header and save action; do not add this standalone page shell inside an existing settings section. See the [settings header and save contract](../settings-and-config/settings-ui.md#page-header).

### Design resources

-   [WordPress Design System components (`@wordpress/ui`)](https://wordpress.github.io/gutenberg/?path=/docs/design-system-components-introduction--docs)
-   [WordPress Components reference (`@wordpress/components`, for verified coverage gaps)](https://wordpress.github.io/gutenberg/?path=/docs/components-introduction--docs)
-   [WordPress Design System tokens](https://wordpress.github.io/gutenberg/?path=/docs/design-system-tokens-introduction--docs)
-   [Figma for WordPress handbook](https://make.wordpress.org/design/handbook/get-involved/tools-figma/) | [WordPress Design System in Figma](https://www.figma.com/community/file/1436359662053949167/wordpress-design-system)
