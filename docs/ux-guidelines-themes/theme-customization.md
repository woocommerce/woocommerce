---
post_title: WooCommerce Theme Guidelines - Customization
menu_title: Customization
---

> This page of the guidelines applies to development of non-block themes only. For more specific guidance on development of block themes, refer to the [WordPress Developer's Guide to Block Themes](https://learn.wordpress.org/course/a-developers-guide-to-block-themes-part-1/).

Themes have to rely on the customizer for any type of initial set up. Specific onboarding flows are not permitted.

Any customization supported by the theme, such as layout options, additional features, block options, etc, should be delivered in the customizer or on block settings for blocks that are included in the theme.

Themes should not bundle or require the installation of additional plugins/extensions (or frameworks) that provide additional options or functionality. For more information on customisation, check out the [WordPress theme customization API](https://codex.wordpress.org/Theme_Customization_API).

On activation, themes shouldn't override the WordPress theme activation flow by taking the user into other pages.
