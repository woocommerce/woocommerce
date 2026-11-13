---
post_title: Colors
sidebar_label: Colors
---

# Colors

Admin interfaces should feel at home in WordPress. Follow the [admin component guidance](./README.md#admin-components): prefer `@wordpress/ui`, and use `@wordpress/components` only for verified coverage gaps in the supported version, with a documented reason. Preserve the host's theme and the user's WordPress admin color scheme rather than introducing an extension-specific palette for shared controls.

## Use components and semantic tokens

Use component props for supported visual states and intent. For example, use a notice's error intent instead of recoloring it red with custom CSS.

When a component does not cover the styling need, choose [semantic WPDS color tokens](https://wordpress.github.io/gutenberg/?path=/docs/design-system-tokens-color--docs) from `@wordpress/theme` by purpose, rather than copying hex values or palette primitives. For example, `--wpds-color-foreground-content-neutral` is for text and static icons; `--wpds-color-background-surface-neutral` is for a container surface. Keep foreground, background and stroke roles aligned with the element's intent and state.

Verify the tokens and [theme setup](https://wordpress.github.io/gutenberg/?path=/docs/design-system-theme-theme-provider--docs) for the versions you support. Do not assume WPDS variables are available on every existing PHP screen. When maintaining those screens, preserve their supported WordPress styles and color-scheme behavior as you plan modernization.

## Check accessibility in context

Meet the [WordPress accessibility standards](./accessibility.md) and check the rendered interface in the host's supported themes and color schemes:

- Normal text needs at least **4.5:1** contrast against its background; qualifying large text needs at least **3:1**. See [WCAG text contrast](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html).
- Visual information needed to identify controls, their states and meaningful graphics needs at least **3:1** against adjacent colors, subject to the [WCAG non-text contrast requirements](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html).
- Use text, icons or another visible cue alongside color to communicate errors, selection and status. Color alone is not enough.
- Check hover, focus, selected and error states. Using a token does not guarantee that every foreground/background pairing meets contrast requirements.

Use the [color contrast checker](https://webaim.org/resources/contrastchecker/) and the [WordPress accessibility coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/) when reviewing your implementation.

The [partial-results example](./notices.md#partial-results) pairs semantic feedback styling with text labels for each outcome. The message and labels explain what needs attention even without color.

## Keep brand resources separate

The [Woo brand and logo guidelines](https://woocommerce.com/brand-and-logo-guidelines/) cover product identity and brand assets. They are not a functional admin color palette. Use the host's components and semantic colors for controls, navigation, validation and notices.
