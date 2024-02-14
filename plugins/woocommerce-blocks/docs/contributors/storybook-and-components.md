# Storybook & Components <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Where are our components?](#where-are-our-components)
-   [How to run Storybook locally and test components](#how-to-run-storybook-locally-and-test-components)
-   [How to add a story for a component](#how-to-add-a-story-for-a-component)

This repo includes [Storybook](https://storybook.js.org) tooling so we can test and develop components in isolation. The storybook is automatically built and published to [GitHub pages](https://woocommerce.github.io/woocommerce-blocks/) on every push to the main branch.

## Where are our components?

We have components in a few folders, for different contexts.

-   [`assets/js/base/components`](../../assets/js/base/components)
-   [`assets/js/editor-components`](../../assets/js/editor-components)
-   [`assets/js/icons`](../../assets/js/icons)

**`assets/js/base/components`** are used in front-end code, as well as editor & admin.
These components help us build consistent interfaces across the front end (shopper) experience and elsewhere.
Because they can be used in the front end and editor, components in this folder should:

-   Perform efficiently - i.e. not adversely affect page performance/experience.
-   Have lean dependencies - i.e. not bloat the payload unnecessarily.
-   Look consistent in common themes; ideally should allow themes to adjust appearance as necessary.

**`assets/js/editor-components`** are used in the editor UI for our blocks.
They allow us to build a consistent and powerful UI for merchants for authoring content relating to Woo data - e.g. selecting products or product attributes. Because they are focused on the editor, they can rely on known editor dependencies and optimise styling for the editor only.

**`assets/js/icons`** is a suite of icons and SVG images that we use in our interfaces.

For more info about individual components, refer to [Storybook](https://woocommerce.github.io/woocommerce-blocks/) or individual readme files.

## How to run Storybook locally and test components

-   `npm run storybook`
-   Point your browser at port 6006, e.g. <http://localhost:6006>
-   Play with components 🎛!

## How to add a story for a component

-   Add a `stories` folder alongside the component.
-   Add stories in `.js` files in this folder.

If you're stuck, copy source of an existing story to get started.

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/storybook-and-components.md)

<!-- /FEEDBACK -->

