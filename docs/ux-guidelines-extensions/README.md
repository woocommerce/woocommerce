---
category_title: Extension Guidelines
category_slug: user-experience-extensions
post_title: Extension Guidelines
---

This section covers general guidelines, and best practices to follow in order to ensure your product experience aligns with WooCommerce for ease of use, seamless integration, and strong adoption.

We strongly recommend you review the current [WooCommerce setup experience](https://woocommerce.com/documentation/plugins/woocommerce/getting-started/) to get familiar with the user experience and taxonomy.

We also recommend you review the [WordPress core guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) to ensure your product isn't breaking any rules, and review [this helpful resource](https://woocommerce.com/document/grammar-punctuation-style-guide/) on content style.

## General

Use existing WordPress/WooCommerce UI, built in components (text fields, checkboxes, etc) and existing menu structures.

Plugins which draw on WordPress' core design aesthetic will benefit from future updates to this design as WordPress continues to evolve. If you need to make an exception for your product, be prepared to provide a valid use case.

-   [WordPress Components library](https://wordpress.github.io/gutenberg/?path=/story/docs-introduction--page)
-   [Figma for WordPress](https://make.wordpress.org/design/2018/11/19/figma-for-wordpress/) | ([WordPress Design Library Figma](https://www.figma.com/file/e4tLacmlPuZV47l7901FEs/WordPress-Design-Library))
-   [WooCommerce Component Library](https://woocommerce.github.io/woocommerce/)

## Component Library - Storybook

> Storybook is an open source tool for developing UI components in isolation for React, React Native and more. It makes building stunning UIs organized and efficient.

The WooCommerce repository also includes [Storybook](https://storybook.js.org/) integration that allows testing and developing in a WooCommerce-agnostic context. This is very helpful for developing reusable components and trying generic JavaScript modules without any backend dependency.

You can launch Storybook by running `pnpm --filter=@woocommerce/storybook watch:build` locally. It will open in your browser automatically.

You can also test Storybook for the current `trunk` branch on GitHub Pages: [https://woocommerce.github.io/woocommerce](https://woocommerce.github.io/woocommerce)
